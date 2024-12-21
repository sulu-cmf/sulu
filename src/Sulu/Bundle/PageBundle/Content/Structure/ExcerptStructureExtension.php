<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Content\Structure;

use PHPCR\NodeInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType\ReferenceContentTypeInterface;
use Sulu\Bundle\SearchBundle\Search\Factory;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\Content\Extension\ReferenceExtensionInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Export\Manager\ExportManagerInterface;
use Sulu\Component\Import\Manager\ImportManagerInterface;

/**
 * extends structure with seo content.
 */
class ExcerptStructureExtension extends AbstractExtension implements ExportExtensionInterface, ReferenceExtensionInterface
{
    /**
     * name of structure extension.
     */
    public const EXCERPT_EXTENSION_NAME = 'excerpt';

    /**
     * will be filled with data in constructor
     * {@inheritdoc}
     */
    protected $properties = [];

    protected $name = self::EXCERPT_EXTENSION_NAME;

    protected $additionalPrefix = self::EXCERPT_EXTENSION_NAME;

    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var ExportManagerInterface
     */
    protected $exportManager;

    /**
     * @var ImportManagerInterface
     */
    protected $importManager;

    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @var string
     */
    private $languageCode;

    /**
     * @var Factory
     */
    private $factory;

    public function __construct(
        StructureManagerInterface $structureManager,
        ContentTypeManagerInterface $contentTypeManager,
        ExportManagerInterface $exportManager,
        ImportManagerInterface $importManager,
        Factory $factory
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
        $this->exportManager = $exportManager;
        $this->importManager = $importManager;
        $this->factory = $factory;
    }

    public function save(NodeInterface $node, $data, $webspaceKey, $languageCode)
    {
        $this->setLanguageCode($languageCode, 'i18n', null);

        $excerptStructure = $this->getExcerptStructure($languageCode);

        foreach ($excerptStructure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if (\array_key_exists($property->getName(), $data)) {
                $property->setValue($data[$property->getName()]);
                $contentType->write(
                    $node,
                    new TranslatedProperty(
                        $property,
                        $languageCode,
                        $this->languageNamespace,
                        $this->additionalPrefix
                    ),
                    null, // userid
                    $webspaceKey,
                    $languageCode,
                    null // segmentkey
                );
            }
        }
    }

    public function load(NodeInterface $node, $webspaceKey, $languageCode)
    {
        $excerptStructure = $this->getExcerptStructure($languageCode);

        $data = [];
        foreach ($excerptStructure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $contentType->read(
                $node,
                new TranslatedProperty(
                    $property,
                    $languageCode,
                    $this->languageNamespace,
                    $this->additionalPrefix
                ),
                $webspaceKey,
                $languageCode,
                null // segmentkey
            );

            $data[$property->getName()] = $property->getValue();
        }

        return $data;
    }

    public function setLanguageCode($languageCode, $languageNamespace, $namespace)
    {
        // lazy load excerpt structure to avoid redeclaration of classes
        // should be done before parent::setLanguageCode because it uses the $thi<->properties
        // which will be set in initExcerptStructure
        $this->initProperties($languageCode);

        $this->languageCode = $languageCode;
        $this->languageNamespace = $languageNamespace;

        parent::setLanguageCode($languageCode, $languageNamespace, $namespace);
    }

    public function getContentData($container)
    {
        $container = new ExcerptValueContainer($container);

        $data = [];
        foreach ($this->getExcerptStructure()->getProperties() as $property) {
            if ($container->__isset($property->getName())) {
                $property->setValue($container->__get($property->getName()));
                $contentType = $this->contentTypeManager->get($property->getContentTypeName());
                $data[$property->getName()] = $contentType->getContentData($property);
            }
        }

        return $data;
    }

    public function getFieldMapping()
    {
        $mappings = parent::getFieldMapping();

        foreach ($this->getExcerptStructure()->getPropertiesByTagName('sulu.search.field') as $property) {
            $tag = $property->getTag('sulu.search.field');
            $tagAttributes = $tag->getAttributes();

            $mappings['excerpt' . \ucfirst($property->getName())] = [
                'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                'field' => $this->factory->createMetadataExpression(
                    \sprintf('object.getExtensionsData()["excerpt"]["%s"]', $property->getName())
                ),
            ];
        }

        return $mappings;
    }

    /**
     * Returns and caches excerpt-structure.
     *
     * @param string $locale
     *
     * @return StructureInterface
     */
    private function getExcerptStructure($locale = null)
    {
        if (null === $locale) {
            $locale = $this->languageCode;
        }

        $excerptStructure = $this->structureManager->getStructure(self::EXCERPT_EXTENSION_NAME);
        $excerptStructure->setLanguageCode($locale);

        return $excerptStructure;
    }

    /**
     * Initiates structure and properties.
     *
     * @param string $locale
     */
    private function initProperties($locale)
    {
        // Reset the properties before new initialization.
        $this->properties = [];

        /** @var PropertyInterface $property */
        foreach ($this->getExcerptStructure($locale)->getProperties() as $property) {
            $this->properties[] = $property->getName();
        }
    }

    public function export($properties, $format = null)
    {
        $container = new ExcerptValueContainer($properties);

        $data = [];
        foreach ($this->getExcerptStructure()->getProperties() as $property) {
            if ($container->__isset($property->getName())) {
                $property->setValue($container->__get($property->getName()));
                $contentType = $this->contentTypeManager->get($property->getContentTypeName());
                if ($this->exportManager->hasExport($property->getContentTypeName(), $format)) {
                    $options = $this->exportManager->getOptions($property->getContentTypeName(), $format);

                    $data[$property->getName()] = [
                        'name' => $property->getName(),
                        'value' => $contentType->exportData($property->getValue()),
                        'type' => $property->getContentTypeName(),
                        'options' => $options,
                    ];
                }
            }
        }

        return $data;
    }

    public function getImportPropertyNames()
    {
        $propertyNames = [];

        foreach ($this->getExcerptStructure()->getProperties() as $property) {
            $propertyNames[] = $property->getName();
        }

        return $propertyNames;
    }

    public function import(NodeInterface $node, $data, $webspaceKey, $languageCode, $format)
    {
        $this->setLanguageCode($languageCode, 'i18n', null);

        foreach ($this->getExcerptStructure()->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if (isset($data[$property->getName()])
                && $this->importManager->hasImport($property->getContentTypeName(), $format)
            ) {
                /* @var ContentTypeExportInterface $contentType */
                $contentType->importData(
                    $node,
                    new TranslatedProperty(
                        $property,
                        $languageCode,
                        $this->languageNamespace,
                        $this->additionalPrefix
                    ),
                    $data[$property->getName()],
                    null, // userid
                    $webspaceKey,
                    $languageCode,
                    null // segmentkey
                );
            }
        }
    }

    public function getReferences(array $data, ReferenceCollectorInterface $referenceCollector, string $propertyPrefix = ''): void
    {
        foreach ($this->getExcerptStructure()->getProperties(true) as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if (!$contentType instanceof ReferenceContentTypeInterface) {
                continue;
            }
            $property->setValue($data[$property->getName()] ?? null);

            $contentType->getReferences(
                $property,
                $referenceCollector,
                $propertyPrefix
            );
        }
    }
}
