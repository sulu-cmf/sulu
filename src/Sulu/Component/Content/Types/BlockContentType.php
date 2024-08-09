<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType\ReferenceContentTypeInterface;
use Sulu\Component\Content\Compat\Block\BlockPropertyInterface;
use Sulu\Component\Content\Compat\Block\BlockPropertyWrapper;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Subscriber\PHPCR\SuluNode;
use Sulu\Component\Content\Exception\UnexpectedPropertyType;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\Types\Block\BlockVisitorInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * content type for block.
 */
class BlockContentType extends ComplexContentType implements ContentTypeExportInterface, PreResolvableContentTypeInterface, ReferenceContentTypeInterface
{
    /**
     * @param string $languageNamespace
     * @param BlockVisitorInterface[] $blockVisitors
     */
    public function __construct(
        private ContentTypeManagerInterface $contentTypeManager,
        private $languageNamespace,
        /**
         * @deprecated This property is not needed anymore and will be removed in Sulu 3.0
         */
        private RequestAnalyzerInterface $requestAnalyzer,
        /**
         * @deprecated This property is not needed anymore and will be removed in Sulu 3.0
         */
        private ?TargetGroupStoreInterface $targetGroupStore = null,
        private iterable $blockVisitors
    ) {
    }

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
            $settingsProperty = new Property('settings', '', 'text_line');
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

                if (!$blockProperty->hasType($typeProperty->getValue())) {
                    continue;
                }

                $contentType = $this->contentTypeManager->get($settingsProperty->getContentTypeName());
                $contentType->read(
                    $node,
                    new BlockPropertyWrapper($settingsProperty, $property, $i),
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                );

                $blockPropertyType = $blockProperty->initProperties($i, $typeProperty->getValue());

                $settings = \json_decode($settingsProperty->getValue(), true);
                $blockPropertyType->setSettings(!empty($settings) ? $settings : new \stdClass());

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

    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        return $this->doWrite($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey, false);
    }

    /**
     * Save the value from given property.
     *
     * @param string $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string|null $segmentKey
     * @param bool $isImport
     *
     * @throws UnexpectedPropertyType
     */
    private function doWrite(
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

            $data = \array_filter($data);

            $len = \count($data);

            // init properties
            $typeProperty = new Property('type', '', 'text_line');
            $settingsProperty = new Property('settings', '', 'text_line');
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

                $this->writeProperty(
                    $typeProperty,
                    $property,
                    $blockPropertyType->getName(),
                    $i,
                    $node,
                    $userId,
                    $webspaceKey,
                    $languageCode,
                    $segmentKey,
                    $isImport
                );

                $this->writeProperty(
                    $settingsProperty,
                    $property,
                    \json_encode($blockPropertyType->getSettings()),
                    $i,
                    $node,
                    $userId,
                    $webspaceKey,
                    $languageCode,
                    $segmentKey,
                    $isImport
                );

                foreach ($blockProperty->getProperties($i)->getChildProperties() as $subProperty) {
                    $this->writeProperty(
                        $subProperty,
                        $property,
                        $subProperty->getValue(),
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
        $value,
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
        $blockPropertyWrapper->setValue($value);

        if ($isImport && $contentType instanceof ContentTypeExportInterface) {
            $contentType->importData(
                new SuluNode($node),
                $blockPropertyWrapper,
                $value,
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey
            );

            return;
        }

        $contentType->write(
            new SuluNode($node),
            $blockPropertyWrapper,
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
    }

    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        foreach ($node->getProperties($property->getName() . '-*') as $nodeProperty) {
            $node->getProperty($nodeProperty->getName())->remove();
        }
    }

    public function getViewData(PropertyInterface $property)
    {
        return $this->prepareData(
            $property,
            function(ContentTypeInterface $contentType, $property) {
                return $contentType->getViewData($property);
            },
            false
        );
    }

    public function getContentData(PropertyInterface $property)
    {
        return $this->prepareData(
            $property,
            function(ContentTypeInterface $contentType, $property) {
                return $contentType->getContentData($property);
            }
        );
    }

    /**
     * Returns prepared data from property
     * use callback to prepare data foreach property function($contentType, $property).
     *
     * @param bool $returnType
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

        $blockPropertyTypes = [];
        for ($i = 0; $i < $blockProperty->getLength(); ++$i) {
            $blockPropertyType = $blockProperty->getProperties($i);

            foreach ($this->blockVisitors as $blockVisitor) {
                $blockPropertyType = $blockVisitor->visit($blockPropertyType);

                if (!$blockPropertyType) {
                    break;
                }
            }

            if ($blockPropertyType) {
                $blockPropertyTypes[] = $blockPropertyType;
            }
        }

        $data = [];
        foreach ($blockPropertyTypes as $blockPropertyType) {
            $blockPropertyTypeSettings = $blockPropertyType->getSettings();

            $blockData = [];

            if ($returnType) {
                $blockData['type'] = $blockPropertyType->getName();
                $blockData['settings'] = $blockPropertyTypeSettings;
            }

            foreach ($blockPropertyType->getChildProperties() as $childProperty) {
                $contentType = $this->contentTypeManager->get($childProperty->getContentTypeName());
                $blockData[$childProperty->getName()] = $dataCallback($contentType, $childProperty);
            }

            $data[] = $blockData;
        }

        if (!$property->getIsMultiple() && \count($data) > 0) {
            $data = $data[0];
        }

        return $data;
    }

    public function exportData($propertyValue)
    {
        return $propertyValue;
    }

    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $property->setValue($value);
        $this->doWrite($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey, true);
    }

    public function preResolve(PropertyInterface $property)
    {
        $this->prepareData(
            $property,
            function(ContentTypeInterface $contentType, $property) {
                if (!$contentType instanceof PreResolvableContentTypeInterface) {
                    return;
                }

                return $contentType->preResolve($property);
            },
            false
        );
    }

    public function getReferences(PropertyInterface $property, ReferenceCollectorInterface $referenceCollector, string $propertyPrefix = ''): void
    {
        $values = $property->getValue();

        if (!\is_array($values)) {
            return;
        }

        foreach ($values as $index => $value) {
            $propertyType = $property->getType($value['type']);

            foreach ($propertyType->getChildProperties() as $child) {
                $contentType = $this->contentTypeManager->get($child->getContentTypeName());
                $childName = $child->getName();

                if (!$contentType instanceof ReferenceContentTypeInterface || !isset($value[$childName])) {
                    continue;
                }

                $oldValue = $child->getValue();
                $child->setValue($value[$childName]);
                $contentType->getReferences(
                    $child,
                    $referenceCollector,
                    $propertyPrefix . $property->getName() . '[' . $index . '].'
                );
                $child->setValue($oldValue);
            }
        }
    }
}
