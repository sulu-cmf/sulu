<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Reference\Refresh;

use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Reference\Refresh\PageReferenceRefresher;
use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\PHPCR\SessionManager\SessionManager;

class PageReferenceRefresherTest extends SuluTestCase
{
    private PageReferenceRefresher $pageReferenceRefresher;
    private DocumentManagerInterface $documentManager;
    private SessionManager $sessionManager;

    /**
     * @var EntityRepository<Reference>
     */
    private EntityRepository $referenceRepository;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $this->initPhpcr();

        $this->pageReferenceRefresher = $this->getContainer()->get('sulu_page.page_reference_refresher');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->referenceRepository = $this->getContainer()->get('sulu.repository.reference');
    }

    public function testRefreshWithoutReferences(): void
    {
        /** @var PageDocument $page */
        $page = $this->documentManager->create('page');
        $page->setTitle('Example page');
        $page->setStructureType('test_page');
        $page->setResourceSegment('/example-page-123');
        $page->setParent($this->documentManager->find($this->sessionManager->getContentPath('sulu_io')));
        $this->documentManager->persist($page, 'en');
        $this->documentManager->publish($page, 'en');
        $this->documentManager->flush();

        $count = 0;
        foreach ($this->pageReferenceRefresher->refresh() as $document) {
            ++$count;
        }
        // flush the references
        $this->getEntityManager()->flush();
        $this->assertSame(5, $count);

        self::assertCount(0, $this->referenceRepository->findAll());
    }

    public function testRefresh(): void
    {
        /** @var SnippetDocument $snippet */
        $snippet = $this->documentManager->create('snippet');
        $snippet->setStructureType('animal');
        $snippet->setTitle('Example snippet');
        $snippet->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($snippet, 'en');

        /** @var PageDocument $page */
        $page = $this->documentManager->create('page');
        $page->setTitle('Example page');
        $page->setLocale('en');
        $page->setStructureType('test_page');
        $page->setResourceSegment('/example-page-123');
        $page->setParent($this->documentManager->find($this->sessionManager->getContentPath('sulu_io')));

        $page->getStructure()->bind([
            'title' => 'Example page',
            'template' => 'test_page',
            'url' => '/test',
            'animals' => [$snippet->getUuid()],
        ]);

        $this->documentManager->persist($page, 'en');
        $this->documentManager->publish($page, 'en');
        $this->documentManager->flush();

        $count = 0;
        foreach ($this->pageReferenceRefresher->refresh() as $document) {
            ++$count;
        }
        $this->getEntityManager()->flush();
        $this->assertSame(5, $count);

        /** @var Reference[] $references */
        $references = $this->referenceRepository->findBy([
            'referenceResourceKey' => 'pages',
            'referenceResourceId' => $page->getUuid(),
            'referenceLocale' => 'en',
        ]);

        self::assertCount(2, $references);

        self::assertSame('animals', $references[0]->getReferenceProperty());
        self::assertSame($snippet->getUuid(), $references[0]->getResourceId());
        self::assertSame('snippets', $references[0]->getResourceKey());
        self::assertSame($page->getUuid(), $references[0]->getReferenceResourceId());
        self::assertSame('pages', $references[0]->getReferenceResourceKey());
        self::assertSame('en', $references[0]->getReferenceLocale());
        self::assertSame('website', $references[0]->getReferenceContext());

        self::assertSame('animals', $references[1]->getReferenceProperty());
        self::assertSame($snippet->getUuid(), $references[1]->getResourceId());
        self::assertSame('snippets', $references[1]->getResourceKey());
        self::assertSame($page->getUuid(), $references[1]->getReferenceResourceId());
        self::assertSame('pages', $references[1]->getReferenceResourceKey());
        self::assertSame('en', $references[1]->getReferenceLocale());
        self::assertSame('admin', $references[1]->getReferenceContext());
    }
}
