<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle;

use PHPCR\Migrations\VersionInterface;
use PHPCR\NodeInterface;
use PHPCR\PhpcrMigrationsBundle\ContainerAwareInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Upgrades date values within block properties.
 *
 * Created: 2015-12-10 10:04
 */
class Version201511240843 implements VersionInterface, ContainerAwareInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    public function setContainer(?ContainerInterface $container = null): void
    {
        if (null === $container) {
            throw new \RuntimeException('Container is required to run this migration.');
        }

        $this->structureMetadataFactory = $container->get('sulu_page.structure.factory');
        $this->propertyEncoder = $container->get('sulu_document_manager.property_encoder');
        $this->localizationManager = $container->get('sulu.core.localization_manager');
        $this->documentManager = $container->get('sulu_document_manager.document_manager');
        $this->documentInspector = $container->get('sulu_document_manager.document_inspector');
    }

    /**
     * Migrate the repository up.
     *
     * @return void
     */
    public function up(SessionInterface $session)
    {
        $this->session = $session;
        $this->iterateStructures(true);
    }

    /**
     * Migrate the system down.
     *
     * @return void
     */
    public function down(SessionInterface $session)
    {
        $this->session = $session;
        $this->iterateStructures(false);
    }

    /**
     * Structures are updated according to their xml definition.
     *
     * @param bool $up
     */
    private function iterateStructures($up)
    {
        $properties = [];

        // find templates containing date fields
        $structureMetadatas = \array_merge(
            $this->structureMetadataFactory->getStructures('page'),
            $this->structureMetadataFactory->getStructures('snippet')
        );

        $structureMetadatas = \array_filter(
            $structureMetadatas,
            function(StructureMetadata $structureMetadata) use (&$properties) {
                $structureName = $structureMetadata->getName();
                $this->findDateProperties($structureMetadata, $properties);

                return !empty($properties[$structureName]) || !empty($blockProperties[$structureName]);
            }
        );

        foreach ($structureMetadatas as $structureMetadata) {
            $this->iterateStructureNodes(
                $structureMetadata,
                $properties[$structureMetadata->getName()],
                $up
            );
        }

        $this->documentManager->flush();
    }

    /**
     * Returns all properties which are a date field.
     *
     * @param StructureMetadata $structureMetadata The metadata in which the date fields are searched
     * @param array $properties The properties which are date fields are added to this array
     */
    private function findDateProperties(StructureMetadata $structureMetadata, array &$properties)
    {
        $structureName = $structureMetadata->getName();
        foreach ($structureMetadata->getProperties() as $property) {
            if ('date' === $property->getType()) {
                $properties[$structureName][] = ['property' => $property];
            } elseif ($property instanceof BlockMetadata) {
                $this->findDateBlockProperties($property, $structureName, $properties);
            }
        }
    }

    /**
     * Adds the block property to the list, if it contains a date field.
     *
     * @param BlockMetadata $property The block property to check
     * @param string $structureName The name of the structure the property belongs to
     * @param array $properties The list of properties, to which the block is added if it is a date field
     */
    private function findDateBlockProperties(BlockMetadata $property, $structureName, array &$properties)
    {
        $result = ['property' => $property, 'components' => []];
        foreach ($property->getComponents() as $component) {
            $componentResult = ['component' => $component, 'children' => []];
            foreach ($component->getChildren() as $childProperty) {
                if ('date' === $childProperty->getType()) {
                    $componentResult['children'][$childProperty->getName()] = $childProperty;
                }
            }

            if (\count($componentResult['children']) > 0) {
                $result['components'][$component->getName()] = $componentResult;
            }
        }

        if (\count($result['components']) > 0) {
            $properties[$structureName][] = $result;
        }
    }

    /**
     * Iterates over all nodes of the given type, and upgrades them.
     *
     * @param StructureMetadata $structureMetadata The structure metadata, whose pages have to be upgraded
     * @param array $properties The properties which are or contain date fields
     * @param bool $up
     */
    private function iterateStructureNodes(StructureMetadata $structureMetadata, array $properties, $up)
    {
        foreach ($this->localizationManager->getLocalizations() as $localization) {
            $rows = $this->session->getWorkspace()->getQueryManager()->createQuery(
                \sprintf(
                    'SELECT * FROM [nt:unstructured] WHERE [%s] = "%s" OR [%s] = "%s"',
                    $this->propertyEncoder->localizedSystemName('template', $localization->getLocale()),
                    $structureMetadata->getName(),
                    'template',
                    $structureMetadata->getName()
                ),
                'JCR-SQL2'
            )->execute();

            foreach ($rows->getNodes() as $node) {
                $this->upgradeNode($node, $localization->getLocale(), $properties, $up);
            }
        }
    }

    /**
     * Upgrades the node to new date representation.
     *
     * @param NodeInterface $node The node to be upgraded
     * @param string $locale The locale of the node to be upgraded
     * @param array $properties The properties which are or contain date fields
     * @param bool $up
     */
    private function upgradeNode(NodeInterface $node, $locale, array $properties, $up)
    {
        /** @var BasePageDocument $document */
        $document = $this->documentManager->find($node->getIdentifier(), $locale);
        $documentLocales = $this->documentInspector->getLocales($document);

        if (!\in_array($locale, $documentLocales)) {
            return;
        }

        foreach ($properties as $property) {
            if ($property['property'] instanceof BlockMetadata) {
                $this->upgradeBlockProperty($property['property'], $property['components'], $node, $locale, $up);
            } else {
                $this->upgradeProperty($property['property'], $node, $locale, $up);
            }
        }

        $this->documentManager->persist($document, $locale, ['auto_name' => false]);
    }

    /**
     * Upgrades the given block property to the new date representation.
     *
     * @param string $locale
     * @param bool $up
     */
    private function upgradeBlockProperty(
        BlockMetadata $blockProperty,
        array $components,
        NodeInterface $node,
        $locale,
        $up
    ) {
        $componentNames = \array_map(
            function($item) {
                return $item['component']->getName();
            },
            $components
        );

        $lengthName = \sprintf('i18n:%s-%s-length', $locale, $blockProperty->getName());
        $length = $node->getPropertyValue($lengthName);

        for ($i = 0; $i < $length; ++$i) {
            $type = $node->getPropertyValue(\sprintf('i18n:%s-%s-type#%s', $locale, $blockProperty->getName(), $i));

            if (!\in_array($type, $componentNames)) {
                continue;
            }

            foreach ($components[$type]['children'] as $child) {
                $name = \sprintf('i18n:%s-%s-%s#%s', $locale, $blockProperty->getName(), $child->getName(), $i);
                if (!$node->hasProperty($name)) {
                    continue;
                }

                $value = $node->getPropertyValue($name);

                if ($up) {
                    $value = $this->upgradeDate($value);
                } else {
                    $value = $this->downgradeDate($value);
                }

                $node->setProperty($name, $value);
            }
        }
    }

    /**
     * Upgrades the given property to the new date representation.
     *
     * @param bool $up
     */
    private function upgradeProperty(PropertyMetadata $property, NodeInterface $node, $locale, $up)
    {
        $name = \sprintf('i18n:%s-%s', $locale, $property->getName());
        if (!$node->hasProperty($name)) {
            return;
        }

        $value = $node->getPropertyValue($name);

        if ($up) {
            $value = $this->upgradeDate($value);
        } else {
            $value = $this->downgradeDate($value);
        }

        $node->setProperty($name, $value);
    }

    /**
     * Upgrades the given date to the new representation.
     *
     * @param string $value The date to change
     *
     * @return string
     */
    private function upgradeDate(&$value)
    {
        if ($value instanceof \DateTime) {
            return $value;
        }

        $value = \DateTime::createFromFormat('Y-m-d', $value);

        return $value;
    }

    /**
     * Downgrades the given date to the old representation.
     *
     * @param string $value The date to change
     *
     * @return string
     */
    private function downgradeDate(&$value)
    {
        $value = $value->format('Y-m-d');

        return $value;
    }
}
