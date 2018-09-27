<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Command;

use Sulu\Bundle\ContentBundle\Command\WebspaceCopyCommand;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class WebspaceCopyCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    public function setUp()
    {
        $application = new Application();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->documentRegistry = $this->getContainer()->get('sulu_document_manager_test.document_registry');

        $command = new WebspaceCopyCommand();
        $command->setApplication($application);
        $command->setContainer($this->getContainer());
        $this->tester = new CommandTester($command);

        $this->setupPages();
    }

    public function testRunAborted()
    {
        /** @var HomeDocument $baseDocument */
        $homeDocumentDestination = $this->documentManager->find('/cmf/destination_io/contents');
        $this->assertCount(0, $homeDocumentDestination->getChildren());

        $this->tester->execute(
            [
                'source-webspace' => 'sulu_io',
                'source-locale' => 'de',
                'destination-webspace' => 'destination_io',
                'destination-locale' => 'es,de',
            ]
        );
        $output = $this->tester->getDisplay();
        $this->assertContains('Aborted', $output);

        $this->tester->execute(
            [
                'source-webspace' => 'sulu_io',
                'source-locale' => 'de,de',
                'destination-webspace' => 'destination_io',
                'destination-locale' => 'es,de',
            ]
        );
        $output = $this->tester->getDisplay();
        $this->assertContains('Aborted', $output);
    }

    public function testRun()
    {
        /** @var HomeDocument $baseDocument */
        $homeDocumentDestination = $this->documentManager->find('/cmf/destination_io/contents');
        $this->assertCount(0, $homeDocumentDestination->getChildren());

        $this->tester->execute(
            [
                'source-webspace' => 'sulu_io',
                'source-locale' => 'de,de',
                'destination-webspace' => 'destination_io',
                'destination-locale' => 'es,de',
                '--clear-destination-webspace' => true,
            ]
        );

        $output = $this->tester->getDisplay();
        $this->assertContains('Done', $output);

        $this->documentRegistry->clear();

        /** @var HomeDocument $baseDocument */
        $homeDocumentDestination = $this->documentManager->find('/cmf/destination_io/contents');
        $this->assertCount(4, $homeDocumentDestination->getChildren());

        $this->checkRedirectInternal();
        $this->checkSmartContentReference();
        $this->checkSmartContentReferenceInDifferentWebspace();
        $this->checkInternalLinks();
        $this->checkSingleInternalLink();
        $this->checkTextEditorLinkInBlock();
    }

    protected function checkRedirectInternal()
    {
        /** @var PageDocument $targetDocument */
        $targetDocument = $this->documentManager->find('/cmf/destination_io/contents/node1', 'es');
        /** @var PageDocument $page1_1_3 */
        $page1_1_3 = $this->documentManager->find('/cmf/destination_io/contents/node1/node1-1/node1-1-3', 'es');
        $this->assertEquals($page1_1_3->getRedirectTarget(), $targetDocument);
    }

    protected function checkSmartContentReference()
    {
        /** @var PageDocument $targetDocument */
        $targetDocument = $this->documentManager->find('/cmf/destination_io/contents/node1', 'es');
        /** @var PageDocument $page2 */
        $page2 = $this->documentManager->find('/cmf/destination_io/contents/node2', 'es');
        $this->assertContains($targetDocument->getUuid(), $page2->getStructure()->toArray()['smart_content']);
    }

    protected function checkSmartContentReferenceInDifferentWebspace()
    {
        /** @var HomeDocument $targetDocument */
        $targetDocument = $this->documentManager->find('/cmf/test_io/contents', 'es');
        /** @var PageDocument $page3 */
        $page3 = $this->documentManager->find('/cmf/destination_io/contents/node3', 'es');
        $this->assertContains($targetDocument->getUuid(), $page3->getStructure()->toArray()['smart_content']);
        /** @var PageDocument $page3_sulu */
        $page3_sulu = $this->documentManager->find('/cmf/sulu_io/contents/node3', 'en');
        $this->assertContains($targetDocument->getUuid(), $page3_sulu->getStructure()->toArray()['smart_content']);
    }

    protected function checkInternalLinks()
    {
        /** @var BasePageDocument $targetDocument1 */
        $targetDocument1 = $this->documentManager->find('/cmf/destination_io/contents/node1', 'es');
        /** @var BasePageDocument $targetDocument2 */
        $targetDocument2 = $this->documentManager->find('/cmf/destination_io/contents/node2', 'es');
        /** @var BasePageDocument $targetDocument3 */
        $targetDocument3 = $this->documentManager->find('/cmf/test_io/contents', 'es');
        /** @var PageDocument $page2_1 */
        $page2_1 = $this->documentManager->find('/cmf/destination_io/contents/node2/node2-1', 'es');
        $structure = $page2_1->getStructure()->toArray()['internalLinks'];
        $this->assertContains($targetDocument1->getUuid(), $structure);
        $this->assertContains($targetDocument2->getUuid(), $structure);
        $this->assertContains($targetDocument3->getUuid(), $structure);
    }

    protected function checkSingleInternalLink()
    {
        /** @var HomeDocument $targetDocument */
        $targetDocument = $this->documentManager->find('/cmf/destination_io/contents/node1', 'es');
        /** @var PageDocument $page2 */
        $page2 = $this->documentManager->find('/cmf/destination_io/contents/node2', 'es');
        $this->assertContains($targetDocument->getUuid(), $page2->getStructure()->toArray()['smart_content']);
    }

    protected function checkTextEditorLinkInBlock()
    {
        /** @var PageDocument $targetDocument */
        $targetDocument = $this->documentManager->find('/cmf/destination_io/contents/node1', 'es');
        /** @var PageDocument $page4 */
        $page4 = $this->documentManager->find('/cmf/destination_io/contents/node4', 'es');
        $this->assertContains($targetDocument->getUuid(), $page4->getStructure()->toArray()['article'][0]['text']);
    }

    /**
     * Creates pages.
     */
    protected function setupPages()
    {
        $this->initPhpcr();

        $testIoHomeDocument = $this->documentManager->find('/cmf/test_io/contents', 'de');

        $page1 = $this->documentManager->create('page');
        $page1->setStructureType('default');
        $page1->setTitle('Node1');
        $page1->setResourceSegment('/node1');
        $this->documentManager->persist(
            $page1,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents',
            ]
        );
        $this->documentManager->flush();

        $page1_1 = $this->documentManager->create('page');
        $page1_1->setStructureType('default');
        $page1_1->setTitle('Node1-1');
        $page1_1->getStructure()->bind(['article' => 'This is a perfect description.']);
        $page1_1->setResourceSegment('/node1-1');
        $this->documentManager->persist(
            $page1_1,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node1',
            ]
        );
        $this->documentManager->flush();

        $page1_1_1 = $this->documentManager->create('page');
        $page1_1_1->setStructureType('default');
        $page1_1_1->setTitle('Node1-1-1');
        $page1_1_1->getStructure()->bind(['article' => 'This is a perfect description.']);
        $page1_1_1->setResourceSegment('/node1-1-1');
        $this->documentManager->persist(
            $page1_1_1,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node1/node1-1',
            ]
        );
        $this->documentManager->flush();

        $page1_1_2 = $this->documentManager->create('page');
        $page1_1_2->setStructureType('default');
        $page1_1_2->setTitle('Node1-1-1-2');
        $page1_1_2->getStructure()->bind(['article' => 'This is a perfect description.']);
        $page1_1_2->setResourceSegment('/node1-1-2');
        $this->documentManager->persist(
            $page1_1_2,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node1/node1-1',
            ]
        );
        $this->documentManager->flush();

        /** @var PageDocument $page1_1_3 */
        $page1_1_3 = $this->documentManager->create('page');
        $page1_1_3->setStructureType('default');
        $page1_1_3->setTitle('Node1-1-3');
        $page1_1_3->setResourceSegment('/node1-1-3');
        $page1_1_3->setRedirectType(RedirectType::INTERNAL);
        $page1_1_3->setRedirectTarget($page1);
        $this->documentManager->persist(
            $page1_1_3,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node1/node1-1',
            ]
        );
        $this->documentManager->flush();

        $page1_2 = $this->documentManager->create('page');
        $page1_2->setStructureType('default');
        $page1_2->setTitle('Node1-2');
        $page1_2->getStructure()->bind(['article' => 'This is a perfect description.']);
        $page1_2->setResourceSegment('/node1-2');
        $this->documentManager->persist(
            $page1_2,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node1',
            ]
        );
        $this->documentManager->flush();

        $page2 = $this->documentManager->create('page');
        $page2->setStructureType('smartcontent');
        $page2->setTitle('Node2');
        $page2->getStructure()->bind(
            [
                'title' => 'Node2',
                'smart_content' => [
                    'dataSource' => $page1->getUuid(),
                ],
            ]
        );
        $page2->setResourceSegment('/node2');
        $this->documentManager->persist(
            $page2,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents',
            ]
        );
        $this->documentManager->flush();

        $page2_1 = $this->documentManager->create('page');
        $page2_1->setStructureType('internallinks');
        $page2_1->setTitle('Node2-1');
        $page2_1->getStructure()->bind(
            [
                'title' => 'Node2-1',
                'internalLinks' => [
                    $page1->getUuid(),
                    $page2->getUuid(),
                    $testIoHomeDocument->getUuid(),
                ],
            ]
        );
        $page2_1->setResourceSegment('/node2-1');
        $this->documentManager->persist(
            $page2_1,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node2',
            ]
        );
        $this->documentManager->flush();

        $page2_2 = $this->documentManager->create('page');
        $page2_2->setStructureType('internallinks');
        $page2_2->setTitle('Node2-2');
        $page2_2->getStructure()->bind(
            [
                'title' => 'Node2-2',
                'singleInternalLinks' => $page1->getUuid(),
            ]
        );
        $page2_2->setResourceSegment('/node2-2');
        $this->documentManager->persist(
            $page2_2,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node2',
            ]
        );
        $this->documentManager->flush();

        $page3 = $this->documentManager->create('page');
        $page3->setStructureType('smartcontent');
        $page3->setTitle('Node3');
        $page3->getStructure()->bind(
            [
                'title' => 'Node3',
                'smart_content' => [
                    'dataSource' => $testIoHomeDocument->getUuid(),
                ],
            ]
        );
        $page3->setResourceSegment('/node3');
        $this->documentManager->persist(
            $page3,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents',
            ]
        );
        $this->documentManager->flush();

        $page4 = $this->documentManager->create('page');
        $page4->setStructureType('block');
        $page4->setTitle('Node4');
        $page4->getStructure()->bind(
            [
                'title' => 'Node4',
                'article' => [
                    [
                        'text' => '<p><sulu:link href="' . $page1->getUuid() . '" provider="page" target="_self" title="Link-Title">Link-Title</sulu:link></p>',
                        'title' => 'Node4 block',
                        'type' => 'textEditor',
                    ],
                ],
            ]
        );
        $page4->setResourceSegment('/node4');
        $this->documentManager->persist(
            $page4,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents',
            ]
        );
        $this->documentManager->flush();
    }
}
