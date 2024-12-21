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
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version201511171538 implements VersionInterface, ContainerAwareInterface
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
     * @param bool $up Indicates that this is up or down
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

        $this->session->save();
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
                $properties[$structureName][] = $property->getName();
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
        foreach ($property->getComponents() as $component) {
            foreach ($component->getChildren() as $childProperty) {
                if ('date' === $childProperty->getType()) {
                    $properties[$structureName][] = $property->getName();
                }
            }
        }
    }

    /**
     * Iterates over all nodes of the given type, and upgrades them.
     *
     * @param StructureMetadata $structureMetadata The structure metadata, whose pages have to be upgraded
     * @param array $properties The properties which are or contain date fields
     * @param bool $up Indicates that this is up or down
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
     * @param array $properties The properties which are or contain date fields$up
     */
    private function upgradeNode(NodeInterface $node, $locale, $properties, $up)
    {
        foreach ($properties as $property) {
            $propertyName = $this->propertyEncoder->localizedContentName($property, $locale);
            if ($node->hasProperty($propertyName)) {
                $value = $this->upgradeProperty($node->getPropertyValue($propertyName), $up);
                $node->setProperty($propertyName, $value);
            }
        }
    }

    /**
     * Upgrades the given property to the new date representation.
     *$up.
     */
    private function upgradeProperty($value, $up)
    {
        if ($up) {
            $this->upgradeDate($value);
        } else {
            $this->downgradeDate($value);
        }

        return $value;
    }

    /**
     * Upgrades the given date to the new representation.
     *
     * @param string $value The date to change
     */
    private function upgradeDate(&$value)
    {
        if ($value instanceof \DateTime) {
            return;
        }

        $value = \DateTime::createFromFormat('Y-m-d', $value);
    }

    /**
     * Downgrades the given date to the old representation.
     *
     * @param string $value The date to change
     */
    private function downgradeDate(&$value)
    {
        $value = $value->format('Y-m-d');
    }
}
