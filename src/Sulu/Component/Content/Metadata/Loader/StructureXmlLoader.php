<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Loader;

use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Metadata\Loader\Exception\InvalidXmlException;
use Sulu\Component\Content\Metadata\Loader\Exception\RequiredPropertyNameNotFoundException;
use Sulu\Component\Content\Metadata\Loader\Exception\RequiredTagNotFoundException;
use Sulu\Component\Content\Metadata\Loader\Exception\ReservedPropertyNameException;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Metadata\XmlParserTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Reads a template xml and returns a StructureMetadata.
 */
class StructureXmlLoader extends AbstractLoader
{
    use XmlParserTrait;

    public const SCHEME_PATH = '/schema/template-1.0.xsd';

    public const SCHEMA_NAMESPACE_URI = 'http://schemas.sulu.io/template/template';

    /**
     * reserved names for sulu internals
     * TODO should be possible to inject from config.
     *
     * @var array
     */
    private $reservedPropertyNames = [
        'template',
        'changer',
        'changed',
        'creator',
        'created',
        'published',
        'state',
        'internal',
        'nodeType',
        'navContexts',
        'shadow-on',
        'shadow-base',
        'lastModified',
        'author',
        'authored',
        'type',
        'id',
        'webspace',
    ];

    /**
     * @var array<int, string>
     */
    private $locales;

    /**
     * @param array<string, string> $locales
     */
    public function __construct(
        private CacheLifetimeResolverInterface $cacheLifetimeResolver,
        private PropertiesXmlParser $propertiesXmlParser,
        private SchemaXmlParser $schemaXmlParser,
        private ContentTypeManagerInterface $contentTypeManager,
        private array $requiredPropertyNames,
        private array $requiredTagNames,
        array $locales = [],
        private ?TranslatorInterface $translator = null
    ) {
        $this->locales = \array_keys($locales);

        parent::__construct(
            self::SCHEME_PATH,
            self::SCHEMA_NAMESPACE_URI
        );
    }

    /**
     * @param string $resource
     */
    public function load($resource, $type = null): StructureMetadata
    {
        if (null === $type) {
            $type = 'page';
        }

        $data = parent::load($resource, $type);

        $data = $this->normalizeStructureData($data);

        $structure = new StructureMetadata();
        $structure->setResource($resource);
        $structure->setName($data['key']);
        $structure->setCacheLifetime($data['cacheLifetime']);
        $structure->setController($data['controller']);
        $structure->setInternal($data['internal']);
        $structure->setCacheLifetime($data['cacheLifetime']);
        $structure->setAreas($data['areas']);
        $structure->setView($data['view']);
        $structure->setTags($data['tags']);
        $structure->setParameters($data['params']);

        if (isset($data['schema'])) {
            $structure->setSchema($data['schema']);
        }

        foreach ($data['properties'] as $property) {
            $structure->addChild($property);
        }
        $structure->burnProperties();

        $this->mapMeta($structure, $data['meta']);

        return $structure;
    }

