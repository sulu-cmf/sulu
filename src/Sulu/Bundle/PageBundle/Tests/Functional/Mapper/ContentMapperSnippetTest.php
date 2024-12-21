<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Mapper;

use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPCR\SessionInterface;
use PHPCR\Util\UUIDHelper;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class ContentMapperSnippetTest extends SuluTestCase
{
    /**
     * @var ContentMapper
     */
    private $contentMapper;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SnippetDocument
     */
    private $snippet1;

    /**
     * @var SnippetDocument
     */
    private $snippet2;

    /**
     * @var string
     */
    private $snippet1OriginalPath;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var HomeDocument
     */
    private $parent;

    /**
     * @var NodeInterface
     */
    private $snippet1Node;

    public function setUp(): void
    {
        $this->initPhpcr();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->loadFixtures();
        $this->parent = $this->documentManager->find('/cmf/sulu_io/contents');
    }

    public function loadFixtures(): void
    {
        $this->snippet1 = $this->createSnippetDocument();
        $this->snippet1->setStructureType('animal');
        $this->snippet1->setTitle('ElePHPant');
        $this->snippet1->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($this->snippet1, 'en');
        $this->documentManager->flush();

        $this->snippet2 = $this->createSnippetDocument();
        $this->snippet2->setStructureType('animal');
        $this->snippet2->setTitle('Penguin');
        $this->snippet2->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($this->snippet2, 'de');
        $this->documentManager->flush();

        $this->snippet1Node = $this->session->getNodeByIdentifier($this->snippet1->getUuid());
        $this->snippet1OriginalPath = $this->snippet1Node->getPath();

        /** @var SnippetDocument $document */
        $document = $this->documentManager->find($this->snippet1->getUuid(), 'de');
        $document->setStructureType('animal');
        $document->setTitle('English ElePHPant');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        /** @var SnippetDocument $document */
        $document = $this->createSnippetDocument();
        $document->setStructureType('animal');
        $document->setTitle('Some other animal');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en');
        $this->documentManager->flush();
    }

    public function testChangeSnippetTemplate(): void
    {
        /** @var SnippetDocument $document */
        $document = $this->documentManager->find($this->snippet1->getUuid());
        $document->setStructureType('hotel');
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        try {
            $this->session->getNode($this->snippet1OriginalPath);
            $this->assertTrue(false);
        } catch (PathNotFoundException $e) {
            $this->assertTrue(true);
        }

        $node = $this->session->getNode('/cmf/snippets/hotel/elephpant');
        $node->getPropertyValue('template');
    }

    public function testRemoveSnippet(): void
    {
        $this->contentMapper->delete($this->snippet1->getUuid(), 'sulu_io');

        try {
            $this->session->getNode($this->snippet1OriginalPath);
            $this->assertTrue(false, 'Snippet was found FAIL');
        } catch (PathNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function testRemoveSnippetWithReferences(): void
    {
        $document = $this->documentManager->create('page');
        $document->setTitle('Hello');
        $document->getStructure()->bind([
            'animals' => [$this->snippet1->getUuid()],
        ], false);
        $document->setParent($this->parent);
        $document->setStructureType('test_page');
        $document->setResourceSegment('/url/foo');
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $this->contentMapper->delete($this->snippet1->getUuid(), 'sulu_io', true);

        try {
            $this->session->getNode($this->snippet1OriginalPath);
            $this->assertTrue(false, 'Snippet was found FAIL');
        } catch (PathNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public static function provideRemoveSnippetWithReferencesDereference()
    {
        return [
            [true],
            [false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRemoveSnippetWithReferencesDereference')]
    public function testRemoveSnippetWithReferencesDereference($multiple = false): void
    {
        $document = $this->documentManager->create('page');
        $document->setTitle('test');
        $document->setResourceSegment('/url/foo');

        if ($multiple) {
            $document->getStructure()->bind([
                'animals' => [$this->snippet1->getUuid(), $this->snippet2->getUuid()],
            ], false);
        } else {
            $document->getStructure()->bind([
                'animals' => $this->snippet1->getUuid(),
            ], false);
        }

        $document->setParent($this->parent);
        $document->setStructureType('test_page');
        $this->documentManager->persist($document, 'de');

        $this->documentManager->flush();

        $this->contentMapper->delete($this->snippet1->getUuid(), 'sulu_io', true);

        try {
            $this->session->getNode($this->snippet1OriginalPath);
            $this->assertTrue(false, 'Snippet was found FAIL');
        } catch (PathNotFoundException $e) {
            $this->assertTrue(true, 'Snippet was removed');
        }

        $referrer = $this->documentManager->find('/cmf/sulu_io/contents/test', 'de');

        if ($multiple) {
            $contents = $referrer->getStructure()->getProperty('animals')->getValue();
            $this->assertCount(1, $contents);
            $content = \reset($contents);
            $this->assertEquals($this->snippet2->getUuid(), $content);
        } else {
            $contents = $referrer->getStructure()->getProperty('animals')->getValue();
            $this->assertCount(0, $contents);
        }
    }

    public function testLoad(): void
    {
        $node = $this->session->getNode($this->snippet1OriginalPath);
        $snippet = $this->contentMapper->loadByNode(
            $node,
            'de',
            null,
            false,
            true
        );

        $templateKey = $snippet->getKey();
        $this->assertEquals('animal', $templateKey);
    }

    public function testLoadShallowStructureByNode(): void
    {
        $node = $this->session->getNode($this->snippet1OriginalPath);
        $snippet = $this->contentMapper->loadShallowStructureByNode(
            $node,
            'de',
            'sulu_io'
        );

        $this->assertEquals('animal', $snippet->getKey());
        $this->assertTrue(UUIDHelper::isUUID($snippet->getUuid()));
    }

    /**
     * @return SnippetDocument
     */
    private function createSnippetDocument()
    {
        return $this->documentManager->create('snippet');
    }
}
