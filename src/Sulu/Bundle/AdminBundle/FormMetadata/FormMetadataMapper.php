<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AllOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ArrayMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ConstMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\IfThenElseMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\RefSchemaMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Component\Content\Metadata\BlockMetadata as ContentBlockMetadata;
use Sulu\Component\Content\Metadata\ItemMetadata as ContentItemMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata as ContentSectionMetadata;

/**
 * Maps the deprecated form metadata objects to the actual ones.
 */
class FormMetadataMapper
{
    public function __construct(private PropertyMetadataMapperRegistry $propertyMetadataMapperRegistry)
    {
    }

    /**
     * @return ItemMetadata[]
     */
    public function mapChildren(array $children, string $locale): array
    {
        $items = [];
        foreach ($children as $child) {
            if ($child instanceof ContentBlockMetadata || $child instanceof ContentPropertyMetadata) {
                $item = $this->mapProperty($child, $locale);
            } elseif ($child instanceof ContentSectionMetadata) {
                $item = $this->mapSection($child, $locale);
            } else {
                throw new \Exception('Unsupported property given "' . \get_class($child) . '"');
            }

            $items[$item->getName()] = $item;
        }

        return $items;
    }

    /**
     * @param ContentItemMetadata[] $itemsMetadata
     */
    public function mapSchema(array $itemsMetadata): SchemaMetadata
    {
        return new SchemaMetadata($this->mapSchemaProperties($itemsMetadata));
    }

    /**
     * @param mixed[] $tagsMetadata
     */
    public function mapTags(array $tagsMetadata): array
    {
        $tags = [];
        foreach ($tagsMetadata as $tagMetadata) {
            $tag = new TagMetadata();
            $tag->setName($tagMetadata['name']);
            $tag->setPriority($tagMetadata['priority'] ?? null);
            $tag->setAttributes($tagMetadata['attributes'] ?? []);

            $tags[] = $tag;
        }

        return $tags;
    }

    private function mapSection(ContentSectionMetadata $property, string $locale): SectionMetadata
    {
        $section = new SectionMetadata($property->getName());

        $title = $property->getTitle($locale);
        if ($title) {
            $section->setLabel($title);
        }

        $section->setColSpan($property->getColSpan());
        $section->setDisabledCondition($property->getDisabledCondition());
        $section->setVisibleCondition($property->getVisibleCondition());

        foreach ($property->getChildren() as $component) {
            if ($component instanceof ContentBlockMetadata || $component instanceof ContentPropertyMetadata) {
                $item = $this->mapProperty($component, $locale);
            } elseif ($component instanceof ContentSectionMetadata) {
                $item = $this->mapSection($component, $locale);
            } else {
                throw new \Exception('Unsupported property given "' . \get_class($property) . '"');
            }

            $section->addItem($item);
        }

        return $section;
    }

    private function mapProperty(ContentPropertyMetadata $property, string $locale): FieldMetadata
    {
        $field = new FieldMetadata($property->getName());
        $field->setDefaultType($property->getDefaultComponentName());
        $field->setTags($this->mapTags($property->getTags()));

        $field->setLabel($property->getTitle($locale));
        $field->setDisabledCondition($property->getDisabledCondition());
        $field->setVisibleCondition($property->getVisibleCondition());
        $field->setDescription($property->getDescription($locale));
        $field->setType($property->getType());
        $field->setColSpan($property->getColSpan());
        $field->setRequired($property->isRequired());
        $field->setOnInvalid($property->getOnInvalid());
        $field->setSpaceAfter($property->getSpaceAfter());
        $field->setMinOccurs($property->getMinOccurs());
        $field->setMaxOccurs($property->getMaxOccurs());

        foreach ($property->getParameters() as $parameter) {
            $field->addOption($this->mapOption($parameter, $locale));
        }

        foreach ($property->getComponents() as $component) {
            $type = new FormMetadata();
            $type->setName($component->getName());
            $type->setTitle($component->getTitle($locale) ?? \ucfirst($component->getName()));
            $type->setTags($this->mapTags($component->getTags()));

            $typeChildren = $this->mapChildren($component->getChildren(), $locale);

            foreach ($typeChildren as $typeChild) {
                $type->addItem($typeChild);
            }

            $field->addType($type);
        }

        return $field;
    }

