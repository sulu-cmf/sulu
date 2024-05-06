<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Search;

use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\Content\Document\WorkflowStage;

class SaveDocumentTest extends BaseTestCase
{
    /**
     * Check that the automatic indexing works.
     */
    public function testSaveDocument(): void
    {
        $this->indexDocument('About Us', '/about-us');

        $searchManager = $this->getSearchManager();
        $res = $searchManager->createSearch('About')->locale('de')->index('page_sulu_io')->execute();
        $this->assertCount(1, $res);
        $hit = $res[0];
        $document = $hit->getDocument();

        $this->assertEquals('About Us', $document->getTitle());
        $this->assertEquals('/about-us', $document->getUrl());
        $this->assertEquals(null, $document->getDescription());
    }

    public function testSaveDocumentWithBlocks(): void
    {
        $document = new PageDocument();
        $document->setTitle('Places');
        $document->setStructureType('blocks');
        $document->setResourceSegment('/places');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->getStructure()->bind([
            'block' => [
                [
                    'type' => 'article',
                    'title' => 'Dornbirn',
                    'article' => 'Dornbirn Austrua',
                    'settings' => [],
                ],
                [
                    'type' => 'article',
                    'title' => 'Basel',
                    'article' => 'Basel Switzerland',
                    'lines' => ['line1', 'line2'],
                ],
            ],
        ], false);
        $document->setParent($this->homeDocument);

        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $searchManager = $this->getSearchManager();

        $searches = [
            'Places' => 1,
            'Basel' => 1,
            'Dornbirn' => 1,
        ];

        foreach ($searches as $search => $count) {
            $res = $searchManager->createSearch($search)->locale('de')->index('page_sulu_io')->execute();
            $this->assertCount($count, $res, 'Searching for: ' . $search);
        }
    }

    public function testSaveDocumentWithScheduledBlock(): void
    {
        $document = new PageDocument();
        $document->setTitle('Places');
        $document->setStructureType('blocks');
        $document->setResourceSegment('/places');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->getStructure()->bind([
            'block' => [
                [
                    'type' => 'article',
                    'title' => 'Dornbirn',
                    'article' => 'Dornbirn Austrua',
                    'settings' => [
                        'schedules_enabled' => false,
                    ],
                ],
                [
                    'type' => 'article',
                    'title' => 'Basel',
                    'article' => 'Basel Switzerland',
                    'lines' => ['line1', 'line2'],
                    'settings' => [
                        'schedules_enabled' => true,
                    ],
                ],
            ],
        ], false);
        $document->setParent($this->homeDocument);

        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $searchManager = $this->getSearchManager();

        $searches = [
            'Places' => 1,
            'Basel' => 0,
            'Dornbirn' => 1,
        ];

        foreach ($searches as $search => $count) {
            $res = $searchManager->createSearch($search)->locale('de')->index('page_sulu_io')->execute();
            $this->assertCount($count, $res, 'Searching for: ' . $search);
        }
    }

    public function testSaveDocumentWithHiddenBlock(): void
    {
        $document = new PageDocument();
        $document->setTitle('Places');
        $document->setStructureType('blocks');
        $document->setResourceSegment('/places');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->getStructure()->bind([
            'block' => [
                [
                    'type' => 'article',
                    'title' => 'Dornbirn',
                    'article' => 'Dornbirn Austria',
                    'settings' => [
                        'hidden' => false,
                    ],
                ],
                [
                    'type' => 'article',
                    'title' => 'Basel',
                    'article' => 'Basel Switzerland',
                    'lines' => ['line1', 'line2'],
                    'settings' => [
                        'hidden' => true,
                    ],
                ],
            ],
        ], false);
        $document->setParent($this->homeDocument);

        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $searchManager = $this->getSearchManager();

        $searches = [
            'Places' => 1,
            'Basel' => 0,
            'Dornbirn' => 1,
        ];

        foreach ($searches as $search => $count) {
            $res = $searchManager->createSearch($search)->locale('de')->index('page_sulu_io')->execute();
            $this->assertCount($count, $res, 'Searching for: ' . $search);
        }
    }
}
