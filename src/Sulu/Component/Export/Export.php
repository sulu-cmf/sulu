<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Export;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\Export\Manager\ExportManagerInterface;
use Twig\Environment;

/**
 * Base export for sulu documents.
 */
class Export
{
    /**
     * @var string
     */
    protected $exportLocale = 'en';

    /**
     * @var string
     */
    protected $format = '1.2.xliff';

    /**
     * @param string[] $formatFilePaths
     */
    public function __construct(
        protected Environment $templating,
        protected DocumentManagerInterface $documentManager,
        protected DocumentInspector $documentInspector,
        protected ExportManagerInterface $exportManager,
        protected array $formatFilePaths
    ) {
        $this->templating = $templating;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->exportManager = $exportManager;
        $this->formatFilePaths = $formatFilePaths;
    }

    /**
     * Creates and returns a property-array.
     *
     * @param PropertyValue $propertyValue
     *
     * @return array
     */
    protected function getPropertyData(PropertyMetadata $property, $propertyValue)
    {
        return $this->createProperty(
            $property->getName(),
            $this->exportManager->export($property->getType(), $propertyValue),
            $this->exportManager->getOptions($property->getType(), $this->format),
            $property->getType()
        );
    }

    /**
     * Creates and Returns a property-array for content-type Block.
     *
     * @param PropertyValue $propertyValue
     *
     * @return array
     */
    protected function getBlockPropertyData(BlockMetadata $property, $propertyValue)
    {
        $children = [];

        $blockDataList = $this->exportManager->export($property->getType(), $propertyValue);

        foreach ($blockDataList as $blockData) {
            $blockType = $blockData['type'];

            $component = $property->getComponentByName($blockType);
            if (!$component) {
                continue;
            }

            $block = $this->getPropertiesContentData(
                $component->getChildren(),
                $blockData
            );

            $block['type'] = $this->createProperty(
                'type',
                $blockType,
                $this->exportManager->getOptions($property->getType(), $this->format),
                $property->getType() . '_type'
            );

            $children[] = $block;
        }

        return $this->createProperty(
            $property->getName(),
            null,
            $this->exportManager->getOptions($property->getType(), $this->format),
            $property->getType(),
            $children
        );
    }

    /**
     * Returns a array with the given value (name, value and options).
     *
     * @param string $name
     * @param array $options
     * @param string $type
     * @param array $children
     *
     * @return array
     */
    protected function createProperty($name, $value = null, $options = [], $type = '', $children = null)
    {
        $property = [
            'name' => $name,
            'type' => $type,
            'options' => $options,
        ];

        if ($children) {
            $property['children'] = $children;
        } else {
            $property['value'] = $value;
        }

        return $property;
    }

    /**
     * Returns the Content as a flat array.
     *
     * @param PropertyMetadata[] $properties
     * @param mixed[] $propertyValues
     *
     * @return array
     */
    protected function getPropertiesContentData($properties, $propertyValues)
    {
        $contentData = [];

        foreach ($properties as $property) {
            if ($this->exportManager->hasExport($property->getType(), $this->format)) {
                if (!isset($propertyValues[$property->getName()])) {
                    continue;
                }

                $propertyValue = $propertyValues[$property->getName()];

                if ($property instanceof BlockMetadata) {
                    $data = $this->getBlockPropertyData($property, $propertyValue);
                } else {
                    $data = $this->getPropertyData($property, $propertyValue);
                }

                $contentData[$property->getName()] = $data;
            }
        }

        return $contentData;
    }

    /**
     * Returns a array of the given content data of the document.
     *
     * @param StructureBehavior $document
     * @param string $locale
     *
     * @return array
     *
     * @throws DocumentManagerException
     */
    protected function getContentData($document, $locale)
    {
        /** @var BasePageDocument $loadedDocument */
        $loadedDocument = $this->documentManager->find($document->getUuid(), $locale);

        /** @var StructureMetadata $metaData */
        $metaData = $this->documentInspector->getStructureMetadata($document);

        $propertyValues = $loadedDocument->getStructure()->toArray();
        $properties = $metaData->getProperties();

        $contentData = $this->getPropertiesContentData($properties, $propertyValues);

        return $contentData;
    }

    /**
     * Returns export template for given format like XLIFF1.2.
     *
     * @param string $format
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getTemplate($format)
    {
        if (!isset($this->formatFilePaths[$format])) {
            throw new \Exception(\sprintf('No format "%s" configured for export', $format));
        }

        $templatePath = $this->formatFilePaths[$format];

        if (!$this->templating->getLoader()->exists($templatePath)) {
            throw new \Exception(\sprintf('No template file "%s" found for export', $format));
        }

        return $templatePath;
    }
}
