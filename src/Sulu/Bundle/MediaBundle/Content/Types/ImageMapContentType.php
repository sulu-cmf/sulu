<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AllOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ArrayMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ConstMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\IfThenElseMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\RefSchemaMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType\ReferenceContentTypeInterface;
use Sulu\Component\Content\Compat\Block\BlockPropertyWrapper;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Document\Subscriber\PHPCR\SuluNode;
use Sulu\Component\Content\Exception\UnexpectedPropertyType;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;
use Sulu\Component\Content\PreResolvableContentTypeInterface;

class ImageMapContentType extends ComplexContentType implements ContentTypeExportInterface, PreResolvableContentTypeInterface, PropertyMetadataMapperInterface, ReferenceContentTypeInterface
{
    public function __construct(
        private ContentTypeManagerInterface $contentTypeManager,
        private FormMetadataMapper $formMetadataMapper,
    ) {
    }

    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $data = [
            'imageId' => null,
            'hotspots' => [],
        ];

        // init properties
        $imageIdProperty = new Property('imageId', '', 'text_line');
        $typeProperty = new Property('type', '', 'text_line');
        $hotspotProperty = new Property('hotspot', '', 'text_line');
        $lengthProperty = new Property('length', '', 'text_line');

        // load imageId
        $contentType = $this->contentTypeManager->get($imageIdProperty->getContentTypeName());
        $contentType->read(
            $node,
            new BlockPropertyWrapper($imageIdProperty, $property),
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
        $imageId = $imageIdProperty->getValue();
        $data['imageId'] = $imageId ? (int) $imageId : null;

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
            $hotspotData = [];

            // load type
            $contentType = $this->contentTypeManager->get($typeProperty->getContentTypeName());
            $contentType->read(
                $node,
                new BlockPropertyWrapper($typeProperty, $property, $i),
                $webspaceKey,
                $languageCode,
                $segmentKey
            );
            $type = $typeProperty->getValue();
            $hotspotData['type'] = $type;

            if (!$property->hasType($type)) {
                continue;
            }

            $contentType = $this->contentTypeManager->get($hotspotProperty->getContentTypeName());
            $contentType->read(
                $node,
                new BlockPropertyWrapper($hotspotProperty, $property, $i),
                $webspaceKey,
                $languageCode,
                $segmentKey
            );
            $hotspot = \json_decode($hotspotProperty->getValue(), true);
            $hotspotData['hotspot'] = $hotspot;

            $propertyType = $property->initProperties($i, $type);

            foreach ($propertyType->getChildProperties() as $subProperty) {
                $contentType = $this->contentTypeManager->get($subProperty->getContentTypeName());
                $contentType->read(
                    $node,
                    new BlockPropertyWrapper($subProperty, $property, $i),
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                );

                $hotspotData[$subProperty->getName()] = $subProperty->getValue();
            }

            $data['hotspots'][] = $hotspotData;
        }

