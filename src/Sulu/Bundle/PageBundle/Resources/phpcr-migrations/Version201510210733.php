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

class Version201510210733 implements VersionInterface, ContainerAwareInterface
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

        // TODO external link
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
                $properties[$structureName][] = $property->getName();
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
        foreach ($property->getComponents() as $component) {
            foreach ($component->getChildren() as $childProperty) {
                if ('url' === $childProperty->getType()) {
                    $properties[$structureName][] = $property->getName();
                }
            }
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
    private function upgradeNode(NodeInterface $node, $locale, $properties, $addScheme)
    {
        /** @var BasePageDocument $document */
        $document = $this->documentManager->find($node->getIdentifier(), $locale);
        $documentLocales = $this->documentInspector->getLocales($document);

        if (!\in_array($locale, $documentLocales)) {
            return;
        }

        foreach ($properties as $property) {
            $this->upgradeProperty($document->getStructure()->getProperty($property), $addScheme);
        }

        $this->documentManager->persist($document, $locale);
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
        if (\is_array($value)) {
            foreach ($value as $key => $entry) {
                if ('url' !== $entry['type']) {
                    continue;
                }

                if ($addScheme) {
                    $this->upgradeUrl($entry['url']);
                } else {
                    $this->downgradeUrl($entry['url']);
                }

                $value[$key] = $entry;
            }
        } elseif ($addScheme) {
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
     */
    private function upgradeUrl(&$value)
    {
        if (!empty($value) && !\strpos($value, '://')) {
            $value = 'http://' . $value;
        }
    }

    /**
     * Downgrades the given URl to the old representation.
     *
     * @param string $value The url to change
     */
    private function downgradeUrl(&$value)
    {
        if (0 === \strpos($value, 'http://')) {
            $value = \substr($value, 7);
        }
    }
}
