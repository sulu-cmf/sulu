<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Controller;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\DocumentManagerBundle\Slugifier\Urlizer;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\LocalizationState;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ArticlePageControllerTest extends SuluTestCase
{
    use ArticleViewDocumentIdTrait;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();

        $this->purgeIndex();

        $this->purgeDatabase();
        $this->initPhpcr();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    private function createArticle($title = 'Test-Article', $template = 'default_pages', $locale = 'de')
    {
        $this->client->jsonRequest(
            'POST',
            '/api/articles?locale=' . $locale,
            [
                'title' => $title,
                'pageTitle' => $title,
                'template' => $template,
                'authored' => \date('c', \strtotime('2016-01-01')),
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    private function createArticleLocale($article, $title = 'Test-Article', $template = 'default_pages', $locale = 'en')
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=' . $locale,
            [
                'title' => $title,
                'pageTitle' => $title,
                'template' => $template,
                'authored' => \date('c', \strtotime('2016-01-01')),
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    private function createArticleShadow($article, $locale = 'en', $shadowLocale = 'de')
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=' . $locale,
            [
                'shadowOn' => true,
                'shadowBaseLanguage' => $shadowLocale,
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    private function getArticle($uuid, $locale = 'de')
    {
        $this->client->jsonRequest('GET', '/api/articles/' . $uuid . '?locale=' . $locale);

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    private function post($article, $pageTitle = 'Test-Page', $template = 'default_pages', $locale = 'de')
    {
        $this->client->jsonRequest(
            'POST',
            '/api/articles/' . $article['id'] . '/pages?locale=' . $locale,
            [
                'pageTitle' => $pageTitle,
                'template' => $template,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testPost($title = 'Test-Article', $pageTitle = 'Test-Page', $template = 'default_pages')
    {
        $article = $this->createArticle($title, $template);
        $response = $this->post($article, $pageTitle);

        $this->assertEquals($title, $response['title']);
        $this->assertEquals($pageTitle, $response['pageTitle']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals($this->getRoute($title, 2), $response['route']);
        $this->assertEquals(2, $response['pageNumber']);

        $this->assertEquals($article['id'], $response['parent']['id']);

        $article = $this->getArticle($article['id']);

        $pages = $article['children'];

        $this->assertCount(1, $pages);
        $this->assertEquals($response['id'], \reset($pages)['id']);

        $articleViewDocument = $this->findViewDocument($article['id'], 'de');
        $this->assertCount(1, $articleViewDocument->getPages());
        $this->assertEquals(2, $articleViewDocument->getPages()[0]->pageNumber);
        $this->assertEquals($pageTitle, $articleViewDocument->getPages()[0]->title);
        $this->assertEquals($response['id'], $articleViewDocument->getPages()[0]->uuid);
    }

    public function testPostMultiplePages($title = 'Test-Article')
    {
        $article = $this->createArticle($title);

        $response1 = $this->post($article, 'Test-1');
        $this->assertEquals($title, $response1['title']);
        $this->assertEquals('Test-1', $response1['pageTitle']);
        $this->assertEquals($this->getRoute($title, 2), $response1['route']);
        $this->assertEquals(2, $response1['pageNumber']);

        $response2 = $this->post($article, 'Test-2');
        $this->assertEquals($title, $response2['title']);
        $this->assertEquals('Test-2', $response2['pageTitle']);
        $this->assertEquals($this->getRoute($title, 3), $response2['route']);
        $this->assertEquals(3, $response2['pageNumber']);

        $article = $this->getArticle($article['id']);

        $pages = \array_values($article['children']);

        $this->assertCount(2, $pages);
        $this->assertEquals($response1['id'], $pages[0]['id']);
        $this->assertEquals($response2['id'], $pages[1]['id']);

        $articleViewDocument = $this->findViewDocument($article['id'], 'de');
        $this->assertCount(2, $articleViewDocument->getPages());

        $this->assertEquals(2, $articleViewDocument->getPages()[0]->pageNumber);
        $this->assertEquals('Test-1', $articleViewDocument->getPages()[0]->title);
        $this->assertEquals($response1['id'], $articleViewDocument->getPages()[0]->uuid);
        $this->assertEquals($response1['route'], $articleViewDocument->getPages()[0]->routePath);

        $this->assertEquals(3, $articleViewDocument->getPages()[1]->pageNumber);
        $this->assertEquals('Test-2', $articleViewDocument->getPages()[1]->title);
        $this->assertEquals($response2['id'], $articleViewDocument->getPages()[1]->uuid);
        $this->assertEquals($response2['route'], $articleViewDocument->getPages()[1]->routePath);
    }

    public function testGet($title = 'Test-Article', $pageTitle = 'Test-Page', $template = 'default_pages')
    {
        $article = $this->createArticle($title, $template);
        $page = $this->post($article, $pageTitle);

        $this->client->jsonRequest('GET', '/api/articles/' . $article['id'] . '/pages/' . $page['id'] . '?locale=de');

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($title, $response['title']);
        $this->assertEquals($this->getRoute($title, 2), $response['route']);
        $this->assertEquals($pageTitle, $response['pageTitle']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals(2, $response['pageNumber']);
    }

    public function testPut($title = 'Test-Article', $pageTitle = 'New-Page-Title', $template = 'default_pages')
    {
        $article = $this->createArticle($title, $template);
        $page = $this->post($article);

        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '/pages/' . $page['id'] . '?locale=de',
            [
                'pageTitle' => $pageTitle,
                'article' => 'Sulu is awesome',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($title, $response['title']);
        $this->assertEquals($this->getRoute($title, 2), $response['route']);
        $this->assertEquals($pageTitle, $response['pageTitle']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals('Sulu is awesome', $response['article']);
        $this->assertEquals(2, $response['pageNumber']);

        $articleViewDocument = $this->findViewDocument($article['id'], 'de');
        $this->assertCount(1, $articleViewDocument->getPages());
        $this->assertEquals(2, $articleViewDocument->getPages()[0]->pageNumber);
        $this->assertEquals($pageTitle, $articleViewDocument->getPages()[0]->title);
        $this->assertEquals($response['id'], $articleViewDocument->getPages()[0]->uuid);
    }

    public function testPutPublish($title = 'Test-Article', $pageTitle = 'New-Page-Title', $template = 'default_pages')
    {
        $article = $this->createArticle($title, $template);
        $page = $this->post($article);

        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '/pages/' . $page['id'] . '?locale=de&action=publish',
            [
                'pageTitle' => $pageTitle,
                'article' => 'Sulu is awesome',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($title, $response['title']);
        $this->assertEquals($this->getRoute($title, 2), $response['route']);
        $this->assertEquals($pageTitle, $response['pageTitle']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals('Sulu is awesome', $response['article']);
        $this->assertEquals(2, $response['pageNumber']);

        $articleViewDocument = $this->findViewDocument($article['id'], 'de');
        $this->assertCount(1, $articleViewDocument->getPages());
        $this->assertEquals(2, $articleViewDocument->getPages()[0]->pageNumber);
        $this->assertEquals($pageTitle, $articleViewDocument->getPages()[0]->title);
        $this->assertEquals($response['id'], $articleViewDocument->getPages()[0]->uuid);
    }

    public function testDelete()
    {
        $article = $this->createArticle();
        $page = $this->post($article);

        $this->client->jsonRequest('DELETE', '/api/articles/' . $article['id'] . '/pages/' . $page['id'] . '?locale=de');

        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $article = $this->getArticle($article['id']);

        $this->assertCount(0, $article['children']);

        $articleViewDocument = $this->findViewDocument($article['id'], 'de');
        $this->assertCount(0, $articleViewDocument->getPages());
    }

    public function testHandleSecondLocale($title = 'Sulu ist toll')
    {
        $articleDE = $this->createArticle($title);
        $page1 = $this->post($articleDE, 'Sulu ist toll - Page 1');

        $articleEN = $this->createArticleLocale($articleDE, 'Sulu is great');

        // page 1 should exists with empty pageTitle
        $this->client->jsonRequest('GET', '/api/articles/' . $articleEN['id'] . '/pages/' . $page1['id'] . '?locale=en');

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertArrayNotHasKey('type', $response);
        $this->assertEquals('', $response['pageTitle']);
    }

    public function testHandleShadowArticlePageForEN($title = 'Sulu ist toll')
    {
        $this->markTestSkipped();

        $articleDE = $this->createArticle($title);
        $page2 = $this->post($articleDE, 'Sulu ist toll - Page 2');
        $page3 = $this->post($articleDE, 'Sulu ist toll - Page 3');
        $page4 = $this->post($articleDE, 'Sulu ist toll - Page 4');

        $articleEN = $this->createArticleLocale($articleDE, 'Sulu is great');
        $articleShadow = $this->createArticleShadow($articleEN);

        // check the article itself
        $this->assertEquals('Sulu ist toll', $articleShadow['title']);

        // load second page
        $this->client->jsonRequest('GET', '/api/articles/' . $articleEN['id'] . '/pages/' . $page2['id'] . '?locale=en');
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('Sulu ist toll', $response['title']);
        $this->assertTrue($response['shadowOn']);
        $this->assertEquals('de', $response['shadowBaseLanguage']);
        $this->assertEquals('/articles/sulu-is-great/page-2', $response['route']);

        $articleViewDocument = $this->findViewDocument($articleEN['id'], 'en');
        $this->assertEquals('en', $articleViewDocument->getLocale());
        $this->assertEquals('/articles/sulu-is-great', $articleViewDocument->getRoutePath());
        $this->assertEquals('Sulu ist toll', $articleViewDocument->getTitle());
        $this->assertEquals(LocalizationState::SHADOW, $articleViewDocument->getLocalizationState()->state);
        $this->assertEquals('de', $articleViewDocument->getLocalizationState()->locale);
        $this->assertCount(3, $articleViewDocument->getPages());
        $this->assertEquals('Sulu ist toll - Page 2', $articleViewDocument->getPages()[0]->title);
        $this->assertEquals('Sulu ist toll - Page 3', $articleViewDocument->getPages()[1]->title);
        $this->assertEquals('Sulu ist toll - Page 4', $articleViewDocument->getPages()[2]->title);
        $this->assertEquals('/articles/sulu-is-great/page-2', $articleViewDocument->getPages()[0]->routePath);
        $this->assertEquals('/articles/sulu-is-great/page-3', $articleViewDocument->getPages()[1]->routePath);
        $this->assertEquals('/articles/sulu-is-great/page-4', $articleViewDocument->getPages()[2]->routePath);
    }

    public function testHandleShadowArticlePageForDE($title = 'Sulu is great')
    {
        $articleEN = $this->createArticle($title, 'default_pages', 'en');
        $page2 = $this->post($articleEN, 'Sulu is great - Page 2', 'default_pages', 'en');
        $page3 = $this->post($articleEN, 'Sulu is great - Page 3', 'default_pages', 'en');

        $articleDE = $this->createArticleLocale($articleEN, 'Sulu is great', 'default_pages', 'de');
        $articleShadow = $this->createArticleShadow($articleDE, 'de', 'en');

        // check the article itself
        $this->assertEquals('Sulu is great', $articleShadow['title']);

        // load second page
        $this->client->jsonRequest('GET', '/api/articles/' . $articleDE['id'] . '/pages/' . $page2['id'] . '?locale=de');
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('Sulu is great', $response['title']);
        $this->assertEquals('Sulu is great - Page 2', $response['pageTitle']);
    }

    private function purgeIndex()
    {
        /** @var IndexerInterface $indexer */
        $indexer = $this->getContainer()->get('sulu_article.elastic_search.article_indexer');
        $indexer->clear();
    }

    /**
     * @return ArticleViewDocumentInterface
     */
    private function findViewDocument($uuid, $locale)
    {
        /** @var Manager $manager */
        $manager = $this->getContainer()->get('es.manager.default');

        return $manager->find(
            $this->getContainer()->getParameter('sulu_article.view_document.article.class'),
            $this->getViewDocumentId($uuid, $locale)
        );
    }

    private function getRoute($title, $page)
    {
        return '/articles/' . Urlizer::urlize($title) . '/page-' . $page;
    }
}
