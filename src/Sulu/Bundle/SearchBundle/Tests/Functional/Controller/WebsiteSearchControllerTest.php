<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Functional\Controller;

use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class WebsiteSearchControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $websiteClient;

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();
        static::initPhpcr();

        $searchManager = self::getContainer()->get('massive_search.search_manager');
        foreach ($searchManager->getIndexNames() as $indexName) {
            $searchManager->purge($indexName);
        }
        $searchManager->flush();

        $documentManager = self::getContainer()->get('sulu_document_manager.document_manager');

        /** @var PageDocument $document1 */
        $document1 = $documentManager->create('page');
        $document1->setLocale('de');
        $document1->setTitle('Hello World');
        $document1->setResourceSegment('/hello-world');
        $document1->setStructureType('default');
        $document1->setExtensionsData(['seo' => [], 'excerpt' => []]);
        $document1->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document1->getStructure()->bind([
            'title' => 'Hello World',
        ]);
        $documentManager->persist($document1, 'de', [
            'parent_path' => '/cmf/sulu_io/contents',
        ]);
        $documentManager->publish($document1, 'de');
        $documentManager->flush();

        /** @var PageDocument $document1 */
        $document2 = $documentManager->create('page');
        $document2->setLocale('de');
        $document2->setTitle('Example Product Page');
        $document2->setResourceSegment('/example-product-page');
        $document2->setStructureType('default');
        $document2->setExtensionsData(['seo' => [], 'excerpt' => []]);
        $document2->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document2->getStructure()->bind([
            'title' => 'Example Product Page',
        ]);
        $documentManager->persist($document2, 'de', [
            'parent_path' => '/cmf/sulu_io/contents',
        ]);
        $documentManager->publish($document2, 'de');
        $documentManager->flush();

        static::ensureKernelShutdown();
    }

    protected function setUp(): void
    {
        $this->websiteClient = static::createWebsiteClient();
    }

    public function testSearchExactTerm(): void
    {
        /** @var Crawler $crawler */
        $crawler = $this->websiteClient->request('GET', 'http://de.sulu.lo/search?q=Product');
        $response = $this->websiteClient->getResponse();

        static::assertHttpStatusCode(200, $response);
        $this->assertSame(1, $crawler->filter('.search-result')->count());
    }

    public function testSearchExactTermEndingWithSpace(): void
    {
        /** @var Crawler $crawler */
        $crawler = $this->websiteClient->request('GET', 'http://de.sulu.lo/search?q=Product%20');
        $response = $this->websiteClient->getResponse();

        static::assertHttpStatusCode(200, $response);
        $this->assertSame(1, $crawler->filter('.search-result')->count());
    }

    public function testSearchIncompleteTerm(): void
    {
        /** @var Crawler $crawler */
        $crawler = $this->websiteClient->request('GET', 'http://de.sulu.lo/search?q=Prod');
        $response = $this->websiteClient->getResponse();

        static::assertHttpStatusCode(200, $response);
        $this->assertSame(1, $crawler->filter('.search-result')->count());
    }

    public function testSearchTermFuzzy(): void
    {
        /** @var Crawler $crawler */
        $crawler = $this->websiteClient->request('GET', 'http://de.sulu.lo/search?q=Prodoct');
        $response = $this->websiteClient->getResponse();

        static::assertHttpStatusCode(200, $response);
        $this->assertSame(1, $crawler->filter('.search-result')->count());
    }

    public function testSearchTermWithSpecialChar(): void
    {
        /** @var Crawler $crawler */
        $crawler = $this->websiteClient->request('GET', 'http://de.sulu.lo/search?q=Prod*?t');
        $response = $this->websiteClient->getResponse();

        static::assertHttpStatusCode(200, $response);
        $this->assertSame(1, $crawler->filter('.search-result')->count());
    }
}
