<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\SearchIntegration;

use Massive\Bundle\SearchBundle\Search\Document;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Api\Media as ApiMedia;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\NodeManager;

class SearchIntegrationTest extends SuluTestCase
{
    use ProphecyTrait;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var HomeDocument
     */
    private $webspaceDocument;

    /**
     * @var ApiMedia
     */
    private $media;

    public function setUp(): void
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->nodeManager = $this->getContainer()->get('sulu_document_manager_test.node_manager');
        $this->webspaceDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $mediaEntity = new Media();
        $tagManager = $this->prophesize(TagManagerInterface::class);
        $this->media = new ApiMedia($mediaEntity, 'de', null, $tagManager);
    }

    /**
     * @return array<array{string, class-string<\Throwable>|null}>
     */
    public static function provideIndex(): array
    {
        return [
            ['sulu-100x100', null],
            ['invalid', \InvalidArgumentException::class],
        ];
    }

    /**
     * @param class-string<\Throwable> $expectException
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideIndex')]
    public function testIndex(string $format, ?string $expectException): void
    {
        $mediaSelectionContainer = $this->prophesize(MediaSelectionContainer::class);
        $mediaSelectionContainer->getData()->willReturn([$this->media]);
        $mediaSelectionContainer->toArray()->willReturn(null);

        if (null !== $expectException) {
            $this->expectException($expectException);
        }

        $this->media->setFormats([
            $format => 'myimage.jpg',
        ]);

        $testAdapter = $this->getContainer()->get('massive_search.adapter.test');

        // remove the documents indexed when creating the fixtures
        foreach ($testAdapter->listIndexes() as $indexName) {
            $testAdapter->purge($indexName);
        }

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setTitle('Hallo');
        $document->setResourceSegment('/hallo/fo');
        $document->setStructureType('images');
        $document->setParent($this->webspaceDocument);
        $document->getStructure()->bind([
            'images' => $mediaSelectionContainer->reveal(),
        ], false);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $documents = $testAdapter->getDocuments();
        $this->assertCount(1, $documents);
        $document = \end($documents);
        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('myimage.jpg', $document->getImageUrl());
    }

    public function testIndexNoFormats(): void
    {
        $mediaSelectionContainer = $this->prophesize(MediaSelectionContainer::class);
        $mediaSelectionContainer->getData()->willReturn([$this->media]);
        $mediaSelectionContainer->toArray()->willReturn(null);

        $this->media->setFormats([]);

        $testAdapter = $this->getContainer()->get('massive_search.adapter.test');

        // remove the documents indexed when creating the fixtures
        foreach ($testAdapter->listIndexes() as $indexName) {
            $testAdapter->purge($indexName);
        }

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setTitle('Hallo');
        $document->setResourceSegment('/hallo/fo');
        $document->setStructureType('images');
        $document->setParent($this->webspaceDocument);
        $document->getStructure()->bind([
            'images' => $mediaSelectionContainer->reveal(),
        ], false);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $documents = $testAdapter->getDocuments();
        $this->assertCount(1, $documents);
        $document = \end($documents);
        $this->assertInstanceOf(Document::class, $document);
        $this->assertNull($document->getImageUrl());
    }

    public function testIndexWithArrayIdsEmpty(): void
    {
        $testAdapter = $this->getContainer()->get('massive_search.adapter.test');

        // remove the documents indexed when creating the fixtures
        foreach ($testAdapter->listIndexes() as $indexName) {
            $testAdapter->purge($indexName);
        }

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setTitle('Hallo');
        $document->setResourceSegment('/hallo/fo');
        $document->setStructureType('images');
        $document->setParent($this->webspaceDocument);
        $document->getStructure()->bind([
            'images' => ['ids' => null],
        ], false);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $documents = $testAdapter->getDocuments();
        $this->assertCount(1, $documents);
        $document = \end($documents);
        $this->assertInstanceOf(Document::class, $document);
        $this->assertNull($document->getImageUrl());
    }

    public function testIndexWithArrayIdEmpty(): void
    {
        $testAdapter = $this->getContainer()->get('massive_search.adapter.test');

        // remove the documents indexed when creating the fixtures
        foreach ($testAdapter->listIndexes() as $indexName) {
            $testAdapter->purge($indexName);
        }

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setTitle('Hallo');
        $document->setResourceSegment('/hallo/fo');
        $document->setStructureType('images');
        $document->setParent($this->webspaceDocument);
        $document->getStructure()->bind([
            'images' => ['id' => null],
        ], false);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $documents = $testAdapter->getDocuments();
        $this->assertCount(1, $documents);
        $document = \end($documents);
        $this->assertInstanceOf(Document::class, $document);
        $this->assertNull($document->getImageUrl());
    }

    public function testIndexNoMedia(): void
    {
        $testAdapter = $this->getContainer()->get('massive_search.adapter.test');

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setStructureType('images');
        $document->setTitle('Hallo');
        $document->setResourceSegment('/hallo');
        $document->setParent($this->webspaceDocument);
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $documents = $testAdapter->getDocuments();
        $document = \end($documents);
        $this->assertInstanceOf(Document::class, $document);
        $this->assertNull($document->getImageUrl());
    }
}
