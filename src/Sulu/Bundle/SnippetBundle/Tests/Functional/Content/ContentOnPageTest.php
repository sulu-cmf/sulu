<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Content;

use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Tests\Functional\BaseFunctionalTestCase;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class ContentOnPageTest extends BaseFunctionalTestCase
{
    /**
     * @var ContentMapperInterface
     */
    protected $contentMapper;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SnippetDocument
     */
    private $snippet1;

    /**
     * @var SnippetDocument
     */
    private $snippet2;

    public function setUp(): void
    {
        $this->initPhpcr();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->loadFixtures();
    }

    public function loadFixtures(): void
    {
        $this->snippet1 = $this->documentManager->create('snippet');
        $this->snippet1->setStructureType('hotel');
        $this->snippet1->setTitle('ElePHPant');
        $this->snippet1->getStructure()->bind([
            'description' => 'Elephants are large mammals of the family Elephantidae and the order Proboscidea.',
        ]);
        $this->snippet1->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($this->snippet1, 'de');
        $this->documentManager->flush();

        $this->snippet1 = $this->documentManager->create('snippet');
        $this->snippet1->setStructureType('hotel');
        $this->snippet1->setTitle('Penguin');
        $this->snippet1->getStructure()->bind([
            'description' => 'Penguins (order Sphenisciformes, family Spheniscidae) are a group of aquatic, flightless birds living almost exclusively in the Southern Hemisphere, especially in Antarctica.',
        ]);
        $this->snippet1->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($this->snippet1, 'de');
        $this->documentManager->flush();
    }

    public static function provideSaveSnippetPage()
    {
        return [
            [
                'sulu_io',
                'hotel_page',
                'de',
                [
                    'title' => 'My new snippet page',
                    'url' => '/snippetpage',
                    'hotels' => [],
                ],
            ],
            [
                'sulu_io',
                'hotel_page',
                'de',
                [
                    'title' => 'Another snippet page',
                    'url' => '/anothersnippetpage',
                    'hotels' => ['snippet1'],
                ],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSaveSnippetPage')]
    public function testSaveLoadSnippetPage($webspaceKey, $templateKey, $locale, $data): void
    {
        foreach ($data['hotels'] as &$varName) {
            $varName = $this->{$varName}->getUUid();
        }

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setStructureType($templateKey);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setTitle($data['title']);
        $document->setResourceSegment($data['url']);
        $document->getStructure()->bind($data);
        $this->documentManager->persist($document, $locale, ['parent_path' => '/cmf/' . $webspaceKey . '/contents']);
        $this->documentManager->flush();

        $structure = $document->getStructure();

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $structure->getProperty($key)->getValue());
        }
        $this->assertEquals($templateKey, $document->getStructureType());

        $page = $this->contentMapper->load(
            $document->getUuid(),
            $webspaceKey,
            $locale
        );

        $this->assertInstanceOf(PageBridge::class, $page);
        $this->assertEquals($templateKey, $page->getKey());

        foreach ($data as $key => $value) {
            if ('hotels' === $key) {
                continue;
            }

            $this->assertEquals($value, $page->getPropertyValue($key), 'Checking property "' . $key . '"');
        }

        $hotels = $page->getPropertyValue('hotels');
        $this->assertCount(\count($data['hotels']), $hotels);

        $this->assertEquals($data['hotels'], $hotels);
    }
}
