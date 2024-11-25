<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Search;

use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\ComplexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\Field\Expression;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\ProviderInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * @template B of DimensionContentInterface
 * @template T of ContentRichEntityInterface<B>
 */
class ContentSearchMetadataProvider implements ProviderInterface
{
    public const SEARCH_FIELD_TAG = 'sulu.search.field';
    public const FIELD_TEMPLATE_KEY = '_template_key';
    public const EXCERPT_FIELDS = [
        'excerptTitle',
        'excerptMore',
        'excerptDescription',
    ];
    public const SEO_FIELDS = [
        'seoTitle',
        'seoDescription',
        'seoKeywords',
        'seoCanonicalUrl',
    ];

    /**
     * @var ContentMetadataInspectorInterface
     */
    private $contentMetadataInspector;

    /**
     * @var Factory
     */
    private $searchMetadataFactory;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureFactory;

    /**
     * @var class-string<T>
     */
    private $contentRichEntityClass;

    /**
     * @var class-string<B>|null
     */
    private $dimensionContentClass;

    /**
     * @param class-string<T> $contentRichEntityClass
     */
    public function __construct(
        ContentMetadataInspectorInterface $contentMetadataInspector,
        Factory $searchMetadataFactory,
        StructureMetadataFactoryInterface $structureFactory,
        string $contentRichEntityClass
    ) {
        $this->contentMetadataInspector = $contentMetadataInspector;
        $this->searchMetadataFactory = $searchMetadataFactory;
        $this->structureFactory = $structureFactory;
        $this->contentRichEntityClass = $contentRichEntityClass;
    }

    public function getMetadataForObject($object): ?ClassMetadata
    {
        $dimensionContentClass = $this->getDimensionContentClass();

        if (!\is_a($object, $dimensionContentClass, true)
            || !$object instanceof DimensionContentInterface
            || !$object instanceof TemplateInterface) {
            return null;
        }

        if (!$object->isMerged()) {
            return null;
        }

        $structureMetadata = $this->structureFactory->getStructureMetadata(
            $object->getTemplateType(),
            $object->getTemplateKey()
        );

        if (!$structureMetadata) {
            // TODO FIXME add test case for this
            return null; // @codeCoverageIgnore
        }

        return $this->getMetadata($dimensionContentClass, $structureMetadata);
    }

    public function getAllMetadata(): array
    {
        /** @var class-string<TemplateInterface> $dimensionContentClass */
        $dimensionContentClass = $this->getDimensionContentClass();

        if (!\is_a($dimensionContentClass, TemplateInterface::class, true)) {
            // TODO FIXME add test case for this
            return [];  // @codeCoverageIgnore
        }

        $metadata = [];
        foreach ($this->structureFactory->getStructures($dimensionContentClass::getTemplateType()) as $structure) {
            $metadata[] = $this->getMetadata($dimensionContentClass, $structure);
        }

        return $metadata;
    }

    public function getMetadataForDocument(Document $document): ?ClassMetadata
    {
        /** @var class-string<TemplateInterface> $dimensionContentClass */
        $dimensionContentClass = $this->getDimensionContentClass();

        if (!\is_a($dimensionContentClass, TemplateInterface::class, true)) {
            // TODO FIXME add test case for this
            return null;  // @codeCoverageIgnore
        }

        if (!$document->hasField(self::FIELD_TEMPLATE_KEY) || $dimensionContentClass !== $document->getClass()) {
            // TODO FIXME add test case for this
            return null;  // @codeCoverageIgnore
        }

        $structureType = $document->getField(self::FIELD_TEMPLATE_KEY)->getValue();

        $structureMetadata = $this->structureFactory->getStructureMetadata(
            $dimensionContentClass::getTemplateType(),
            $structureType
        );

        if (!$structureMetadata) {
            // TODO FIXME add test case for this
            return null;  // @codeCoverageIgnore
        }

        return $this->getMetadata($dimensionContentClass, $structureMetadata);
    }

