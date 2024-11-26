<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Component\Content\Compat\Block\BlockProperty;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\Property as LegacyProperty;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\PropertyTag;
use Sulu\Component\Content\Compat\Section\SectionProperty;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ComponentMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\ItemMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\NamespaceRegistry;

/**
 * Creates legacy properties from "new" properties.
 *
 * @deprecated
 */
class LegacyPropertyFactory
{
    public function __construct(
        private NamespaceRegistry $namespaceRegistry,
        private StructureMetadataFactoryInterface $structureFactory
    ) {
    }

    /**
     * Create a new "translated" property.
     *
     * @param object $property
     * @param string $locale
     *
     * @return PropertyInterface
     */
    public function createTranslatedProperty($property, $locale, ?StructureInterface $structure = null)
    {
        if ($property instanceof ItemMetadata) {
            $property = $this->createProperty($property, $structure);
        }

        return new TranslatedProperty(
            $property,
            $locale,
            $this->namespaceRegistry->getPrefix('content_localized')
        );
    }

    /**
     * Create a new property.
     *
     * @return PropertyInterface $property
     */
    public function createProperty(ItemMetadata $property, ?StructureInterface $structure = null)
    {
        if ($property instanceof SectionMetadata) {
            return $this->createSectionProperty($property, $structure);
        }

        if ($property instanceof BlockMetadata) {
            return $this->createBlockProperty($property, $structure);
        }

        if (!$property instanceof PropertyMetadata) {
            throw new \RuntimeException(\sprintf(
                'Property needs to be of type [%s].',
                \implode(', ', [
                    PropertyMetadata::class,
                    BlockMetadata::class,
                    SectionMetadata::class,
                ])
            ));
        }

        if (null === $property->getType()) {
            throw new \RuntimeException(\sprintf(
                'Property name "%s" has no type.',
                $property->getName()
            ));
        }

        $parameters = $this->convertArrayToParameters($property->getParameters());
        $propertyBridge = new LegacyProperty(
            $property->getName(),
            [
                'title' => $property->getTitles(),
                'info_text' => $property->getDescriptions(),
                'placeholder' => $property->getPlaceholders(),
            ],
            $property->getType(),
            $property->isRequired(),
            $property->isLocalized(),
            $property->getMaxOccurs(),
            $property->getMinOccurs(),
            $parameters,
            [],
            $property->getColSpan(),
            $property->getDefaultComponentName()
        );

        foreach ($property->getTags() as $tag) {
            $propertyBridge->addTag(new PropertyTag($tag['name'], $tag['priority'], $tag['attributes']));
        }

        foreach ($property->getComponents() as $component) {
            if ($component->hasTag('sulu.global_block')) {
                $propertyBridge->addType($this->createReferenceType($component, $structure));

                continue;
            }

            $propertyBridge->addType($this->createType($component, $structure));
        }
        $propertyBridge->setStructure($structure);

        return $propertyBridge;
    }

    private function convertArrayToParameters($arrayParams)
    {
        $parameters = [];
        foreach ($arrayParams as $arrayParam) {
            $value = $arrayParam['value'];

            if (\is_array($value)) {
                $value = $this->convertArrayToParameters($value);
            }

            $parameters[$arrayParam['name']] = new PropertyParameter($arrayParam['name'], $value, $arrayParam['type'], $arrayParam['meta']);
        }

        return $parameters;
    }

    private function createSectionProperty(SectionMetadata $property, ?StructureInterface $structure = null)
    {
        $sectionProperty = new SectionProperty(
            $property->getName(),
            [
                'title' => $property->getTitles(),
                'info_text' => $property->getDescriptions(),
            ],
            $property->getColSpan()
        );

        foreach ($property->getChildren() as $child) {
            $sectionProperty->addChild($this->createProperty($child, $structure));
        }

        return $sectionProperty;
    }

    private function createBlockProperty(BlockMetadata $property, ?StructureInterface $structure = null)
    {
        $blockProperty = new BlockProperty(
            $property->getName(),
            [
                'title' => $property->getTitles(),
                'info_text' => $property->getDescriptions(),
            ],
            $property->getDefaultComponentName(),
            $property->isRequired(),
            $property->isLocalized(),
            $property->getMaxOccurs(),
            $property->getMinOccurs(),
            $property->getParameters(),
            [],
            $property->getColSpan()
        );
        $blockProperty->setStructure($structure);

        foreach ($property->getComponents() as $component) {
            if ($component->hasTag('sulu.global_block')) {
                $blockProperty->addType($this->createReferenceType($component, $structure));

                continue;
            }

            $blockProperty->addType($this->createType($component, $structure));
        }

        return $blockProperty;
    }

    private function createReferenceType(ComponentMetadata $component, ?StructureInterface $structure = null): BlockPropertyType
    {
        if (!$this->structureFactory) {
            throw new \RuntimeException('The required service "sulu_page.structure.factory" was not injected.');
        }

        /** @var StructureMetadata $structureMetadata */
        $structureMetadata = $this->structureFactory->getStructureMetadata('block', $component->getName());
        if (!$structureMetadata) {
            throw new \InvalidArgumentException(
                \sprintf('Global block with name "%s" was not found!', $component->getName())
            );
        }

        if ($structure) {
            $propertyType = new BlockPropertyType(
                $component->getName(),
                [
                    'title' => $structureMetadata->getTitle($structure->getLanguageCode()),
                    'info_text' => $structureMetadata->getDescription($structure->getLanguageCode()),
                ]
            );
        } else {
            $propertyType = new BlockPropertyType($component->getName(), []);
        }

        foreach ($structureMetadata->getProperties() as $property) {
            $propertyType->addChild($this->createProperty($property, $structure));
        }

        return $propertyType;
    }

    public function createType(ComponentMetadata $component, ?StructureInterface $structure = null): BlockPropertyType
    {
        $propertyType = new BlockPropertyType(
            $component->getName(),
            [
                'title' => $component->getTitles(),
                'info_text' => $component->getDescriptions(),
            ]
        );

        foreach ($component->getChildren() as $property) {
            $propertyType->addChild($this->createProperty($property, $structure));
        }

        return $propertyType;
    }
}