    private function mapOption(array $parameter, string $locale): OptionMetadata
    {
        $option = new OptionMetadata();
        $option->setName($parameter['name']);
        $option->setType($parameter['type']);

        if (OptionMetadata::TYPE_COLLECTION === $parameter['type']) {
            foreach ($parameter['value'] as $parameterName => $parameterValue) {
                $valueOption = new OptionMetadata();
                $valueOption->setName($parameterValue['name']);
                $valueOption->setValue($parameterValue['value']);

                $this->mapOptionMeta($parameterValue, $locale, $valueOption);

                $option->addValueOption($valueOption);
            }
        } elseif (OptionMetadata::TYPE_STRING === $parameter['type'] || OptionMetadata::TYPE_EXPRESSION === $parameter['type']) {
            $option->setValue($parameter['value']);
            $this->mapOptionMeta($parameter, $locale, $option);
        } else {
            throw new \Exception('Unsupported parameter given "' . \get_class($parameter) . '"');
        }

        return $option;
    }

    private function mapOptionMeta(array $parameterValue, string $locale, OptionMetadata $option): void
    {
        if (!\array_key_exists('meta', $parameterValue)) {
            return;
        }

        foreach ($parameterValue['meta'] as $metaKey => $metaValues) {
            if (\array_key_exists($locale, $metaValues)) {
                switch ($metaKey) {
                    case 'title':
                        $option->setTitle($metaValues[$locale]);
                        break;
                    case 'info_text':
                        $option->setInfotext($metaValues[$locale]);
                        break;
                    case 'placeholder':
                        $option->setPlaceholder($metaValues[$locale]);
                        break;
                }
            }
        }
    }

    /**
     * @param ContentItemMetadata[] $itemsMetadata
     *
     * @return PropertyMetadata[]
     */
    private function mapSchemaProperties(array $itemsMetadata): array
    {
        return \array_filter(\array_map(function(ContentItemMetadata $itemMetadata) {
            if ($itemMetadata instanceof ContentSectionMetadata) {
                return $this->mapSchemaProperties($itemMetadata->getChildren());
            }

            if ($itemMetadata instanceof ContentBlockMetadata) {
                $blockTypeSchemas = [];
                foreach ($itemMetadata->getComponents() as $blockType) {
                    $metadata = new SchemaMetadata($this->mapSchemaProperties($blockType->getChildren()));
                    if ($blockType->hasTag('sulu.global_block')) {
                        $definitionName = $blockType->getTag('sulu.global_block')['attributes']['global_block'];
                        $metadata = new RefSchemaMetadata('#/definitions/' . $definitionName);
                    }

                    $blockTypeSchemas[] = new IfThenElseMetadata(
                        new SchemaMetadata([
                            new PropertyMetadata('type', true, new ConstMetadata($blockType->getName())),
                        ]),
                        $metadata
                    );
                }

                return new PropertyMetadata(
                    $itemMetadata->getName(),
                    $itemMetadata->isRequired(),
                    new ArrayMetadata(
                        new AllOfsMetadata($blockTypeSchemas)
                    )
                );
            }

            /** @var ContentPropertyMetadata $propertyMetadata */
            $propertyMetadata = $itemMetadata;
            $type = $propertyMetadata->getType();

            if ($this->propertyMetadataMapperRegistry->has($type)) {
                return $this->propertyMetadataMapperRegistry
                    ->get($type)
                    ->mapPropertyMetadata($propertyMetadata);
            }

            return new PropertyMetadata(
                $propertyMetadata->getName(),
                $propertyMetadata->isRequired()
            );
        }, $itemsMetadata));
    }
}
