<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ObjectRepository;
use PHPCR\SessionInterface;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class SnippetControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    protected $client;

    /**
     * @var SnippetDocument
     */
    protected $hotel1;

    /**
     * @var SnippetDocument
     */
    protected $hotel2;

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var SessionInterface
     */
    private $phpcrSession;

    /**
     * @var DefaultSnippetManagerInterface
     */
    private $defaultSnippetManager;

    /**
     * @var ObjectRepository<ActivityInterface>
     */
    private $activityRepository;

    /**
     * @var ObjectRepository<TrashItemInterface>
     */
    private $trashItemRepository;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $this->initPhpcr();
        $this->phpcrSession = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->defaultSnippetManager = $this->getContainer()->get('sulu_snippet.default_snippet.manager');
        $this->activityRepository = $this->getEntityManager()->getRepository(ActivityInterface::class);
        $this->trashItemRepository = $this->getEntityManager()->getRepository(TrashItemInterface::class);
        $this->loadFixtures();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideGet')]
    public function testGet($locale, $expected): void
    {
        $this->client->jsonRequest('GET', '/api/snippets/' . $this->hotel1->getUuid() . '?locale=' . $locale);
        $response = $this->client->getResponse();

        $result = \json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);

        $this->assertEquals($expected['title'], $result['title']);
        $this->assertEquals($expected['description'], $result['description']);
        $this->assertEquals($this->hotel1->getUuid(), $result['id']);
    }

    public static function provideGet()
    {
        return [
            [
                'de',
                [
                    'title' => 'Das Großes Budapest',
                    'description' => 'Hallo Weld!',
                ],
            ],
            [
                'en',
                [
                    'title' => 'The Grand Budapest',
                    'description' => 'Hello World',
                ],
            ],
            [
                'nl',
                [
                    'title' => '',
                    'description' => '',
                ],
            ],
        ];
    }

    public function testGetMany(): void
    {
        $this->client->jsonRequest('GET', \sprintf(
            '/api/snippets?ids=%s,%s%s',
            $this->hotel1->getUuid(),
            $this->hotel2->getUuid(),
            '&locale=de'
        ));
        $response = $this->client->getResponse();

        $result = \json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);

        $this->assertCount(2, $result['_embedded']['snippets']);

        $result = \reset($result['_embedded']['snippets']);
        $this->assertEquals('Das Großes Budapest', $result['title']);
        $this->assertEquals($this->hotel1->getUuid(), $result['id']);
        $this->assertEquals('Hotel', $result['localizedTemplate']);
    }

    public function testGetManyWithGhosts(): void
    {
        $this->client->jsonRequest('GET', \sprintf(
            '/api/snippets?ids=%s,%s&locale=en',
            $this->hotel1->getUuid(),
            $this->hotel2->getUuid()
        ));

        $response = $this->client->getResponse();

        $result = \json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);

        $this->assertCount(2, $result['_embedded']['snippets']);

        $results = $result['_embedded']['snippets'];
        $this->assertEquals('The Grand Budapest', $results[0]['title']);
        $this->assertArrayNotHasKey('ghostLocale', $results[0]);
        $this->assertEquals('L\'Hôtel New Hampshire', $results[1]['title']);
        $this->assertEquals('de', $results[1]['ghostLocale']);
    }

    public function testGetManyLocalizedTemplate(): void
    {
        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/snippets?ids=%s&locale=fr',
                $this->hotel1->getUuid()
            )
        );
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = \json_decode($response->getContent(), true);
        $result = \reset($result['_embedded']['snippets']);
        $this->assertEquals($this->hotel1->getUuid(), $result['id']);
        $this->assertEquals('Hotel', $result['localizedTemplate']);
    }

    public function testGetMultipleWithNotExistingIds(): void
    {
        $this->client->jsonRequest('GET', \sprintf(
            '/api/snippets?ids=%s,%s,%s%s',
            $this->hotel1->getUuid(),
            '99999999-754c-4da0-bbc7-bf909b05c352',
            $this->hotel2->getUuid(),
            '&locale=de'
        ));
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = \json_decode($response->getContent(), true);
        $result = $result['_embedded']['snippets'];
        $this->assertCount(2, $result);
        $result = \reset($result);
        $this->assertEquals('Das Großes Budapest', $result['title']);
        $this->assertEquals($this->hotel1->getUuid(), $result['id']);
    }

    public static function provideIndex()
    {
        return [
            [
                [],
                6,
            ],
            [
                [
                    'types' => 'car',
                ],
                4,
            ],
            [
                [
                    'areas' => 'car',
                ],
                4,
            ],
            [
                [
                    'areas' => 'golf_hotel',
                ],
                2,
            ],
            [
                [
                    'areas' => 'sport_hotel',
                ],
                2,
            ],
            [
                [
                    'types' => 'hotel',
                ],
                2,
            ],
            [
                [
                    'limit' => 2,
                    'page' => 1,
                ],
                2,
            ],
            [
                [
                    'limit' => 2,
                    'page' => 2,
                ],
                2,
            ],
            [
                [
                    'limit' => 2,
                    'page' => 3,
                ],
                2,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideIndex')]
    public function testIndex($params, $expectedResultCount): void
    {
        $params = \array_merge([
            'locale' => 'de',
        ], $params);

        $query = \http_build_query($params);
        $this->client->jsonRequest('GET', '/api/snippets?' . $query);
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);

        $result = \json_decode($response->getContent(), true);
        $this->assertCount($expectedResultCount, $result['_embedded']['snippets']);

        foreach ($result['_embedded']['snippets'] as $snippet) {
            // check if all snippets have a title, even if it is a ghost page
            $this->assertArrayHasKey('title', $snippet);
        }
    }

    public function testIndexWithFields(): void
    {
        $fields = ['id', 'title', 'path'];
        $this->client->jsonRequest('GET', '/api/snippets?locale=de&fields=' . \implode(',', $fields));
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);

        $result = \json_decode($response->getContent(), true);
        $this->assertIsArray($result);

        /** @array array<int, array<string, mixed>> $snippetData */
        $snippetData = $result['_embedded']['snippets'];

        foreach ($snippetData as $snippet) {
            foreach ($fields as $field) {
                $this->assertArrayHasKey($field, $snippet);
            }
            $this->assertArrayNotHasKey('description', $snippet);
        }
    }

    public static function providePost()
    {
        return [
            [
                [],
                [
                    'template' => 'car',
                    'data' => 'My New Car',
                ],
            ],
            [
                [
                    'locale' => 'en',
                ],
                [
                    'template' => 'hotel',
                    'data' => 'Some Hotel Yeah',
                ],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePost')]
    public function testPost($params, $data): void
    {
        $params = \array_merge([
            'locale' => 'de',
        ], $params);

        $data = [
            'template' => 'car',
            'title' => 'My New Car',
            'description' => 'My car is red.',
        ];

        $query = \http_build_query($params);
        $this->client->jsonRequest('POST', '/api/snippets?' . $query, $data);
        $response = $this->client->getResponse();

        $result = \json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);

        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($params['locale'], \reset($result['contentLocales']));
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $result['nodeState']);
        $this->assertEquals('My car is red.', $result['description']);

        try {
            $this->documentManager->find($result['id'], 'de');
        } catch (DocumentNotFoundException $e) {
            $this->fail('Document was not persisted');
        }

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'created']);
        $this->assertNotNull($activity);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePost')]
    public function testPostPublished($params, $data): void
    {
        $params = \array_merge([
            'locale' => 'de',
            'state' => StructureInterface::STATE_PUBLISHED,
        ], $params);

        $data = [
            'template' => 'car',
            'title' => 'My New Car',
        ];

        $query = \http_build_query($params);
        $this->client->jsonRequest('POST', '/api/snippets?' . $query, $data);
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = \json_decode($response->getContent(), true);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($params['locale'], \reset($result['contentLocales']));
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $result['nodeState']);
    }

    public function testPut(): void
    {
        $data = [
            'template' => 'hotel',
            'title' => 'Renamed Hotel',
            'description' => 'My hotel is red',
        ];

        $params = [
            'locale' => 'de',
        ];

        $query = \http_build_query($params);
        $this->client->jsonRequest('PUT', \sprintf('/api/snippets/%s?%s', $this->hotel1->getUuid(), $query), $data);
        $response = $this->client->getResponse();

        $result = \json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);

        foreach ($data as $key => $value) {
            $this->assertEquals($data[$key], $value);
        }

        $this->assertContains($params['locale'], $result['contentLocales']);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $result['nodeState']);

        $document = $this->documentManager->find($result['id'], 'de');

        $this->assertEquals($data['title'], $document->getTitle());
        $this->assertEquals($data['description'], $document->getStructure()->getProperty('description')->getValue());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'modified']);
        $this->assertSame((string) $this->hotel1->getUuid(), $activity->getResourceId());
    }

    public function testPutPublished(): void
    {
        $data = [];
        $params = [
            'locale' => 'de',
            'state' => StructureInterface::STATE_PUBLISHED,
        ];

        $query = \http_build_query($params);
        $this->client->jsonRequest('PUT', \sprintf('/api/snippets/%s?%s', $this->hotel1->getUuid(), $query), $data);
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = \json_decode($response->getContent(), true);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $result[$key]);
        }

        $this->assertContains($params['locale'], $result['contentLocales']);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $result['nodeState']);
    }

    public function testPutWithValidHash(): void
    {
        $data = [
            'template' => 'car',
            'title' => 'My New Car',
            'description' => 'My car is red.',
        ];

        $this->client->jsonRequest('POST', '/api/snippets?locale=de', $data);
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = \json_decode($response->getContent(), true);

        $this->client->jsonRequest(
            'PUT',
            '/api/snippets/' . $result['id'] . '?locale=de',
            \array_merge(['_hash' => $result['_hash']], $data)
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testPutWithInvalidHash(): void
    {
        $data = [
            'template' => 'car',
            'title' => 'My New Car',
            'description' => 'My car is red.',
        ];

        $this->client->jsonRequest('POST', '/api/snippets?locale=de', $data);
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = \json_decode($response->getContent(), true);

        $this->client->jsonRequest(
            'PUT',
            '/api/snippets/' . $result['id'] . '?locale=de',
            \array_merge(['_hash' => 'wrong-hash'], $data)
        );

        $this->assertHttpStatusCode(409, $this->client->getResponse());
    }

    public function testPutWithExcerpt(): void
    {
        $data = [
            'template' => 'hotel',
            'title' => 'Renamed Hotel',
            'description' => 'My hotel is red',
            'ext' => [
                'excerpt' => [
                    'title' => 'Magnificient hotel',
                ],
            ],
        ];

        $params = [
            'locale' => 'de',
        ];

        $query = \http_build_query($params);
        $this->client->jsonRequest('PUT', \sprintf('/api/snippets/%s?%s', $this->hotel1->getUuid(), $query), $data);
        $response = $this->client->getResponse();

        $result = \json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);

        $this->assertEquals('Magnificient hotel', $result['ext']['excerpt']['title']);

        $document = $this->documentManager->find($result['id'], 'de');

        $this->assertEquals('Magnificient hotel', $document->getExtensionsData()['excerpt']['title']);
        $this->assertEquals($data['title'], $document->getTitle());
        $this->assertEquals($data['description'], $document->getStructure()->getProperty('description')->getValue());
    }

    public function testDeleteReferenced(): void
    {
        $page = $this->documentManager->create('page');
        $page->setStructureType('hotel_page');
        $page->setTitle('Hotels page');
        $page->setResourceSegment('/hotels');
        $page->getStructure()->bind(['hotels' => [$this->hotel1->getUuid(), $this->hotel2->getUuid()]]);
        $this->documentManager->persist($page, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->defaultSnippetManager->save('sulu_io', 'sport_hotel', $this->hotel1->getUuid(), 'en');

        $this->client->jsonRequest('DELETE', '/api/snippets/' . $this->hotel1->getUuid());

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(409, $response);

        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('errors', $content);
        unset($content['errors']);

        $this->assertEquals([
            'code' => 1106,
            'message' => 'Found 2 referencing resources.',
            'resource' => [
                'id' => $this->hotel1->getUuid(),
                'resourceKey' => 'snippets',
            ],
            'referencingResourcesCount' => 2,
            'referencingResources' => [
                [
                    'id' => $page->getUuid(),
                    'resourceKey' => 'pages',
                    'title' => 'Hotels page',
                ],
                [
                    'id' => 'sulu_io',
                    'resourceKey' => 'webspaces',
                    'title' => 'sulu.io default snippet',
                ],
            ],
        ], $content);

        static::assertCount(0, $this->trashItemRepository->findAll());

        $this->client->jsonRequest('DELETE', '/api/snippets/' . $this->hotel1->getUuid() . '?force=true');
        $response = $this->client->getResponse();
        $content = \json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'removed']);
        $this->assertSame((string) $this->hotel1->getUuid(), $activity->getResourceId());

        /** @var TrashItemInterface[] $trashItems */
        $trashItems = $this->trashItemRepository->findAll();
        static::assertCount(1, $trashItems);
        static::assertSame(SnippetDocument::RESOURCE_KEY, $trashItems[0]->getResourceKey());
    }

    public function testCopyLocale(): void
    {
        $snippet = $this->documentManager->create('snippet');
        $snippet->setStructureType('hotel');
        $snippet->setTitle('Hotel title DE');
        $snippet->getStructure()->bind(['description' => 'Hotel description DE']);

        $this->documentManager->persist($snippet, 'de');
        $this->documentManager->publish($snippet, 'de');
        $this->documentManager->flush();

        $this->client->jsonRequest('POST', '/api/snippets/' . $snippet->getUuid() . '?action=copy-locale&dest=en&locale=de');
        $this->documentManager->clear();
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $content = \json_decode($response->getContent(), true);
        $this->assertEquals('Hotel title DE', $content['title']);
        $this->assertEquals('Hotel description DE', $content['description']);

        /** @var SnippetDocument $newPage */
        $newPage = $this->documentManager->find($snippet->getUuid(), 'en');
        $this->assertEquals(WorkflowStage::PUBLISHED, $newPage->getWorkflowStage());
        $this->assertEquals('Hotel title DE', $newPage->getTitle());
        $this->assertEquals('Hotel description DE', $newPage->getStructure()->getProperty('description')->getValue());

        $newPage = $this->documentManager->find($snippet->getUuid(), 'de');
        $this->assertEquals(WorkflowStage::PUBLISHED, $newPage->getWorkflowStage());
        $this->assertEquals('Hotel title DE', $newPage->getTitle());
        $this->assertEquals('Hotel description DE', $newPage->getStructure()->getProperty('description')->getValue());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'translation_copied']);
        $this->assertSame((string) $snippet->getUuid(), $activity->getResourceId());
    }

    public function testCopyLocaleWithSource(): void
    {
        $snippet = $this->documentManager->create('snippet');
        $snippet->setStructureType('hotel');
        $snippet->setTitle('Hotel title DE');
        $snippet->getStructure()->bind(['description' => 'Hotel description DE']);

        $this->documentManager->persist($snippet, 'de');
        $this->documentManager->publish($snippet, 'de');
        $this->documentManager->flush();

        $this->client->jsonRequest('POST', '/api/snippets/' . $snippet->getUuid() . '?action=copy-locale&dest=en&locale=en&src=de');
        $this->documentManager->clear();
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $content = \json_decode($response->getContent(), true);
        $this->assertEquals('Hotel title DE', $content['title']);
        $this->assertEquals('Hotel description DE', $content['description']);
        $this->assertEquals('en', $content['locale']);

        /** @var SnippetDocument $newPage */
        $newPage = $this->documentManager->find($snippet->getUuid(), 'en');
        $this->assertEquals(WorkflowStage::PUBLISHED, $newPage->getWorkflowStage());
        $this->assertEquals('Hotel title DE', $newPage->getTitle());
        $this->assertEquals('Hotel description DE', $newPage->getStructure()->getProperty('description')->getValue());

        $newPage = $this->documentManager->find($snippet->getUuid(), 'de');
        $this->assertEquals(WorkflowStage::PUBLISHED, $newPage->getWorkflowStage());
        $this->assertEquals('Hotel title DE', $newPage->getTitle());
        $this->assertEquals('Hotel description DE', $newPage->getStructure()->getProperty('description')->getValue());
    }

    public function testCopy(): void
    {
        $params = [
            'locale' => 'de',
            'action' => 'copy',
        ];

        $query = \http_build_query($params);
        $this->client->jsonRequest('POST', \sprintf('/api/snippets/%s?%s', $this->hotel1->getUuid(), $query));
        $response = $this->client->getResponse();

        $result = \json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);

        $this->assertNotSame($this->hotel1->getUuid(), $result['id']);

        $document = $this->documentManager->find($result['id'], 'de');

        $this->assertEquals($this->hotel1->getTitle(), $document->getTitle());
    }

    private function loadFixtures(): void
    {
        // HOTELS
        $this->hotel1 = $this->documentManager->create('snippet');
        $this->hotel1->setStructureType('hotel');
        $this->hotel1->setTitle('The Grand Budapest');
        $this->hotel1->getStructure()->getProperty('description')->setValue('Hello World');
        $this->documentManager->persist($this->hotel1, 'en');

        $this->hotel1->getStructure()->getProperty('description')->setValue('Hallo Weld!');
        $this->hotel1->setTitle('Das Großes Budapest');
        $this->documentManager->persist($this->hotel1, 'de');

        $this->hotel2 = $this->documentManager->create('snippet');
        $this->hotel2->setStructureType('hotel');
        $this->hotel2->setTitle('L\'Hôtel New Hampshire');
        $this->documentManager->persist($this->hotel2, 'de');

        // CARS
        $car = $this->documentManager->create('snippet');
        $car->setStructureType('car');
        $car->setTitle('Skoda');
        $this->documentManager->persist($car, 'de');

        $car = $this->documentManager->create('snippet');
        $car->setStructureType('car');
        $car->setTitle('Volvo');
        $this->documentManager->persist($car, 'de');

        $car = $this->documentManager->create('snippet');
        $car->setStructureType('car');
        $car->setTitle('Ford');
        $this->documentManager->persist($car, 'de');

        $car = $this->documentManager->create('snippet');
        $car->setStructureType('car');
        $car->setTitle('VW');
        $this->documentManager->persist($car, 'en');

        $this->documentManager->flush();
        $this->documentManager->clear();
    }
}
