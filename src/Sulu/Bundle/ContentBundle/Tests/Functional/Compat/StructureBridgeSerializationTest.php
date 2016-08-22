<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Compat;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class StructureBridgeSerializationTest extends SuluTestCase
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var PageDocument
     */
    private $contentDocument;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    public function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        $this->contentDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');
        $this->serializer = $this->getContainer()->get('jms_serializer');
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
    }

    public function testSerialize()
    {
        $pageDocument = $this->createPage();
        $managedPage = $this->contentMapper->load($pageDocument->getUuid(), 'sulu_io', 'fr');

        $this->assertInstanceOf(StructureBridge::class, $managedPage);

        return $this->serializer->serialize(
            $managedPage,
            'json',
            SerializationContext::create()->setGroups('preview')
        );
    }

    /**
     * @depends testSerialize
     */
    public function testDeserialize($data)
    {
        $result = $this->serializer->deserialize(
            $data,
            PageBridge::class,
            'json',
            DeserializationContext::create()->setGroups('preview')
        );

        $this->assertInstanceOf(StructureBridge::class, $result);
        $this->assertEquals('internallinks', $result->getKey());
        $this->assertEquals(1, $result->getChanger());
        $this->assertEquals(1, $result->getCreator());
        $this->assertInstanceOf(\DateTime::class, $result->getChanged());
        $this->assertInstanceOf(\DateTime::class, $result->getCreated());

        $property = $result->getProperty('internalLinks');
        $this->assertInstanceOf(Property::class, $property);

        $value = $property->getValue();
        $this->assertInternalType('array', $value);
        $this->assertCount(1, $value);
    }

    private function createPage()
    {
        $page = new PageDocument();
        $page->setTitle('Hello');
        $page->setResourceSegment('/hello');
        $page->setParent($this->contentDocument);
        $page->setStructureType('internallinks');
        $page->getStructure()->bind(
            [
                'title' => 'World',
                'internalLinks' => [
                    $this->contentDocument->getUuid(),
                ],
            ],
            true
        );

        $this->documentManager->persist($page, 'fr', ['user' => 1]);
        $this->documentManager->flush();

        return $page;
    }
}
