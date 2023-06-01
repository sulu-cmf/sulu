<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class RouteControllerTest extends SuluTestCase
{
    public const TEST_ENTITY = 'AppBundle\\Entity\\Test';

    public const TEST_RESOURCE_KEY = 'tests';

    public const TEST_ID = 1;

    public const TEST_LOCALE = 'de';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->entityManager = $this->getEntityManager();
        $this->purgeDatabase();
    }

    public function testGenerate(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/routes?action=generate',
            [
                'locale' => self::TEST_LOCALE,
                'resourceKey' => self::TEST_RESOURCE_KEY,
                'parts' => [
                    'title' => 'test',
                    'year' => '2019',
                ],
            ]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('/prefix/2019/test', $result['resourcelocator']);
    }

    public function testGenerateWithConfigFromParameter(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/routes?action=generate',
            [
                'locale' => self::TEST_LOCALE,
                'resourceKey' => 'resource-key-without-route-bundle-config',
                'entityClass' => 'some-entity-class',
                'routeSchema' => '/{object["year"]}/custom-part/{object["title"]}',
                'parts' => [
                    'title' => 'test',
                    'year' => '2019',
                ],
            ]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('/2019/custom-part/test', $result['resourcelocator']);
    }

    public function testGenerateWithTranslationInSchema(): void
    {
        // test english translation
        $this->client->jsonRequest(
            'POST',
            '/api/routes?action=generate',
            [
                'locale' => 'en',
                'resourceKey' => 'event-resource-key',
                'entityClass' => 'event-class',
                'routeSchema' => '/{translator.trans("app.event")}/{object["title"]}',
                'parts' => [
                    'title' => 'Tomorrowland',
                ],
            ]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('/event/tomorrowland', $result['resourcelocator']);

        // test german translation
        $this->client->jsonRequest(
            'POST',
            '/api/routes?action=generate',
            [
                'locale' => 'de',
                'resourceKey' => 'event-resource-key',
                'entityClass' => 'event-class',
                'routeSchema' => '/{translator.trans("app.event")}/{object["title"]}',
                'parts' => [
                    'title' => 'Tomorrowland',
                ],
            ]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('/veranstaltung/tomorrowland', $result['resourcelocator']);
    }

    public function testGenerateWithConflict(): void
    {
        $this->createRoute('/prefix/2019/test');
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->request(
            'POST',
            '/api/routes?action=generate',
            [
                'locale' => self::TEST_LOCALE,
                'resourceKey' => self::TEST_RESOURCE_KEY,
                'parts' => [
                    'title' => 'test',
                    'year' => '2019',
                ],
            ]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('/prefix/2019/test-1', $result['resourcelocator']);
    }

    public function testGenerateWithConflictSameEntity(): void
    {
        $this->createRoute('/prefix/2019/test');
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->request(
            'POST',
            '/api/routes?action=generate',
            [
                'locale' => self::TEST_LOCALE,
                'resourceKey' => self::TEST_RESOURCE_KEY,
                'id' => self::TEST_ID,
                'parts' => [
                    'title' => 'test',
                    'year' => '2019',
                ],
            ]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('/prefix/2019/test', $result['resourcelocator']);
    }

    public function testCGetAction(): void
    {
        $routes = [
            $this->createRoute('/test-1'),
            $this->createRoute('/test-2', null, self::TEST_ENTITY, 2),
        ];

        $this->createRoute('/test-1-1', $routes[0]);
        $this->createRoute('/test-2-1', $routes[1]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/routes?resourceKey=%s&id=%s&locale=%s',
                self::TEST_RESOURCE_KEY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertCount(1, $result['_embedded']['routes']);

        $items = $result['_embedded']['routes'];
        $this->assertEquals($routes[0]->getId(), $items[0]['id']);
        $this->assertEquals($routes[0]->getPath(), $items[0]['path']);
    }

    public function testCGetActionWithEntityClassParameter(): void
    {
        $entityRoute1 = $this->createRoute('/test-1', null, 'some-entity-class', 1);
        $this->createRoute('/test-1-1', $entityRoute1, 'some-entity-class', 1);

        $otherRoute1 = $this->createRoute('/test-2');
        $this->createRoute('/test-2-1', $otherRoute1);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/routes?resourceKey=%s&entityClass=%s&id=%s&locale=%s',
                'resource-key-without-route-bundle-config',
                'some-entity-class',
                1,
                self::TEST_LOCALE
            )
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertCount(1, $result['_embedded']['routes']);

        $items = $result['_embedded']['routes'];
        $this->assertEquals($entityRoute1->getId(), $items[0]['id']);
        $this->assertEquals($entityRoute1->getPath(), $items[0]['path']);
    }

    public function testCGetActionNotExistingResourceKey(): void
    {
        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/routes?resourceKey=%s&id=%s&locale=%s',
                'articles',
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCGetActionHistory(): void
    {
        $targetRoute = $this->createRoute('/test');
        $routes = [
            $this->createRoute('/test-2', $targetRoute),
            $this->createRoute('/test-3', $targetRoute),
            $this->createRoute('/test-4', $targetRoute),
        ];

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/routes?history=true&resourceKey=%s&id=%s&locale=%s',
                self::TEST_RESOURCE_KEY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertCount(3, $result['_embedded']['routes']);

        $items = $result['_embedded']['routes'];
        $items = [
            $items[0]['id'] => $items[0],
            $items[1]['id'] => $items[1],
            $items[2]['id'] => $items[2],
        ];

        for ($i = 0; $i < 3; ++$i) {
            $id = $routes[$i]->getId();

            $this->assertEquals($routes[$i]->getId(), $items[$id]['id']);
            $this->assertEquals($routes[$i]->getPath(), $items[$id]['path']);
        }
    }

    public function testDelete(): void
    {
        $targetRoute = $this->createRoute('/test');
        $routes = [
            $this->createRoute('/test-2', $targetRoute),
            $this->createRoute('/test-3', $targetRoute),
            $this->createRoute('/test-4', $targetRoute),
        ];

        $this->entityManager->flush();
        $this->entityManager->clear();

        $targetRouteId = $targetRoute->getId();

        $this->client->jsonRequest('DELETE', '/api/routes?ids=' . $targetRouteId);
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/routes?history=true&resourceKey=%s&id=%s&locale=%s',
                self::TEST_RESOURCE_KEY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );
        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertCount(0, $result['_embedded']['routes']);
    }

    public function testDeleteHistory(): void
    {
        $targetRoute = $this->createRoute('/test');
        $routes = [
            $this->createRoute('/test-2', $targetRoute),
            $this->createRoute('/test-3', $targetRoute),
            $this->createRoute('/test-4', $targetRoute),
        ];

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->client->jsonRequest('DELETE', '/api/routes?ids=' . $routes[0]->getId());
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/routes?history=true&resourceKey=%s&id=%s&locale=%s',
                self::TEST_RESOURCE_KEY,
                self::TEST_ID,
                self::TEST_LOCALE
            )
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertCount(2, $result['_embedded']['routes']);

        $items = $result['_embedded']['routes'];
        $items = [
            $items[0]['id'] => $items[0],
            $items[1]['id'] => $items[1],
        ];

        for ($i = 0; $i < 2; ++$i) {
            $id = $routes[$i + 1]->getId();

            $this->assertEquals($routes[$i + 1]->getId(), $items[$id]['id']);
            $this->assertEquals($routes[$i + 1]->getPath(), $items[$id]['path']);
        }
    }

    private function createRoute(
        $path,
        ?Route $target = null,
        $entityClass = self::TEST_ENTITY,
        $entityId = self::TEST_ID
    ): Route {
        $route = new Route($path, $entityId, $entityClass, self::TEST_LOCALE);
        if ($target) {
            $route->setTarget($target);
            $route->setHistory(true);
        }

        $this->entityManager->persist($route);

        return $route;
    }
}
