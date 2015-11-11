<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\Block\BlockPropertyInterface;
use Sulu\Component\Content\Compat\Block\BlockPropertyWrapper;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Exception\UnexpectedPropertyType;

/**
 * content type for block.
 */
class BlockContentType extends ComplexContentType implements ContentTypeExportInterface
{
    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * template for form generation.
     *
     * @var string
     */
    private $template;

    /**
     * @var string
     */
    private $languageNamespace;

    public function __construct(ContentTypeManagerInterface $contentTypeManager, $template, $languageNamespace)
    {
        $this->contentTypeManager = $contentTypeManager;
        $this->template = $template;
        $this->languageNamespace = $languageNamespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    /**
     * {@inheritdoc}
     */
    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($property->getIsBlock()) {
            /** @var BlockPropertyInterface $blockProperty */
            $blockProperty = $property;
            while (!($blockProperty instanceof BlockPropertyInterface)) {
                $blockProperty = $blockProperty->getProperty();
            }

            // init properties
            $typeProperty = new Property('type', '', 'text_line');
            $lengthProperty = new Property('length', '', 'text_line');

            // load length
            $contentType = $this->contentTypeManager->get($lengthProperty->getContentTypeName());
            $contentType->read(
                $node,
                new BlockPropertyWrapper($lengthProperty, $property),
                $webspaceKey,
                $languageCode,
                $segmentKey
            );
            $len = $lengthProperty->getValue();

            for ($i = 0; $i < $len; ++$i) {
                // load type
                $contentType = $this->contentTypeManager->get($typeProperty->getContentTypeName());
                $contentType->read(
                    $node,
                    new BlockPropertyWrapper($typeProperty, $property, $i),
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                );

                $blockPropertyType = $blockProperty->initProperties($i, $typeProperty->getValue());

                /** @var PropertyInterface $subProperty */
                foreach ($blockPropertyType->getChildProperties() as $subProperty) {
                    $contentType = $this->contentTypeManager->get($subProperty->getContentTypeName());
                    $contentType->read(
                        $node,
                        new BlockPropertyWrapper($subProperty, $property, $i),
                        $webspaceKey,
                        $languageCode,
                        $segmentKey
                    );
                }
            }
        } else {
            throw new UnexpectedPropertyType($property, $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasValue(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($property->getIsBlock()) {
            /** @var BlockPropertyInterface $blockProperty */
            $blockProperty = $property;
            while (!($blockProperty instanceof BlockPropertyInterface)) {
                $blockProperty = $blockProperty->getProperty();
            }

            // init properties
            $lengthProperty = new Property('length', '', 'text_line');
            $lengthBlockProperty = new BlockPropertyWrapper($lengthProperty, $property);
            $contentType = $this->contentTypeManager->get($lengthProperty->getContentTypeName());

            return $contentType->hasValue($node, $lengthBlockProperty, $webspaceKey, $languageCode, $segmentKey);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview(
        $data,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($property->getIsBlock()) {
            /** @var BlockPropertyInterface $blockProperty */
            $blockProperty = $property;
            while (!($blockProperty instanceof BlockPropertyInterface)) {
                $blockProperty = $blockProperty->getProperty();
            }

            $blockProperty->clearProperties();

            $len = count($data);

            for ($i = 0; $i < $len; ++$i) {
                $blockPropertyType = $blockProperty->initProperties($i, $data[$i]['type']);

                /** @var PropertyInterface $subProperty */
                foreach ($blockPropertyType->getChildProperties() as $subProperty) {
                    if (isset($data[$i][$subProperty->getName()])) {
                        $contentType = $this->contentTypeManager->get($subProperty->getContentTypeName());
                        $contentType->readForPreview(
                            $data[$i][$subProperty->getName()],
                            $subProperty,
                            $webspaceKey,
                            $languageCode,
                            $segmentKey
                        );
                    }
                }
            }

            $property->setValue($data);
        } else {
            throw new UnexpectedPropertyType($property, $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey,
        $isImport = false
    ) {
        if ($property->getIsBlock()) {
            /** @var BlockPropertyInterface $blockProperty */
            $blockProperty = $property;
            while (!($blockProperty instanceof BlockPropertyInterface)) {
                $blockProperty = $blockProperty->getProperty();
            }

            $data = $blockProperty->getValue();

            if (!$blockProperty->getIsMultiple()) {
                $data = [$data];
            }

            $data = array_filter($data);

            $len = count($data);

            // init properties
            $typeProperty = new Property('type', '', 'text_line');
            $lengthProperty = new Property('length', '', 'text_line');

            //save length
            $lengthProperty->setValue($len);
            $contentType = $this->contentTypeManager->get($lengthProperty->getContentTypeName());
            $contentType->write(
                $node,
                new BlockPropertyWrapper($lengthProperty, $property),
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            );

            for ($i = 0; $i < $len; ++$i) {
                $blockPropertyType = $blockProperty->getProperties($i);

                // save type property
                $typeProperty->setValue($blockPropertyType->getName());
                $this->writeProperty(
                    $typeProperty,
                    $property,
                    $i,
                    $node,
                    $userId,
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                );

                foreach ($blockProperty->getProperties($i)->getChildProperties() as $subProperty) {
                    $this->writeProperty(
                        $subProperty,
                        $property,
                        $i,
                        $node,
                        $userId,
                        $webspaceKey,
                        $languageCode,
                        $segmentKey,
                        $isImport
                    );
                }
            }
        } else {
            throw new UnexpectedPropertyType($property, $this);
        }
    }

    /**
     * write a property to node.
     */
    private function writeProperty(
        PropertyInterface $property,
        PropertyInterface $blockProperty,
        $index,
        NodeInterface $node,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey,
        $isImport = false
    ) {
        // save sub property
        $contentType = $this->contentTypeManager->get($property->getContentTypeName());
        $blockPropertyWrapper = new BlockPropertyWrapper($property, $blockProperty, $index);

        // TODO find a better why for change Types (same hack is used in ContentMapper:save )
        $contentType->remove(
            $node,
            $blockPropertyWrapper,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );

        if ($isImport && $contentType instanceof ContentTypeExportInterface) {
            $contentType->importData(
                $node,
                $blockPropertyWrapper,
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            );
        } else {
            $contentType->write(
                $node,
                $blockPropertyWrapper,
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        foreach ($node->getProperties($property->getName() . '-*')  as $nodeProperty) {
            $node->getProperty($nodeProperty->getName())->remove();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        return $this->prepareData(
            $property,
            function (ContentTypeInterface $contentType, $property) {
                return $contentType->getViewData($property);
            },
            false
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        return $this->prepareData(
            $property,
            function (ContentTypeInterface $contentType, $property) {
                return $contentType->getContentData($property);
            }
        );
    }

    /**
     * Returns prepared data from property
     * use callback to prepare data foreach property function($contentType, $property).
     *
     * @param PropertyInterface $property
     * @param callable          $dataCallback
     * @param bool              $returnType
     *
     * @return array
     */
    private function prepareData(PropertyInterface $property, callable $dataCallback, $returnType = true)
    {
        /** @var BlockPropertyInterface $blockProperty */
        $blockProperty = $property;
        while (!($blockProperty instanceof BlockPropertyInterface)) {
            $blockProperty = $blockProperty->getProperty();
        }

        $data = [];
        for ($i = 0; $i < $blockProperty->getLength(); ++$i) {
            $blockPropertyType = $blockProperty->getProperties($i);

            if ($returnType) {
                $type = $blockPropertyType->getName();
                $data[$i] = ['type' => $type];
            }

            foreach ($blockPropertyType->getChildProperties() as $childProperty) {
                $contentType = $this->contentTypeManager->get($childProperty->getContentTypeName());
                $data[$i][$childProperty->getName()] = $dataCallback($contentType, $childProperty);
            }
        }

        if (!$property->getIsMultiple() && count($data) > 0) {
            $data = $data[0];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function exportData($propertyValue)
    {
        return $propertyValue;
    }

    /**
     * {@inheritdoc}
     */
    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey, true);
    }
}