        $property->setValue($data);
    }

    public function hasValue(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        // init properties
        $imageIdProperty = new BlockPropertyWrapper(
            new Property('imageId', '', 'text_line'),
            $property
        );
        $contentType = $this->contentTypeManager->get($imageIdProperty->getContentTypeName());

        return $contentType->hasValue($node, $imageIdProperty, $webspaceKey, $languageCode, $segmentKey);
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
        $data = $property->getValue();

        $imageId = $data['imageId'] ?? null;
        $hotspots = $data['hotspots'] ?? [];

        $len = \count($hotspots);

        // init properties
        $imageIdProperty = new Property('imageId', '', 'text_line');
        $typeProperty = new Property('type', '', 'text_line');
        $hotspotProperty = new Property('hotspot', '', 'text_line');
        $lengthProperty = new Property('length', '', 'text_line');

        //save imageId
        $this->writeProperty(
            $imageIdProperty,
            $property,
            $imageId,
            null,
            $node,
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey,
            $isImport
        );

        //save length
        $this->writeProperty(
            $lengthProperty,
            $property,
            $len,
            null,
            $node,
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey,
            $isImport
        );

        for ($i = 0; $i < $len; ++$i) {
            $hotspot = $hotspots[$i];
            $propertyType = $property->initProperties($i, $hotspot['type']);

            $this->writeProperty(
                $typeProperty,
                $property,
                $propertyType->getName(),
                $i,
                $node,
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey,
                $isImport
            );

            $this->writeProperty(
                $hotspotProperty,
                $property,
                \json_encode($hotspots[$i]['hotspot'] ?? null),
                $i,
                $node,
                $userId,
                $webspaceKey,
                $languageCode,
                $segmentKey,
                $isImport
            );

            /** @var PropertyInterface $subProperty */
            foreach ($propertyType->getChildProperties() as $subProperty) {
                if (!isset($hotspot[$subProperty->getName()])) {
                    continue;
                }

                $subName = $subProperty->getName();
                $subValue = $hotspot[$subName];

                if ($subValue instanceof PropertyValue) {
                    $subValueProperty = new PropertyValue($subName, $subValue);
                    $subProperty->setPropertyValue($subValueProperty);
                    $hotspot[$subName] = $subValueProperty;
                } else {
                    $subProperty->setValue($subValue);
                }
            }

            foreach ($propertyType->getChildProperties() as $subProperty) {
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
    ): void {
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
        $data = $this->prepareData(
            $property,
            function(ContentTypeInterface $contentType, $property) {
                return $contentType->getContentData($property);
            }
        );

        if (!isset($data['image'])) {
            return null;
        }

        return $data;
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
        $value = $property->getValue();

        $imageId = $value['imageId'] ?? null;
        $imageId = $imageId ? (int) $imageId : null;

        $imageProperty = new Property('image', '', 'single_media_selection');
        $imageProperty->setValue(['id' => $imageId]);
        $imageProperty->setStructure($property->getStructure());
        $contentType = $this->contentTypeManager->get($imageProperty->getContentTypeName());

        $data = [
            'image' => $dataCallback($contentType, $imageProperty),
            'hotspots' => [],
        ];

        $hotspots = $value['hotspots'] ?? [];
        foreach ($hotspots as $i => $hotspot) {
            $hotspotData = [];

            $propertyType = $property->initProperties($i, $hotspot['type']);
            foreach ($propertyType->getChildProperties() as $childProperty) {
                $childProperty->setValue($hotspot[$childProperty->getName()] ?? null);
                $contentType = $this->contentTypeManager->get($childProperty->getContentTypeName());

                $hotspotData[$childProperty->getName()] = $dataCallback($contentType, $childProperty);
            }

            if ($returnType) {
                $hotspotData['type'] = $hotspot['type'];
                $hotspotData['hotspot'] = $hotspot['hotspot'];
            }

            $data['hotspots'][] = $hotspotData;
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

    public function mapPropertyMetadata(ContentPropertyMetadata $propertyMetadata): PropertyMetadata
    {
        $blockTypeSchemas = [];
        foreach ($propertyMetadata->getComponents() as $blockType) {
            if ($blockType->hasTag('sulu.global_block')) {
                $blockName = $blockType->getTag('sulu.global_block')['attributes']['global_block'];
                $blockTypeSchemas[] = new IfThenElseMetadata(
                    new SchemaMetadata([
                        new PropertyMetadata('type', true, new ConstMetadata($blockType->getName())),
                    ]),
                    new RefSchemaMetadata('#/definitions/' . $blockName)
                );

                continue;
            }

            $blockTypeSchemas[] = new IfThenElseMetadata(
                new SchemaMetadata([
                    new PropertyMetadata('type', true, new ConstMetadata($blockType->getName())),
                ]),
                $this->formMetadataMapper->mapSchema($blockType->getChildren()),
            );
        }

        return new PropertyMetadata(
            (string) $propertyMetadata->getName(),
            $propertyMetadata->isRequired(),
            new SchemaMetadata([
                new PropertyMetadata('imageId', $propertyMetadata->isRequired()),
                new PropertyMetadata(
                    'hotspots', $propertyMetadata->isRequired(), new ArrayMetadata(
                        new AllOfsMetadata($blockTypeSchemas)
                    )),
            ])
        );
    }

    public function getReferences(PropertyInterface $property, ReferenceCollectorInterface $referenceCollector, string $propertyPrefix = ''): void
    {
        $value = $property->getValue();

        if (!$value) {
            return;
        }

        $imageId = $value['imageId'] ?? null;
        if ($imageId) {
            $imageProperty = new Property('image', '', 'single_media_selection');
            $imageProperty->setValue(['id' => $imageId]);
            $imageProperty->setStructure($property->getStructure());
            /** @var SingleMediaSelection $contentType */
            $contentType = $this->contentTypeManager->get($imageProperty->getContentTypeName());

            if ($contentType instanceof ReferenceContentTypeInterface) {
                $contentType->getReferences(
                    $imageProperty,
                    $referenceCollector,
                    $propertyPrefix . $property->getName() . '.'
                );
            }
        }

        $hotspots = $value['hotspots'] ?? [];
        foreach ($hotspots as $index => $value) {
            $propertyType = $property->getType($value['type']);

            foreach ($propertyType->getChildProperties() as $child) {
                $contentType = $this->contentTypeManager->get($child->getContentTypeName());
                $childName = $child->getName();

                if (!$contentType instanceof ReferenceContentTypeInterface || !isset($value[$childName])) {
                    continue;
                }

                $child->setValue($value[$childName]);
                $contentType->getReferences(
                    $child,
                    $referenceCollector,
                    $propertyPrefix . $property->getName() . '.hotspots[' . $index . '].'
                );
            }
        }
    }
}
