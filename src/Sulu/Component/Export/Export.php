<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Export;

use Sulu\Component\Content\Metadata\PropertyMetadata;

/**
 * Base export for sulu documents.
 */
class Export
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var DocumentInspector
     */
    protected $documentInspector;

    /**
     * @var null
     */
    protected $exportLocale = 'en';

    /**
     * @var null
     */
    protected $format = '1.2.xliff';

    /**
     * Creates and returns a property-array.
     *
     * @param PropertyMetadata $property
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
     * @param BlockMetadata $property
     * @param PropertyValue $propertyValue
     * @param $format
     *
     * @return array
     */
    protected function getBlockPropertyData(BlockMetadata $property, $propertyValue)
    {
        $children = [];

        $blockDataList = $this->exportManager->export($property->getType(), $propertyValue);

        foreach ($blockDataList as $blockData) {
            $blockType = $blockData['type'];

            $block = $this->getPropertiesContentData(
                $property->getComponentByName($blockType)->getChildren(),
                $blockData,
                $this->format
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
     * @param $name
     * @param $value
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
     * @param $propertyValues
     * @param $format
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
     * @param $document
     * @param $locale
     *
     * @return array
     */
    protected function getContentData($document, $locale)
    {
        /** @var BasePageDocument $loadedDocument */
        $loadedDocument = $this->documentManager->find($document->getUuid(), $locale);

        /** @var \Sulu\Component\Content\Metadata\StructureMetadata $metaData */
        $metaData = $this->documentInspector->getStructureMetadata($document);

        $propertyValues = $loadedDocument->getStructure()->toArray();
        $properties = $metaData->getProperties();

        $contentData = $this->getPropertiesContentData($properties, $propertyValues);

        return $contentData;
    }

    /**
     * Returns export template for given format like XLIFF1.2.
     *
     * @param $format
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getTemplate($format)
    {
        if (!isset($this->formatFilePaths[$format])) {
            throw new \Exception(sprintf('No format "%s" configured for Snippet export', $format));
        }

        $templatePath = $this->formatFilePaths[$format];

        if (!$this->templating->exists($templatePath)) {
            throw new \Exception(sprintf('No template file "%s" found for Snippet export', $format));
        }

        return $templatePath;
    }
}