    private function getMetadata(string $className, StructureMetadata $structureMetadata): ClassMetadata
    {
        $classMetadata = $this->searchMetadataFactory->createClassMetadata($className);

        $indexMeta = $this->searchMetadataFactory->createIndexMetadata();
        $indexMeta->setIdField($this->searchMetadataFactory->createMetadataField('resourceId'));
        $indexMeta->setLocaleField($this->searchMetadataFactory->createMetadataField('locale'));

        /** @var class-string<B> $dimensionContentClass */
        $dimensionContentClass = $this->getDimensionContentClass();

        if (!\is_a($dimensionContentClass, DimensionContentInterface::class, true)) {
            // TODO FIXME add test case for this
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(
                \sprintf('$dimensionContentClass needs to be of type "%s"', DimensionContentInterface::class)
            );
            // @codeCoverageIgnoreEnd
        }

        /** @var string $expression */
        $expression = $this->createIndexNameField($dimensionContentClass::getResourceKey());
        $indexMeta->setIndexName($expression);

        $indexMeta->addFieldMapping(
            self::FIELD_TEMPLATE_KEY,
            [
                'type' => 'string',
                'stored' => true,
                'indexed' => true,
                'field' => $this->searchMetadataFactory->createMetadataProperty('templateKey'),
            ]
        );

        if (\is_a($className, ExcerptInterface::class, true)) {
            foreach (self::EXCERPT_FIELDS as $property) {
                $indexMeta->addFieldMapping(
                    $property,
                    [
                        'field' => $this->searchMetadataFactory->createMetadataProperty($property),
                        'type' => 'string',
                        'aggregate' => true,
                        'indexed' => false,
                    ]
                );
            }
        }

        if (\is_a($className, SeoInterface::class, true)) {
            foreach (self::SEO_FIELDS as $property) {
                $indexMeta->addFieldMapping(
                    $property,
                    [
                        'field' => $this->searchMetadataFactory->createMetadataProperty($property),
                        'type' => 'string',
                        'aggregate' => true,
                        'indexed' => false,
                    ]
                );
            }
        }

        foreach ($structureMetadata->getProperties() as $property) {
            if ($property instanceof BlockMetadata) {
                $propertyMapping = new ComplexMetadata();
                foreach ($property->getComponents() as $component) {
                    foreach ($component->getChildren() as $componentProperty) {
                        if (false === $componentProperty->hasTag(self::SEARCH_FIELD_TAG)) {
                            // TODO FIXME add test case for this
                            continue; // @codeCoverageIgnore
                        }

                        $tag = $componentProperty->getTag(self::SEARCH_FIELD_TAG);
                        $tagAttributes = $tag['attributes'];

                        if (!isset($tagAttributes['index']) || 'false' !== $tagAttributes['index']) {
                            $propertyMapping->addFieldMapping(
                                $property->getName() . '.' . $componentProperty->getName(),
                                [
                                    'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                                    'field' => $this->searchMetadataFactory->createMetadataProperty(
                                        '[' . $componentProperty->getName() . ']'
                                    ),
                                    'aggregate' => true,
                                    'indexed' => isset($tagAttributes['index']) && 'indexed' === $tagAttributes['index'],
                                ]
                            );
                        }
                    }
                }

                $indexMeta->addFieldMapping(
                    $property->getName(),
                    [
                        'type' => 'complex',
                        'mapping' => $propertyMapping,
                        'field' => $this->getContentField($property),
                    ]
                );
            } else {
                $this->mapProperty($property, $indexMeta);
            }
        }

        $classMetadata->addIndexMetadata('_default', $indexMeta);

        return $classMetadata;
    }

    private function createIndexNameField(string $indexName): Expression
    {
        $expression = '"' . $indexName . '"';
        $expression .= '~(object.getStage() == "' . DimensionContentInterface::STAGE_LIVE . '" ? "_published" : "")';

        return new Expression($expression);
    }

    private function getContentField(PropertyMetadata $property): Expression
    {
        return $this->searchMetadataFactory->createMetadataExpression(
            \sprintf(
                'object.getTemplateData()["%s"]',
                $property->getName()
            )
        );
    }

    private function mapProperty(PropertyMetadata $property, IndexMetadata $metadata): void
    {
        if (!$property->hasTag(self::SEARCH_FIELD_TAG)) {
            return;
        }

        $tag = $property->getTag(self::SEARCH_FIELD_TAG);
        $tagAttributes = $tag['attributes'];

        if (isset($tagAttributes['role'])) {
            switch ($tagAttributes['role']) {
                case 'title':
                    $metadata->setTitleField($this->getContentField($property));
                    $metadata->addFieldMapping(
                        $property->getName(),
                        [
                            'field' => $this->getContentField($property),
                            'type' => 'string',
                            'aggregate' => true,
                            'indexed' => false,
                        ]
                    );
                    break;
                case 'description':
                    $metadata->setDescriptionField($this->getContentField($property));
                    $metadata->addFieldMapping(
                        $property->getName(),
                        [
                            'field' => $this->getContentField($property),
                            'type' => 'string',
                            'aggregate' => true,
                            'indexed' => false,
                        ]
                    );
                    break;
                case 'url':
                    $metadata->setUrlField($this->getContentField($property));
                    $metadata->addFieldMapping(
                        $property->getName(),
                        [
                            'field' => $this->getContentField($property),
                            'type' => 'string',
                            'aggregate' => true,
                            'indexed' => false,
                        ]
                    );
                    break;
                case 'image':
                    // TODO FIXME add test case for this
                    // @codeCoverageIgnoreStart
                    $metadata->setImageUrlField($this->getContentField($property));
                    break;
                    // @codeCoverageIgnoreEnd
                default:
                    // TODO FIXME add test case for this
                    // @codeCoverageIgnoreStart
                    throw new \InvalidArgumentException(
                        \sprintf(
                            'Unknown search field role "%s", role must be one of "%s"',
                            $tagAttributes['role'],
                            \implode(', ', ['title', 'description', 'image'])
                        )
                    );
                    // @codeCoverageIgnoreEnd
            }

            return;
        }

        if (!isset($tagAttributes['index']) || 'false' !== $tagAttributes['index']) {
            $metadata->addFieldMapping(
                $property->getName(),
                [
                    'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                    'field' => $this->getContentField($property),
                    'aggregate' => true,
                    'indexed' => isset($tagAttributes['index']) && 'indexed' === $tagAttributes['index'],
                ]
            );
        }
    }

    /**
     * @return class-string<B>
     */
    private function getDimensionContentClass(): string
    {
        if (null !== $this->dimensionContentClass) {
            return $this->dimensionContentClass;
        }

        $this->dimensionContentClass = $this->contentMetadataInspector->getDimensionContentClass($this->contentRichEntityClass);

        return $this->dimensionContentClass;
    }
}
