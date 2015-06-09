<?php

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Component\Content\Compat\Property as LegacyProperty;
use Sulu\Component\Content\Compat\Section\SectionProperty;
use Sulu\Component\Content\Compat\Block\BlockProperty;
use Sulu\Component\Content\Metadata\Property;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Content\Metadata\ItemMetadata;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Compat\PropertyTag;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\PropertyParameter;

/**
 * Creates legacy properties from "new" properties.
 *
 * @deprecated
 */
class LegacyPropertyFactory
{
    private $namespaceRegistry;

    /**
     * @param NamespaceRegistry $namespaceRegistry
     */
    public function __construct(NamespaceRegistry $namespaceRegistry)
    {
        $this->namespaceRegistry = $namespaceRegistry;
    }

    /**
     * Create a new "translated" property
     *
     * @param Item $item
     * @param string $locale
     * @return PropertyInterface
     */
    public function createTranslatedProperty($property, $locale, StructureInterface $structure = null)
    {
        $property = new TranslatedProperty(
            $this->createProperty($property, $structure),
            $locale,
            $this->namespaceRegistry->getPrefix('content_localized')
        );

        return $property;
    }

    /**
     * Create a new property
     *
     * @param Item $item
     * @return PropertyInterface $property
     */
    public function createProperty(ItemMetadata $property, StructureInterface $structure = null)
    {
        if ($property instanceof SectionMetadata) {
            return $this->createSectionProperty($property, $structure);
        }

        if ($property instanceof BlockMetadata) {
            return $this->createBlockProperty($property, $structure);
        }

        if (null === $property->getType()) {
            throw new \RuntimeException(sprintf(
                'Property name "%s" has no type.',
                $property->name
            ));
        }

        $parameters = $this->arrayToParameters($property->getParameters());
        $propertyBridge = new LegacyProperty(
            $property->getName(),
            array(
                'title' => $property->title,
                'info_text' => $property->description,
                'placeholder' => $property->placeholder,
            ),
            $property->getType(),
            $property->isRequired(),
            $property->isLocalized(),
            $property->getMaxOccurs(),
            $property->getMinOccurs(),
            $parameters,
            array(),
            $property->getColspan()
        );

        foreach ($property->tags as $tag) {
            $propertyBridge->addTag(new PropertyTag($tag['name'], $tag['priority'], $tag['attributes']));
        }

        $propertyBridge->setStructure($structure);

        return $propertyBridge;
    }

    private function arrayToParameters($arrayParams)
    {
        $parameters = array();
        foreach ($arrayParams as $arrayParam) {
            $value = $arrayParam['value'];

            if (is_array($value)) {
                $value = $this->arrayToParameters($value);
            }

            $parameters[] = new PropertyParameter($arrayParam['name'], $value, $arrayParam['type'], $arrayParam['meta']);
        }

        return $parameters;
    }

    private function createSectionProperty(SectionMetadata $property, StructureInterface $structure = null)
    {
        $sectionProperty = new SectionProperty(
            $property->getName(),
            array(
                'title' => $property->title,
                'info_text' => $property->description,
            ),
            $property->getColspan()
        );

        foreach ($property->getChildren() as $child) {
            $sectionProperty->addChild($this->createProperty($child, $structure));
        }

        return $sectionProperty;
    }

    private function createBlockProperty(BlockMetadata $property, StructureInterface $structure = null)
    {
        $blockProperty = new BlockProperty(
            $property->getName(),
            array(
                'title' => $property->title,
                'info_text' => $property->description,
            ),
            $property->getDefaultComponentName(),
            $property->isRequired(),
            $property->isLocalized(),
            $property->getMaxOccurs(),
            $property->getMinOccurs(),
            $property->getParameters(),
            array(),
            $property->getColspan()
        );
        $blockProperty->setStructure($structure);

        foreach ($property->getComponents() as $component) {
            $blockPropertyType = new BlockPropertyType(
                $component->getName(),
                array(
                    'title' => $property->title,
                    'info_text' => $property->description,
                )
            );

            foreach ($component->getChildren() as $property) {
                $blockPropertyType->addChild($this->createProperty($property, $structure));
            }

            $blockProperty->addType($blockPropertyType);
        }

        return $blockProperty;
    }
}
