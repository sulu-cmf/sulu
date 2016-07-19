<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Structure;

use PHPCR\NodeInterface;
use Sulu\Bundle\SearchBundle\Search\Factory;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;

/**
 * extends structure with seo content.
 */
class ExcerptStructureExtension extends AbstractExtension
{
    /**
     * name of structure extension.
     */
    const EXCERPT_EXTENSION_NAME = 'excerpt';

    /**
     * will be filled with data in constructor
     * {@inheritdoc}
     */
    protected $properties = [];

    /**
     * {@inheritdoc}
     */
    protected $name = self::EXCERPT_EXTENSION_NAME;

    /**
     * {@inheritdoc}
     */
    protected $additionalPrefix = self::EXCERPT_EXTENSION_NAME;

    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @var string
     */
    private $languageCode;

    /**
     * @var Factory
     */
    private $factory;

    public function __construct(
        StructureManagerInterface $structureManager,
        ContentTypeManagerInterface $contentTypeManager,
        Factory $factory
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(NodeInterface $node, $data, $webspaceKey, $languageCode)
    {
        $excerptStructure = $this->getExcerptStructure($languageCode);

        foreach ($excerptStructure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if (isset($data[$property->getName()])) {
                $property->setValue($data[$property->getName()]);
                $contentType->write(
                    $node,
                    new TranslatedProperty(
                        $property,
                        $languageCode,
                        $this->languageNamespace,
                        $this->additionalPrefix
                    ),
                    null, // userid
                    $webspaceKey,
                    $languageCode,
                    null // segmentkey
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(NodeInterface $node, $webspaceKey, $languageCode)
    {
        $excerptStructure = $this->getExcerptStructure($languageCode);

        $data = [];
        foreach ($excerptStructure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $contentType->read(
                $node,
                new TranslatedProperty(
                    $property,
                    $languageCode,
                    $this->languageNamespace,
                    $this->additionalPrefix
                ),
                $webspaceKey,
                $languageCode,
                null // segmentkey
            );

            $data[$property->getName()] = $property->getValue();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function setLanguageCode($languageCode, $languageNamespace, $namespace)
    {
        // lazy load excerpt structure to avoid redeclaration of classes
        // should be done before parent::setLanguageCode because it uses the $thi<->properties
        // which will be set in initExcerptStructure
        $this->initProperties($languageCode);

        $this->languageCode = $languageCode;
        $this->languageNamespace = $languageNamespace;

        parent::setLanguageCode($languageCode, $languageNamespace, $namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData($container)
    {
        $container = new ExcerptValueContainer($container);

        $data = [];
        foreach ($this->getExcerptStructure()->getProperties() as $property) {
            if ($container->__isset($property->getName())) {
                $property->setValue($container->__get($property->getName()));
                $contentType = $this->contentTypeManager->get($property->getContentTypeName());
                $data[$property->getName()] = $contentType->getContentData($property);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldMapping()
    {
        $mappings = parent::getFieldMapping();

        foreach ($this->getExcerptStructure()->getPropertiesByTagName('sulu.search.field') as $property) {
            $tag = $property->getTag('sulu.search.field');
            $tagAttributes = $tag->getAttributes();

            $mappings['excerpt' . ucfirst($property->getName())] = [
                'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                'field' => $this->factory->createMetadataExpression(
                    sprintf('object.getExtensionsData()["excerpt"]["%s"]', $property->getName())
                ),
            ];
        }

        return $mappings;
    }

    /**
     * Returns and caches excerpt-structure.
     *
     * @param string $locale
     *
     * @return StructureInterface
     */
    private function getExcerptStructure($locale = null)
    {
        if ($locale === null) {
            $locale = $this->languageCode;
        }

        $excerptStructure = $this->structureManager->getStructure(self::EXCERPT_EXTENSION_NAME);
        $excerptStructure->setLanguageCode($locale);

        return $excerptStructure;
    }

    /**
     * Initiates structure and properties.
     *
     * @param string $locale
     */
    private function initProperties($locale)
    {
        /** @var PropertyInterface $property */
        foreach ($this->getExcerptStructure($locale)->getProperties() as $property) {
            $this->properties[] = $property->getName();
        }
    }
}
