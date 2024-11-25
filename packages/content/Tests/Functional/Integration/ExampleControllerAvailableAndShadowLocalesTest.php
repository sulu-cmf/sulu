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

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * This test is responsibility to test the update of available and shadow locales based on different
 * publishing processes.
 *
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 *
 * @coversNothing
 */
class ExampleControllerAvailableAndShadowLocalesTest extends SuluTestCase
{
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

    public function testPostCreateEnDraft(): int
    {
        self::purgeDatabase();
        self::initPhpcr();

        $this->client->request('POST', '/admin/api/examples?locale=en', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test EN',
            'url' => '/test-en',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(201, $response);
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $data = $this->getDimensionContent($id);

        $this->assertSame([
            [
                'stage' => 'draft',
                'locale' => null,
                'availableLocales' => ['en'],
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [],
            ],
            [
                'stage' => 'draft',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN',
                    'images' => null,
                ],
            ],
        ], $data);

        return $id;
    }

    /**
     * @depends testPostCreateEnDraft
     */
    public function testPostCreateDeDraft(int $id): int
    {
        $this->client->request('PUT', '/admin/api/examples/' . $id . '?locale=de', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test DE',
            'url' => '/test-de',
            'shadowOn' => true,
            'shadowLocale' => 'en',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $data = $this->getDimensionContent($id);

        $this->assertSame([
            [
                'stage' => 'draft',
                'locale' => null,
                'availableLocales' => ['en', 'de'],
                'shadowLocale' => null,
                'shadowLocales' => ['de' => 'en'],
                'templateData' => [],
            ],
            [
                'stage' => 'draft',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'draft',
                'locale' => 'de',
                'availableLocales' => null,
                'shadowLocale' => 'en',
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-de',
                    'title' => 'Test DE',
                    'images' => null,
                ],
            ],
        ], $data);

        return $id;
    }

    /**
     * @depends testPostCreateDeDraft
     */
    public function testPostPublishEn(int $id): int
    {
        $this->client->request('PUT', '/admin/api/examples/' . $id . '?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test EN',
            'url' => '/test-en',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $data = $this->getDimensionContent($id);

        $this->assertSame([
            [
                'stage' => 'draft',
                'locale' => null,
                'availableLocales' => ['en', 'de'],
                'shadowLocale' => null,
                'shadowLocales' => ['de' => 'en'],
                'templateData' => [],
            ],
            [
                'stage' => 'draft',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'draft',
                'locale' => 'de',
                'availableLocales' => null,
                'shadowLocale' => 'en',
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-de',
                    'title' => 'Test DE',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'live',
                'locale' => null,
                'availableLocales' => ['en'],
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [],
            ],
            [
                'stage' => 'live',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN',
                    'images' => null,
                ],
            ],
        ], $data);

        return $id;
    }

    /**
     * @depends testPostPublishEn
     */
    public function testPostPublishDe(int $id): int
    {
        $this->client->request('PUT', '/admin/api/examples/' . $id . '?locale=de&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test DE',
            'url' => '/test-de',
            'shadowOn' => true,
            'shadowLocale' => 'en',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $data = $this->getDimensionContent($id);

        $this->assertSame([
            [
                'stage' => 'draft',
                'locale' => null,
                'availableLocales' => ['en', 'de'],
                'shadowLocale' => null,
                'shadowLocales' => ['de' => 'en'],
                'templateData' => [],
            ],
            [
                'stage' => 'draft',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'draft',
                'locale' => 'de',
                'availableLocales' => null,
                'shadowLocale' => 'en',
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-de',
                    'title' => 'Test DE',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'live',
                'locale' => null,
                'availableLocales' => ['en', 'de'],
                'shadowLocale' => null,
                'shadowLocales' => ['de' => 'en'],
                'templateData' => [],
            ],
            [
                'stage' => 'live',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'live',
                'locale' => 'de',
                'availableLocales' => null,
                'shadowLocale' => 'en',
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-de',
                    'title' => 'Test EN',
                    'images' => null,
                ],
            ],
        ], $data);

        return $id;
    }