    protected function parse($resource, \DOMXPath $xpath, $type)
    {
        // init running vars
        $tags = [];

        // init result
        $result = $this->loadTemplateAttributes($resource, $xpath, $type);

        // load properties
        $propertiesNode = $xpath->query('/x:template/x:properties')->item(0);
        $result['properties'] = $this->propertiesXmlParser->load(
            $tags,
            $xpath,
            $propertiesNode,
            $type
        );

        $schemaNode = $xpath->query('/x:template/x:schema')->item(0);
        if ($schemaNode) {
            $result['schema'] = $this->schemaXmlParser->load($xpath, $schemaNode);
        }

        $missingProperty = $this->findMissingRequiredProperties($type, $result['properties']);
        if ($missingProperty) {
            throw new RequiredPropertyNameNotFoundException($result['key'], $missingProperty);
        }

        $reservedProperty = $this->findReservedProperties($result['properties']);
        if ($reservedProperty) {
            throw new ReservedPropertyNameException($result['key'], $reservedProperty);
        }

        $result['properties'] = \array_filter($result['properties'], function($property) {
            if (!$property instanceof PropertyMetadata) {
                return true;
            }

            $propertyType = $property->getType();
            if ($this->contentTypeManager->has($propertyType)) {
                return true;
            }

            if ('ignore' === $property->getOnInvalid()) {
                return false;
            }

            throw new \InvalidArgumentException(\sprintf(
                'Content type with alias "%s" has not been registered. Known content types are: "%s"',
                $propertyType,
                \implode('", "', $this->contentTypeManager->getAll())
            ));
        });

        // FIXME until excerpt-template is no page template anymore
        // - https://github.com/sulu-io/sulu/issues/1220#issuecomment-110704259
        if (!\array_key_exists('internal', $result) || !$result['internal']) {
            if (isset($this->requiredTagNames[$type])) {
                foreach ($this->requiredTagNames[$type] as $requiredTagName) {
                    if (!\array_key_exists($requiredTagName, $tags)) {
                        throw new RequiredTagNotFoundException($result['key'], $requiredTagName);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Load template attributes.
     */
    protected function loadTemplateAttributes($resource, \DOMXPath $xpath, $type)
    {
        if ('page' === $type || 'home' === $type) {
            $result = [
                'key' => $this->getValueFromXPath('/x:template/x:key', $xpath),
                'view' => $this->getValueFromXPath('/x:template/x:view', $xpath),
                'controller' => $this->getValueFromXPath('/x:template/x:controller', $xpath),
                'internal' => $this->getValueFromXPath('/x:template/x:internal', $xpath),
                'cacheLifetime' => $this->loadCacheLifetime('/x:template/x:cacheLifetime', $xpath),
                'tags' => $this->loadStructureTags('/x:template/x:tag', $xpath),
                'areas' => $this->loadStructureAreas('/x:template/x:areas/x:area', $xpath),
                'meta' => $this->loadMeta('/x:template/x:meta/x:*', $xpath),
            ];

            $result = \array_filter(
                $result,
                function($value) {
                    return null !== $value;
                }
            );

            foreach (['key', 'view', 'controller', 'cacheLifetime'] as $requiredProperty) {
                if (!isset($result[$requiredProperty])) {
                    throw new InvalidXmlException(
                        $type,
                        \sprintf(
                            'Property "%s" is required in XML template file "%s"',
                            $requiredProperty,
                            $resource
                        )
                    );
                }
            }
        } else {
            $result = [
                'key' => $this->getValueFromXPath('/x:template/x:key', $xpath),
                'view' => $this->getValueFromXPath('/x:template/x:view', $xpath),
                'controller' => $this->getValueFromXPath('/x:template/x:controller', $xpath),
                'cacheLifetime' => $this->loadCacheLifetime('/x:template/x:cacheLifetime', $xpath),
                'tags' => $this->loadStructureTags('/x:template/x:tag', $xpath),
                'areas' => $this->loadStructureAreas('/x:template/x:areas/x:area', $xpath),
                'meta' => $this->loadMeta('/x:template/x:meta/x:*', $xpath),
            ];

            $result = \array_filter(
                $result,
                function($value) {
                    return null !== $value;
                }
            );

            if (\count($result) < 1) {
                throw new InvalidXmlException($result['key']);
            }
        }

        return $result;
    }

    /**
     * Load cache lifetime metadata.
     *
     * @param string $path
     *
     * @return array
     */
    private function loadCacheLifetime($path, \DOMXPath $xpath)
    {
        $nodeList = $xpath->query($path);

        if (!$nodeList->length) {
            return [
                'type' => CacheLifetimeResolverInterface::TYPE_SECONDS,
                'value' => 0,
            ];
        }

        // get first node
        $node = $nodeList->item(0);

        $type = $node->getAttribute('type');
        if ('' === $type) {
            $type = CacheLifetimeResolverInterface::TYPE_SECONDS;
        }

        $value = $node->nodeValue;
        if (!$this->cacheLifetimeResolver->supports($type, $value)) {
            throw new \InvalidArgumentException(
                \sprintf('CacheLifetime "%s" with type "%s" not supported.', $value, $type)
            );
        }

        return [
            'type' => $type,
            'value' => $value,
        ];
    }

    private function normalizeStructureData($data)
    {
        $data = \array_replace_recursive(
            [
                'key' => null,
                'view' => null,
                'controller' => null,
                'internal' => false,
                'cacheLifetime' => null,
                'areas' => [],
            ],
            $this->normalizeItem($data)
        );

        return $data;
    }

    private function normalizeItem($data)
    {
        $data = \array_merge_recursive(
            [
                'meta' => [
                    'title' => [],
                    'info_text' => [],
                    'placeholder' => [],
                ],
                'params' => [],
                'tags' => [],
            ],
            $data
        );

        return $data;
    }

    private function mapMeta(StructureMetadata $structure, $meta)
    {
        $structure->setTitles(
            $this->loadMetaValues($meta['title'])
        );
        $structure->setDescriptions(
            $this->loadMetaValues($meta['info_text'])
        );
    }

    /**
     * @param array<string, string> $metaValues
     *
     * @return array<string, string>
     */
    private function loadMetaValues(array $metaValues): array
    {
        if (!$this->translator) {
            return $metaValues;
        }

        $result = [];

        $translationKey = null;

        foreach ($metaValues as $lang => $metaValue) {
            if (!$lang) {
                $translationKey = $metaValue;

                continue;
            }

            $result[$lang] = $metaValue;
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

    private function findMissingRequiredProperties(string $type, array $propertyData): ?string
    {
        if (!\array_key_exists($type, $this->requiredPropertyNames)) {
            return null;
        }

        foreach ($this->requiredPropertyNames[$type] as $requiredPropertyName) {
            if ($this->isRequiredPropertyMissing($type, $propertyData, $requiredPropertyName)) {
                return $requiredPropertyName;
            }
        }

        return null;
    }

    private function isRequiredPropertyMissing(string $type, array $propertyData, string $requiredPropertyName): bool
    {
        foreach ($propertyData as $property) {
            if ($property->getName() === $requiredPropertyName) {
                return false;
            }

            if ($property instanceof SectionMetadata) {
                $isPropertyMissing = $this->findMissingRequiredProperties($type, $property->getChildren());

                if (!$isPropertyMissing) {
                    return false;
                }
            }
        }

        return true;
    }

    private function findReservedProperties(array $propertyData): ?string
    {
        foreach ($this->reservedPropertyNames as $reservedPropertyName) {
            if ($this->isReservedProperty($propertyData, $reservedPropertyName)) {
                return $reservedPropertyName;
            }
        }

        return null;
    }

    private function isReservedProperty(array $propertyData, string $reservedPropertyName): bool
    {
        foreach ($propertyData as $property) {
            if ($property->getName() === $reservedPropertyName) {
                return true;
            }

            if ($property instanceof SectionMetadata) {
                $isReservedProperty = $this->isReservedProperty(
                    $property->getChildren(),
                    $reservedPropertyName
                );

                if ($isReservedProperty) {
                    return true;
                }
            }
        }

        return false;
    }
}
