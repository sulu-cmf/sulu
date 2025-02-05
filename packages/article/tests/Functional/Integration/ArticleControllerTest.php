<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Tests\Functional\Integration;

use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 *
 * @coversNothing
 */
class ArticleControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    /**
     * @var KernelBrowser
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );
    }

    public function testPostPublish(): string
    {
        self::purgeDatabase();
        self::initPhpcr();

        $this->client->request('POST', '/admin/api/articles?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Test Article',
            'url' => '/my-article',
            'published' => '2020-05-08T00:00:00+00:00', // Should be ignored
            'description' => null,
            'image' => null,
            'lastModified' => '2022-05-08T00:00:00+00:00',
            'lastModifiedEnabled' => true,
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoCanonicalUrl' => 'https://sulu.io/',
            'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
            'seoNoIndex' => true,
            'seoNoFollow' => true,
            'seoHideInSitemap' => true,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'author' => null,
            'authored' => '2020-05-08T00:00:00+00:00',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var string $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $this->assertResponseSnapshot('article_post_publish.json', $response, 201);
        $this->assertNotSame('2020-05-08T00:00:00+00:00', $content['published']); // @phpstan-ignore-line

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/en/my-article');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('Test Article', $content);

        return $id;
    }

    /**
     * @depends testPostPublish
     */
    public function testPostTriggerUnpublish(string $id): void
    {
        $this->client->request('POST', '/admin/api/articles/' . $id . '?locale=en&action=unpublish');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('article_post_trigger_unpublish.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/en/my-article');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    public function testPost(): string
    {
        self::purgeDatabase();

        $this->client->request('POST', '/admin/api/articles?locale=en', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Test Article',
            'url' => '/my-article',
            'images' => null,
            'lastModified' => '2022-05-08T00:00:00+00:00',
            'lastModifiedEnabled' => true,
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoCanonicalUrl' => 'https://sulu.io/',
            'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
            'seoNoIndex' => true,
            'seoNoFollow' => true,
            'seoHideInSitemap' => true,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'mainWebspace' => 'sulu-io',
            'authored' => '2020-05-08T00:00:00+00:00',
        ]) ?: null);

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('article_post.json', $response, 201);

        $routeRepository = $this->getContainer()->get('sulu.repository.route');
        $this->assertCount(0, $routeRepository->findAll());

        /** @var string $id */
        $id = \json_decode((string) $response->getContent(), true)['id'] ?? null; // @phpstan-ignore-line

        return $id;
    }

    /**
     * @depends testPost
     */
    public function testGet(string $id): void
    {
        $this->client->request('GET', '/admin/api/articles/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('article_get.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/en/my-article');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    /**
     * @depends testPost
     */
    public function testGetGhostLocale(string $id): void
    {
        $this->client->request('GET', '/admin/api/articles/' . $id . '?locale=de');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('article_get_ghost_locale.json', $response, 200);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $websiteClient->request('GET', '/de/my-article');

        $response = $websiteClient->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    /**
     * @depends testPost
     */
    public function testPostTriggerCopyLocale(string $id): void
    {
        $this->client->request('POST', '/admin/api/articles/' . $id . '?locale=de&action=copy-locale&src=en&dest=de');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('article_post_trigger_copy_locale.json', $response, 200);
    }

    /**
     * @depends testPost
     * @depends testGet
     */
    public function testPut(string $id): void
    {
        $this->client->request('PUT', '/admin/api/articles/' . $id . '?locale=en', [], [], [], \json_encode([
            'template' => 'article',
            'title' => 'Test Article 2',
            'url' => '/my-article-2',
            'description' => '<p>Test Article 2</p>',
            'seoTitle' => 'Seo Title 2',
            'seoDescription' => 'Seo Description 2',
            'seoCanonicalUrl' => 'https://sulu.io/2',
            'seoKeywords' => 'Seo Keyword 3, Seo Keyword 4',
            'seoNoIndex' => false,
            'seoNoFollow' => false,
            'seoHideInSitemap' => false,
            'excerptTitle' => 'Excerpt Title 2',
            'excerptDescription' => 'Excerpt Description 2',
            'excerptMore' => 'Excerpt More 2',
            'excerptTags' => ['Tag 3', 'Tag 4'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
            'authored' => '2020-06-09T00:00:00+00:00',
            'mainWebspace' => 'sulu-io',
        ]) ?: null);

        $response = $this->client->getResponse();

        $routeRepository = $this->getContainer()->get('sulu.repository.route');
        $this->assertCount(0, $routeRepository->findAll());

        $this->assertResponseSnapshot('article_put.json', $response, 200);
    }

    /**
     * @depends testPost
     * @depends testPut
     */
    public function testGetList(): void
    {
        $this->client->request('GET', '/admin/api/articles?locale=en');
        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('article_cget.json', $response, 200);
    }

    /**
     * @depends testPost
     * @depends testGetList
     */
    public function testDelete(string $id): void
    {
        $this->client->request('DELETE', '/admin/api/articles/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $routeRepository = $this->getContainer()->get('sulu.repository.route');
        $this->assertCount(0, $routeRepository->findAll());
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}