    /**
     * @depends testPostPublishDe
     */
    public function testPostRepublishEn(int $id): int
    {
        $this->client->request('PUT', '/admin/api/examples/' . $id . '?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test EN New',
            'url' => '/test-en',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $data = $this->getDimensionContent($id);

        $this->assertSame([
            [
                'stage' => 'draft',
                'locale' => null,
                'availableLocales' => ['en', 'de'],
                'shadowLocale' => null,
                'shadowLocales' => ['de' => 'en'],
                'templateData' => [],
            ],
            [
                'stage' => 'draft',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN New',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'draft',
                'locale' => 'de',
                'availableLocales' => null,
                'shadowLocale' => 'en',
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-de',
                    'title' => 'Test DE',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'live',
                'locale' => null,
                'availableLocales' => ['en', 'de'],
                'shadowLocale' => null,
                'shadowLocales' => ['de' => 'en'],
                'templateData' => [],
            ],
            [
                'stage' => 'live',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN New',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'live',
                'locale' => 'de',
                'availableLocales' => null,
                'shadowLocale' => 'en',
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-de',
                    'title' => 'Test EN New',
                    'images' => null,
                ],
            ],
        ], $data);

        return $id;
    }

    /**
     * @depends testPostRepublishEn
     */
    public function testPostUnpublishDe(int $id): int
    {
        $this->client->request('POST', '/admin/api/examples/' . $id . '?locale=de&action=unpublish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test DE',
            'url' => '/test-de',
            'shadowOn' => true,
            'shadowLocale' => 'en',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $data = $this->getDimensionContent($id);

        $this->assertSame([
            [
                'stage' => 'draft',
                'locale' => null,
                'availableLocales' => ['en', 'de'],
                'shadowLocale' => null,
                'shadowLocales' => ['de' => 'en'],
                'templateData' => [],
            ],
            [
                'stage' => 'draft',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN New',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'draft',
                'locale' => 'de',
                'availableLocales' => null,
                'shadowLocale' => 'en',
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-de',
                    'title' => 'Test DE',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'live',
                'locale' => null,
                'availableLocales' => ['en'],
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [],
            ],
            [
                'stage' => 'live',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN New',
                    'images' => null,
                ],
            ],
        ], $data);

        return $id;
    }

    /**
     * @depends testPostRepublishEn
     */
    public function testPostRepublishEnAgain(int $id): int
    {
        $this->client->request('PUT', '/admin/api/examples/' . $id . '?locale=en&action=publish', [], [], [], \json_encode([
            'template' => 'example-2',
            'title' => 'Test EN New 2',
            'url' => '/test-en',
        ]) ?: null);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $content = \json_decode((string) $response->getContent(), true);
        /** @var int $id */
        $id = $content['id'] ?? null; // @phpstan-ignore-line

        $data = $this->getDimensionContent($id);

        $this->assertSame([
            [
                'stage' => 'draft',
                'locale' => null,
                'availableLocales' => ['en', 'de'],
                'shadowLocale' => null,
                'shadowLocales' => ['de' => 'en'],
                'templateData' => [],
            ],
            [
                'stage' => 'draft',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN New 2',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'draft',
                'locale' => 'de',
                'availableLocales' => null,
                'shadowLocale' => 'en',
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-de',
                    'title' => 'Test DE',
                    'images' => null,
                ],
            ],
            [
                'stage' => 'live',
                'locale' => null,
                'availableLocales' => ['en'],
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [],
            ],
            [
                'stage' => 'live',
                'locale' => 'en',
                'availableLocales' => null,
                'shadowLocale' => null,
                'shadowLocales' => null,
                'templateData' => [
                    'url' => '/test-en',
                    'title' => 'Test EN New 2',
                    'images' => null,
                ],
            ],
        ], $data);

        return $id;
    }

    /**
     * @return mixed[]
     */
    private function getDimensionContent(int $id): array
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->getContainer()->get(EntityManagerInterface::class);
        $queryBuilder = $entityManager->createQueryBuilder()
            ->from(ExampleDimensionContent::class, 'dimensionContent')
            ->select('dimensionContent.stage')
            ->addSelect('dimensionContent.locale')
            ->addSelect('dimensionContent.availableLocales')
            ->addSelect('dimensionContent.shadowLocale')
            ->addSelect('dimensionContent.shadowLocales')
            ->addSelect('dimensionContent.templateData')
            ->where('IDENTITY(dimensionContent.example) = :id')
            ->orderBy('dimensionContent.id')
            ->setParameter('id', $id);

        return $queryBuilder->getQuery()->getArrayResult();
    }
}
