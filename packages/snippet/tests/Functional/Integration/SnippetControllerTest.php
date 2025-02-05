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

namespace Sulu\Snippet\Tests\Functional\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Snippet\Tests\Traits\AssertSnapshotTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 *
 * @coversNothing
 */
class SnippetControllerTest extends SuluTestCase
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

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema($classes, false);
    }

    public function testPostPublish(): string
    {
        self::purgeDatabase();
        self::initPhpcr();

        $this->client->request('POST', '/admin/api/snippets?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'snippet',
            'title' => 'Test Snippet',
            'published' => '2020-05-08T00:00:00+00:00', // Should be ignored
            'description' => null,
            'image' => null,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
        ]) ?: null);

        $response = $this->client->getResponse();
        $content = \json_decode((string) $response->getContent(), true);
        /** @var string $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $this->assertResponseSnapshot('snippet_post_publish.json', $response, 201);
        $this->assertNotSame('2020-05-08T00:00:00+00:00', $content['published']); // @phpstan-ignore-line

        self::ensureKernelShutdown();

        return $id;
    }

    /**
     * @depends testPostPublish
     */
    public function testPostTriggerUnpublish(string $id): void
    {
        $this->client->request('POST', '/admin/api/snippets/' . $id . '?locale=en&action=unpublish');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('snippet_post_trigger_unpublish.json', $response, 200);
    }

    public function testPost(): string
    {
        self::purgeDatabase();

        $this->client->request('POST', '/admin/api/snippets?locale=en', [], [], [], \json_encode([
            'template' => 'snippet',
            'title' => 'Test Snippet',
            'images' => null,
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
        ]) ?: null);

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('snippet_post.json', $response, 201);

        /** @var string $id */
        $id = \json_decode((string) $response->getContent(), true)['id'] ?? null; // @phpstan-ignore-line

        return $id;
    }

    /**
     * @depends testPost
     */
    public function testGet(string $id): void
    {
        $this->client->request('GET', '/admin/api/snippets/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_get.json', $response, 200);
    }

    /**
     * @depends testPost
     */
    public function testGetGhostLocale(string $id): void
    {
        $this->client->request('GET', '/admin/api/snippets/' . $id . '?locale=de');
        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('snippet_get_ghost_locale.json', $response, 200);
    }

    /**
     * @depends testPost
     */
    public function testPostTriggerCopyLocale(string $id): void
    {
        $this->client->request('POST', '/admin/api/snippets/' . $id . '?locale=de&action=copy-locale&src=en&dest=de');

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('snippet_post_trigger_copy_locale.json', $response, 200);
    }

    /**
     * @depends testPost
     * @depends testGet
     */
    public function testPut(string $id): void
    {
        $this->client->request('PUT', '/admin/api/snippets/' . $id . '?locale=en', [], [], [], \json_encode([
            'template' => 'snippet',
            'title' => 'Test Snippet 2',
            'description' => '<p>Test Snippet 2</p>',
            'excerptTitle' => 'Excerpt Title 2',
            'excerptDescription' => 'Excerpt Description 2',
            'excerptMore' => 'Excerpt More 2',
            'excerptTags' => ['Tag 3', 'Tag 4'],
            'excerptCategories' => [],
            'excerptIcon' => null,
            'excerptMedia' => null,
        ]) ?: null);

        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('snippet_put.json', $response, 200);
    }

    /**
     * @depends testPost
     * @depends testPut
     */
    public function testGetList(): void
    {
        $this->client->request('GET', '/admin/api/snippets?locale=en');
        $response = $this->client->getResponse();

        $this->assertResponseSnapshot('snippet_cget.json', $response, 200);
    }

    /**
     * @depends testPost
     * @depends testGetList
     */
    public function testDelete(string $id): void
    {
        $this->client->request('DELETE', '/admin/api/snippets/' . $id . '?locale=en');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}
