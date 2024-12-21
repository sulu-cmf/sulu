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
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Upgrades URLs within block properties.
 */
class Version201511240844 implements VersionInterface, ContainerAwareInterface
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
        $this->upgradeExternalLinks(true);
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
        $this->upgradeExternalLinks(false);
    }

    /**
     * External links are easily updated by fetching all nodes with the external redirect type, and add or remove the
     * scheme to the external property.
     *
     * @param bool $addScheme Adds the scheme to URLs if true, removes the scheme otherwise
     */
    private function upgradeExternalLinks($addScheme)
    {
        foreach ($this->localizationManager->getLocalizations() as $localization) {
            $rows = $this->session->getWorkspace()->getQueryManager()->createQuery(
                \sprintf(
                    'SELECT * FROM [nt:unstructured] WHERE [%s] = "%s"',
                    $this->propertyEncoder->localizedSystemName('nodeType', $localization->getLocale()),
                    RedirectType::EXTERNAL
                ),
                'JCR-SQL2'
            )->execute();

            $name = $this->propertyEncoder->localizedSystemName('external', $localization->getLocale());
            foreach ($rows->getNodes() as $node) {
                /** @var NodeInterface $node */
                $value = $node->getPropertyValue($name);

                if ($addScheme) {
                    $this->upgradeUrl($value);
                } else {
                    $this->downgradeUrl($value);
                }

                $node->setProperty($name, $value);
            }
        }
    }

    /**
     * Structures are updated according to their xml definition.
     *
     * @param bool $addScheme Adds the scheme to URLs if true, removes the scheme otherwise
     */
    private function iterateStructures($addScheme)
    {
        $properties = [];

        // find templates containing URL fields
        $structureMetadatas = \array_merge(
            $this->structureMetadataFactory->getStructures('page'),
            $this->structureMetadataFactory->getStructures('snippet')
        );

        $structureMetadatas = \array_filter(
            $structureMetadatas,
            function(StructureMetadata $structureMetadata) use (&$properties) {
                $structureName = $structureMetadata->getName();
                $this->findUrlProperties($structureMetadata, $properties);

                return !empty($properties[$structureName]) || !empty($blockProperties[$structureName]);
            }
        );

        foreach ($structureMetadatas as $structureMetadata) {
            $this->iterateStructureNodes(
                $structureMetadata,
                $properties[$structureMetadata->getName()],
                $addScheme
            );
        }

        $this->documentManager->flush();
    }

    /**
     * Returns all properties which are a URL field.
     *
     * @param StructureMetadata $structureMetadata The metadata in which the URL fields are searched
     * @param array $properties The properties which are URL fields are added to this array
     */
    private function findUrlProperties(StructureMetadata $structureMetadata, array &$properties)
    {
        $structureName = $structureMetadata->getName();
        foreach ($structureMetadata->getProperties() as $property) {
            if ('url' === $property->getType()) {
                $properties[$structureName][] = ['property' => $property];
            } elseif ($property instanceof BlockMetadata) {
                $this->findUrlBlockProperties($property, $structureName, $properties);
            }
        }
    }

    /**
     * Adds the block property to the list, if it contains a URL field.
     *
     * @param BlockMetadata $property The block property to check
     * @param string $structureName The name of the structure the property belongs to
     * @param array $properties The list of properties, to which the block is added if it is a URL field
     */
    private function findUrlBlockProperties(BlockMetadata $property, $structureName, array &$properties)
    {
        $result = ['property' => $property, 'components' => []];
        foreach ($property->getComponents() as $component) {
            $componentResult = ['component' => $component, 'children' => []];
            foreach ($component->getChildren() as $childProperty) {
                if ('url' === $childProperty->getType()) {
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
     * @param array $properties The properties which are or contain URL fields
     * @param bool $addScheme Adds the scheme to URLs if true, removes the scheme otherwise
     */
    private function iterateStructureNodes(StructureMetadata $structureMetadata, array $properties, $addScheme)
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
                $this->upgradeNode($node, $localization->getLocale(), $properties, $addScheme);
            }
        }
    }

    /**
     * Upgrades the node to new URL representation.
     *
     * @param NodeInterface $node The node to be upgraded
     * @param string $locale The locale of the node to be upgraded
     * @param array $properties The properties which are or contain URL fields
     * @param bool $addScheme Adds the scheme to URLs if true, removes the scheme otherwise
     */
    private function upgradeNode(NodeInterface $node, $locale, array $properties, $addScheme)
    {
        /** @var BasePageDocument $document */
        $document = $this->documentManager->find($node->getIdentifier(), $locale);
        $documentLocales = $this->documentInspector->getLocales($document);

        if (!\in_array($locale, $documentLocales)) {
            return;
        }

        foreach ($properties as $property) {
            $propertyValue = $document->getStructure()->getProperty($property['property']->getName());

            if ($property['property'] instanceof BlockMetadata) {
                $this->upgradeBlockProperty($property['property'], $property['components'], $propertyValue, $addScheme);
            } else {
                $this->upgradeProperty($propertyValue, $addScheme);
            }
        }

        $this->documentManager->persist($document, $locale, ['auto_name' => false]);
    }

    /**
     * Upgrades the given block property to the new URL representation.
     *
     * @param bool $addScheme
     */
    private function upgradeBlockProperty(
        BlockMetadata $blockProperty,
        array $components,
        PropertyValue $propertyValue,
        $addScheme
    ) {
        $componentNames = \array_map(
            function($item) {
                return $item['component']->getName();
            },
            $components
        );

        $value = $propertyValue->getValue();
        foreach ($value as &$item) {
            if (!\in_array($item['type'], $componentNames)) {
                continue;
            }

            foreach ($components[$item['type']]['children'] as $child) {
                if (!isset($item[$child->getName()])) {
                    continue;
                }

                if ($addScheme) {
                    $item[$child->getName()] = $this->upgradeUrl($item[$child->getName()]);
                } else {
                    $item[$child->getName()] = $this->downgradeUrl($item[$child->getName()]);
                }
            }
        }

        $propertyValue->setValue($value);
    }

    /**
     * Upgrades the given property to the new URL representation.
     *
     * @param PropertyValue $property The current property value, which will be updated
     * @param bool $addScheme Adds the scheme to URLs if true, removes the scheme otherwise
     */
    private function upgradeProperty(PropertyValue $property, $addScheme)
    {
        $value = $property->getValue();
        if ($addScheme) {
            $this->upgradeUrl($value);
        } else {
            $this->downgradeUrl($value);
        }

        $property->setValue($value);
    }

    /**
     * Upgrades the given URL to the new representation.
     *
     * @param string $value The url to change
     *
     * @return string
     */
    private function upgradeUrl(&$value)
    {
        if (!empty($value)
            && false === \strpos($value, 'http://')
            && false === \strpos($value, 'https://')
            && false === \strpos($value, 'ftp://')
            && false === \strpos($value, 'ftps://')
            && false === \strpos($value, 'mailto:')
            && false === \strpos($value, '//')
        ) {
            $value = 'http://' . $value;
        }

        return $value;
    }

    /**
     * Downgrades the given URl to the old representation.
     *
     * @param string $value The url to change
     *
     * @return string
     */
    private function downgradeUrl(&$value)
    {
        if (0 === \strpos($value, 'http://')) {
            $value = \substr($value, 7);
        }

        return $value;
    }
}
