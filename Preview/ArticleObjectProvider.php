<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Preview;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Integrates article items into preview-system.
 */
class ArticleObjectProvider implements PreviewObjectProviderInterface
{
    use StructureTagTrait;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $articleDocumentClass;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    public function __construct(
        DocumentManagerInterface $documentManager,
        SerializerInterface $serializer,
        string $articleDocumentClass,
        StructureMetadataFactoryInterface $structureMetadataFactory
    ) {
        $this->documentManager = $documentManager;
        $this->serializer = $serializer;
        $this->articleDocumentClass = $articleDocumentClass;
        $this->structureMetadataFactory = $structureMetadataFactory;
    }

    public function getObject($id, $locale)
    {
        return $this->documentManager->find(
            $id,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => true,
            ]
        );
    }

    /**
     * @param ArticleDocument $object
     */
    public function getId($object)
    {
        return $object->getUuid();
    }

    /**
     * @param ArticleDocument $object
     */
    public function setValues($object, $locale, array $data)
    {
        $propertyAccess = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();

        $structure = $object->getStructure();
        foreach ($data as $property => $value) {
            try {
                if ('ext' === $property) {
                    $object->setExtensionsData(new ExtensionContainer($value));
                    continue;
                }

                $propertyAccess->setValue($structure, $property, $value);
            } catch (\InvalidArgumentException $e) {
                // @ignoreException
                //ignore not existing properties
            }
        }
    }

    /**
     * @param ArticleDocument $object
     */
    public function setContext($object, $locale, array $context)
    {
        if (\array_key_exists('template', $context)) {
            $object->setStructureType($context['template']);
        }

        return $object;
    }

    /**
     * @param ArticleDocument $object
     */
    public function serialize($object)
    {
        $pageNumber = $object->getPageNumber();
        if ($object instanceof ArticlePageDocument) {
            // resolve proxy to ensure that this will also be serialized
            $object = $object->getParent();

            $object->getTitle();
        }

        $result = $this->serializer->serialize(
            $object,
            'json',
            SerializationContext::create()
                ->setSerializeNull(true)
                ->setGroups(['preview'])
        );

        return \json_encode(['pageNumber' => $pageNumber, 'object' => $result]);
    }

    /**
     * @param ArticleDocument $serializedObject
     */
    public function deserialize($serializedObject, $objectClass)
    {
        $result = \json_decode($serializedObject, true);

        $article = $this->serializer->deserialize(
            $result['object'],
            $this->articleDocumentClass,
            'json',
            DeserializationContext::create()
                ->setGroups(['preview'])
        );

        foreach ($article->getChildren() as $child) {
            $child->setParent($article);
        }

        if (1 === $result['pageNumber']) {
            return $article;
        }

        $children = \array_values($article->getChildren());

        $object = $children[$result['pageNumber'] - 2];

        return $object;
    }

    public function getSecurityContext($id, $locale): ?string
    {
        /** @var ArticleDocument $object */
        $object = $this->getObject($id, $locale);
        $articleType = $this->getArticleType($object);
        if (!$articleType) {
            return ArticleAdmin::SECURITY_CONTEXT;
        }

        return ArticleAdmin::getArticleSecurityContext($articleType);
    }

    private function getArticleType(ArticleDocument $articleDocument): ?string
    {
        $structureMetadata = $this->structureMetadataFactory->getStructureMetadata(
            'article',
            $articleDocument->getStructureType()
        );

        if (!$structureMetadata) {
            return null;
        }

        return $this->getType($structureMetadata);
    }
}
