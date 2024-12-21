<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Parser;

use Sulu\Component\Content\Exception\InvalidDefaultTypeException;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ComponentMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Content\Metadata\XmlParserTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Parse properties structure from an XML file.
 */
class PropertiesXmlParser
{
    use XmlParserTrait;

    /**
     * @var string[]
     */
    private $locales;

    public function __construct(private TranslatorInterface $translator, array $locales)
    {
        $this->locales = \array_keys($locales);
    }

    public function load(
        &$tags,
        \DOMXPath $xpath,
        \DOMNode $context,
        ?string $formKey = null
    ): array {
        $propertyData = $this->loadProperties($tags, $xpath, $context, $formKey);

        return $this->mapProperties($propertyData);
    }

    private function loadProperties(&$tags, \DOMXPath $xpath, \DOMNode $context, ?string $formKey): array
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query('x:*', $context) as $node) {
            if ('property' === $node->tagName) {
                $value = $this->loadProperty($xpath, $node, $tags, $formKey);
                $result[$value['name']] = $value;
            } elseif ('block' === $node->tagName) {
                $value = $this->loadBlock($xpath, $node, $tags, $formKey);
                $result[$value['name']] = $value;
            } elseif ('section' === $node->tagName) {
                $value = $this->loadSection($xpath, $node, $tags, $formKey);
                $result[$value['name']] = $value;
            }
        }

        return $result;
    }

    private function loadProperty(\DOMXPath $xpath, \DOMNode $node, &$tags, $formKey)
    {
        $result = $this->loadValues(
            $xpath,
            $node,
            [
                'name',
                'type',
                'default-type',
                'minOccurs',
                'maxOccurs',
                'colspan',
                'cssClass',
                'spaceAfter',
                'disabledCondition',
                'visibleCondition',
            ]
        );

        $result['mandatory'] = $this->getValueFromXPath('@mandatory', $xpath, $node, false);
        $result['multilingual'] = $this->getValueFromXPath('@multilingual', $xpath, $node, true);
        $result['onInvalid'] = $this->getValueFromXPath('@onInvalid', $xpath, $node);
        $result['tags'] = $this->loadTags($tags, $xpath, $node);
        $result['params'] = $this->loadParams('x:params/x:param', $xpath, $node);
        $result['meta'] = $this->loadMeta($xpath, $node);
        $result['types'] = $this->loadTypes($tags, $xpath, $node, $formKey);

        $typeNames = \array_map(function($type) {
            return $type['name'];
        }, $result['types']);

        if (!empty($typeNames)) {
            if (!$result['default-type'] && null !== ($key = \array_key_first($typeNames))) {
                $result['default-type'] = $typeNames[$key];
            }

            if (!\in_array($result['default-type'], $typeNames)) {
                throw new InvalidDefaultTypeException($result['name'], $result['default-type'], $typeNames);
            }
        }

        return $result;
    }

    private function validateTag($tag, &$tags)
    {
        if (!isset($tags[$tag['name']])) {
            $tags[$tag['name']] = [];
        }

        $tags[$tag['name']][] = $tag['priority'];
    }

    private function loadTag(\DOMXPath $xpath, \DOMNode $node)
    {
        $tag = [
            'name' => null,
            'priority' => null,
            'attributes' => [],
        ];

        foreach ($node->attributes as $key => $attr) {
            if (\in_array($key, ['name', 'priority'])) {
                $tag[$key] = $attr->value;
            } else {
                $tag['attributes'][$key] = $attr->value;
            }
        }

        return $tag;
    }

    private function loadBlock(\DOMXPath $xpath, \DOMNode $node, &$tags, $formKey)
    {
        $result = $this->loadProperty($xpath, $node, $tags, $formKey);
        $result['type'] = 'block';

        return $result;
    }

    private function loadSection(\DOMXPath $xpath, \DOMNode $node, &$tags, $formKey)
    {
        $result = $this->loadValues(
            $xpath,
            $node,
            ['name', 'colspan', 'cssClass', 'disabledCondition', 'visibleCondition']
        );

        $result['type'] = 'section';
        $result['params'] = $this->loadParams('x:params/x:param', $xpath, $node);
        $result['meta'] = $this->loadMeta($xpath, $node);

        $propertiesNode = $xpath->query('x:properties', $node)->item(0);
        $result['properties'] = $this->loadProperties($tags, $xpath, $propertiesNode, $formKey);

        return $result;
    }

    private function loadTags(&$tags, \DOMXPath $xpath, ?\DOMNode $context = null)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query('x:tag', $context) as $node) {
            $tag = $this->loadTag($xpath, $node);
            $this->validateTag($tag, $tags);

            $result[] = $tag;
        }

        return $result;
    }

    private function loadTypes(&$tags, \DOMXPath $xpath, ?\DOMNode $context, $formKey)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query('x:types/x:type', $context) as $node) {
            $value = $this->loadType($xpath, $node, $tags, $formKey);
            $result[$value['name']] = $value;
        }

        return $result;
    }

    private function loadType(\DOMXPath $xpath, \DOMNode $node, &$tags, $formKey)
    {
        $result = $this->loadValues($xpath, $node, ['name', 'ref']);
        if ($result['ref'] && $result['name']) {
            throw new \InvalidArgumentException(\sprintf(
                "Element '{http://schemas.sulu.io/template/template}type', attribute 'name' / 'ref': The attribute 'name' and 'ref' is not allowed at the same time. (in %s - line %s)",
                $node->baseURI,
                $node->getLineNo()
            ));
        } elseif (!$result['ref'] && !$result['name']) {
            throw new \InvalidArgumentException(\sprintf(
                "Element '{http://schemas.sulu.io/template/template}type', attribute 'name' / 'ref': The attribute 'name' or 'ref' is required. (in %s - line %s)",
                $node->baseURI,
                $node->getLineNo()
            ));
        }

        if ($result['ref']) {
            $result['name'] = $result['ref'];
            $result['ref'] = true;
        }

        $result['meta'] = $this->loadMeta($xpath, $node);

        $propertiesNode = $xpath->query('x:properties', $node)->item(0);
        if ($propertiesNode) {
            $result['properties'] = $this->loadProperties($tags, $xpath, $propertiesNode, $formKey);
        }

        return $result;
    }

    private function loadValues(\DOMXPath $xpath, \DOMNode $node, $keys, $prefix = '@')
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->getValueFromXPath($prefix . $key, $xpath, $node);
        }

        return $result;
    }

    private function loadMeta(\DOMXPath $xpath, ?\DOMNode $context = null)
    {
        $result = [];
        $metaNode = $xpath->query('x:meta', $context)->item(0);

        if (!$metaNode) {
            return $result;
        }

        $result['title'] = $this->loadMetaTag('x:title', $xpath, $metaNode);
        $result['info_text'] = $this->loadMetaTag('x:info_text', $xpath, $metaNode);
        $result['placeholder'] = $this->loadMetaTag('x:placeholder', $xpath, $metaNode);

        return $result;
    }

    private function loadMetaTag($path, \DOMXPath $xpath, ?\DOMNode $context = null)
    {
        $result = [];

        $translationKey = null;

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $lang = $this->getValueFromXPath('@lang', $xpath, $node);

            if (!$lang) {
                $translationKey = $node->textContent;

                continue;
            }

            $result[$lang] = $node->textContent;
        }

        if (!$translationKey) {
            return $result;
        }

        $missingLocales = \array_diff($this->locales, \array_keys($result));
        foreach ($missingLocales as $missingLocale) {
            $result[$missingLocale] = $this->translator->trans($translationKey, [], 'admin', $missingLocale);
        }

        return $result;
    }

    private function loadParams($path, \DOMXPath $xpath, ?\DOMNode $context = null)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $result[] = $this->loadParam($xpath, $node);
        }

        return $result;
    }

    private function loadParam(\DOMXPath $xpath, \DOMNode $node)
    {
        $result = [
            'name' => $this->getValueFromXPath('@name', $xpath, $node),
            'type' => $this->getValueFromXPath('@type', $xpath, $node, 'string'),
            'meta' => $this->loadMeta($xpath, $node),
        ];

        $result['value'] = match ($result['type']) {
            'collection' => $this->loadParams('x:param', $xpath, $node),
            default => $this->getValueFromXPath('@value', $xpath, $node),
        };

        return $result;
    }

    private function mapProperties(array $data): array
    {
        $properties = [];
        foreach ($data as $propertyName => $dataProperty) {
            $property = $this->createProperty($propertyName, $dataProperty);

            if ($property) {
                $properties[] = $property;
            }
        }

        return $properties;
    }

    /**
     * @return null|BlockMetadata|PropertyMetadata|SectionMetadata
     */
    private function createProperty(string $propertyName, array $propertyData)
    {
        if ('block' === $propertyData['type']) {
            return $this->createBlock($propertyName, $propertyData);
        }

        if ('section' === $propertyData['type']) {
            return $this->createSection($propertyName, $propertyData);
        }

        $property = new PropertyMetadata();
        $property->setName($propertyName);
        $this->mapProperty($property, $propertyData);

        return $property;
    }

    private function createSection($propertyName, $data): SectionMetadata
    {
        $section = new SectionMetadata();
        $section->setName($propertyName);
        if (isset($data['colspan'])) {
            $section->setColSpan($data['colspan']);
        }

        if (isset($data['meta']['title'])) {
            $section->setTitles($data['meta']['title']);
        }

        if (isset($data['meta']['info_text'])) {
            $section->setDescriptions($data['meta']['info_text']);
        }

        if (isset($data['disabledCondition'])) {
            $section->setDisabledCondition($this->normalizeConditionData($data['disabledCondition']));
        }

        if (isset($data['visibleCondition'])) {
            $section->setVisibleCondition($this->normalizeConditionData($data['visibleCondition']));
        }

        foreach ($data['properties'] as $name => $property) {
            $section->addChild($this->createProperty($name, $property));
        }

        return $section;
    }

    private function createBlock($propertyName, $data): BlockMetadata
    {
        $blockProperty = new BlockMetadata();
        $blockProperty->setName($propertyName);

        if (isset($data['disabledCondition'])) {
            $blockProperty->setDisabledCondition($this->normalizeConditionData($data['disabledCondition']));
        }

        if (isset($data['visibleCondition'])) {
            $blockProperty->setVisibleCondition($this->normalizeConditionData($data['visibleCondition']));
        }

        if (isset($data['meta']['title'])) {
            $blockProperty->setTitles($data['meta']['title']);
        }

        if (isset($data['meta']['info_text'])) {
            $blockProperty->setDescriptions($data['meta']['info_text']);
        }

        $this->mapProperty($blockProperty, $data);

        return $blockProperty;
    }

    private function mapProperty(PropertyMetadata $property, $data): void
    {
        $data = $this->normalizePropertyData($data);

        $property->defaultComponentName = $data['default-type'];
        $property->setType($data['type']);
        $property->setLocalized($data['multilingual']);
        $property->setRequired($data['mandatory']);
        if (isset($data['colspan'])) {
            $property->setColSpan($data['colspan']);
        }
        $property->setSpaceAfter($data['spaceAfter']);
        $property->setCssClass($data['cssClass']);
        $property->setTags($data['tags']);
        $property->setMinOccurs(null !== $data['minOccurs'] ? \intval($data['minOccurs']) : null);
        $property->setMaxOccurs(null !== $data['maxOccurs'] ? \intval($data['maxOccurs']) : null);
        $property->setDisabledCondition($this->normalizeConditionData($data['disabledCondition'] ?? null));
        $property->setVisibleCondition($this->normalizeConditionData($data['visibleCondition'] ?? null));
        $property->setParameters($data['params']);
        $property->setOnInvalid(\array_key_exists('onInvalid', $data) ? $data['onInvalid'] : null);
        $this->mapMeta($property, $data['meta']);

        $types = $data['types'];
        foreach ($types as $name => $type) {
            $component = new ComponentMetadata();
            $component->setName($name);

            if (isset($type['meta']['title'])) {
                $component->setTitles($type['meta']['title']);
            }

            if (isset($data['meta']['info_text'])) {
                $component->setDescriptions($data['meta']['info_text']);
            }

            if (!$type['ref']) {
                foreach ($this->mapProperties($type['properties']) as $childProperty) {
                    $component->addChild($childProperty);
                }
            } else {
                $component->addTag([
                    'name' => 'sulu.global_block',
                    'attributes' => [
                        'global_block' => $name,
                    ],
                ]);
            }

            $property->addComponent($component);
        }
    }

    private function normalizePropertyData($data): array
    {
        $data = \array_replace_recursive(
            [
                'type' => null,
                'multilingual' => true,
                'mandatory' => true,
                'colspan' => null,
                'cssClass' => null,
                'minOccurs' => null,
                'maxOccurs' => null,
                'spaceAfter' => null,
            ],
            $this->normalizeItem($data)
        );

        return $data;
    }

    private function normalizeConditionData($data): ?string
    {
        if (\is_bool($data)) {
            return $data ? 'true' : 'false';
        }

        return $data;
    }

    private function normalizeItem($data): array
    {
        $data = \array_merge_recursive(
            [
                'meta' => [
                    'title' => [],
                    'info_text' => [],
                    'placeholders' => [],
                ],
                'params' => [],
                'tags' => [],
            ],
            $data
        );

        return $data;
    }

    private function mapMeta(PropertyMetadata $item, $meta): void
    {
        $item->setTitles($meta['title']);
        $item->setDescriptions($meta['info_text']);

        if ($item->getPlaceholders()) {
            $item->setPlaceholders($meta['info_text']);
        }
    }
}
