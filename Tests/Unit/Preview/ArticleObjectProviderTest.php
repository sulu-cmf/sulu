<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Preview;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Preview\ArticleObjectProvider;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class ArticleObjectProviderTest extends TestCase
{
    /**
     * @var DocumentManagerInterface|ObjectProphecy
     */
    private $documentManager;

    /**
     * @var SerializerInterface|ObjectProphecy
     */
    private $serializer;

    /**
     * @var StructureMetadataFactoryInterface|ObjectProphecy
     */
    private $structureMetadataFactory;

    /**
     * @var ArticleObjectProvider
     */
    private $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);

        $this->provider = new ArticleObjectProvider(
            $this->documentManager->reveal(),
            $this->serializer->reveal(),
            ArticleDocument::class,
            $this->structureMetadataFactory->reveal()
        );
    }

    public function testGetObject($id = '123-123-123', $locale = 'de')
    {
        $this->documentManager->find($id, $locale, Argument::any())
            ->willReturn($this->prophesize(ArticleDocument::class)->reveal())->shouldBeCalledTimes(1);

        $this->assertInstanceOf(ArticleDocument::class, $this->provider->getObject($id, $locale));
    }

    public function testGetId($id = '123-123-123')
    {
        $object = $this->prophesize(ArticleDocument::class);
        $object->getUuid()->willReturn($id);

        $this->assertEquals($id, $this->provider->getId($object->reveal()));
    }

    public function testSetValues($locale = 'de', $data = ['title' => 'SULU'])
    {
        $structure = new Structure();
        $object = $this->prophesize(ArticleDocument::class);
        $object->getStructure()->willReturn($structure);

        $this->provider->setValues($object->reveal(), $locale, $data);

        $this->assertEquals('SULU', $structure->getProperty('title')->getValue());
    }

    public function testSetExtensionValues(string $locale = 'de', array $data = ['title' => 'SULU', 'ext' => ['key' => 'value']]): void
    {
        $structure = new Structure();
        $object = $this->prophesize(ArticleDocument::class);
        $object->setExtensionsData(
            Argument::that(
                function($extensionContainer) {
                    $this->assertEquals(['key' => 'value'], $extensionContainer->toArray());

                    return true;
                }
            )
        )->shouldBeCalled();
        $object->getStructure()->willReturn($structure);

        $this->provider->setValues($object->reveal(), $locale, $data);

        $this->assertEquals('SULU', $structure->getProperty('title')->getValue());
    }

    public function testSetContext($locale = 'de', $context = ['template' => 'test-template'])
    {
        $object = $this->prophesize(ArticleDocument::class);
        $object->setStructureType('test-template')->shouldBeCalled();

        $this->assertEquals($object->reveal(), $this->provider->setContext($object->reveal(), $locale, $context));
    }

    public function testSerialize()
    {
        $object = $this->prophesize(ArticleDocument::class);
        $object->getPageNumber()->willReturn(1);

        $this->serializer->serialize(
            $object->reveal(),
            'json',
            Argument::that(
                function(SerializationContext $context) {
                    return $context->shouldSerializeNull()
                           && $context->getAttribute('groups') === ['preview'];
                }
            )
        )->shouldBeCalled()->willReturn('{"title": "test"}');

        $this->assertEquals(
            '{"pageNumber":1,"object":"{\"title\": \"test\"}"}',
            $this->provider->serialize($object->reveal())
        );
    }

    public function testSerializePage()
    {
        $article = $this->prophesize(ArticleDocument::class);
        $article->getPageNumber()->willReturn(1);
        $article->getTitle()->willReturn('page 1');

        $object = $this->prophesize(ArticlePageDocument::class);
        $object->getPageNumber()->willReturn(2);
        $object->getTitle()->willReturn('page 2');
        $object->getParent()->willReturn($article->reveal());

        $this->serializer->serialize(
            $article->reveal(),
            'json',
            Argument::that(
                function(SerializationContext $context) {
                    return $context->shouldSerializeNull()
                           && $context->getAttribute('groups') === ['preview'];
                }
            )
        )->shouldBeCalled()->willReturn('{"title": "test"}');

        $this->assertEquals(
            '{"pageNumber":2,"object":"{\"title\": \"test\"}"}',
            $this->provider->serialize($object->reveal())
        );
    }

    public function testDeserialize()
    {
        $object = $this->prophesize(ArticleDocument::class);

        $this->serializer->deserialize(
            '{"title": "test"}',
            ArticleDocument::class,
            'json',
            Argument::that(
                function(DeserializationContext $context) {
                    return $context->getAttribute('groups') === ['preview'];
                }
            )
        )->shouldBeCalled()->willReturn($object->reveal());

        $object->getChildren()->willReturn([]);

        $this->assertEquals(
            $object->reveal(),
            $this->provider->deserialize(
                '{"pageNumber":1,"object":"{\"title\": \"test\"}"}',
                \get_class($object->reveal())
            )
        );
    }

    public function testDeserializePage()
    {
        $article = $this->prophesize(ArticleDocument::class);

        $object = $this->prophesize(ArticlePageDocument::class);
        $page2 = $this->prophesize(ArticlePageDocument::class);

        $this->serializer->deserialize(
            '{"title": "test"}',
            ArticleDocument::class,
            'json',
            Argument::that(
                function(DeserializationContext $context) {
                    return $context->getAttribute('groups') === ['preview'];
                }
            )
        )->shouldBeCalled()->willReturn($article->reveal());

        $article->getChildren()->willReturn(['page-1' => $object->reveal(), 'page-2' => $page2->reveal()]);
        $object->setParent($article->reveal())->shouldBeCalled();
        $page2->setParent($article->reveal())->shouldBeCalled();

        $this->assertEquals(
            $object->reveal(),
            $this->provider->deserialize(
                '{"pageNumber":2,"object":"{\"title\": \"test\"}"}',
                \get_class($object->reveal())
            )
        );
    }

    public function testGetSecurityContext(): void
    {
        $metadata = $this->prophesize(StructureMetadata::class);
        $metadata->hasTag(ArticleAdmin::STRUCTURE_TAG_TYPE)->willReturn(true);
        $metadata->getTag(ArticleAdmin::STRUCTURE_TAG_TYPE)->willReturn([
            'attributes' => ['type' => 'test-type'],
        ]);

        $this->structureMetadataFactory->getStructureMetadata('article', 'default')->willReturn($metadata->reveal());

        $object = $this->prophesize(ArticleDocument::class);
        $object->getStructureType()->willReturn('default');
        $this->documentManager->find('123-123-213', 'en', Argument::any())
            ->willReturn($object->reveal());

        $this->assertSame(
            ArticleAdmin::getArticleSecurityContext('test-type'),
            $this->provider->getSecurityContext('123-123-213', 'en')
        );
    }

    public function testGetSecurityContextNoMetadata(): void
    {
        $this->structureMetadataFactory->getStructureMetadata('article', 'default')->willReturn(null);

        $object = $this->prophesize(ArticleDocument::class);
        $object->getStructureType()->willReturn('default');
        $this->documentManager->find('123-123-213', 'en', Argument::any())
            ->willReturn($object->reveal());

        $this->assertSame(
            ArticleAdmin::SECURITY_CONTEXT,
            $this->provider->getSecurityContext('123-123-213', 'en')
        );
    }
}
