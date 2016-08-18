<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Massive\Bundle\SearchBundle\Search\SearchManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\BrowserKit\Client;

class SearchControllerTest extends SuluTestCase
{
    /**
     * @var SearchManager
     */
    private $searchManager;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var User
     */
    private $user;

    public function setUp()
    {
        parent::setUp();
        $this->purgeDatabase();
        $this->client = $this->createAuthenticatedClient();
        $this->searchManager = $this->client->getContainer()->get('massive_search.search_manager');
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->createUser();
        $this->indexProducts();
    }

    public function provideSearch()
    {
        return [
            [
                [
                    'q' => 'Hello',
                    'locale' => 'de',
                ],
                [
                    'page' => 1,
                    'limit' => 10,
                    'pages' => 1,
                    '_embedded' => [
                        'result' => [
                        ],
                    ],
                    'totals' => [
                        'product' => 0,
                        'contact' => 0,
                    ],
                    'total' => 0,
                ],
            ],
            [
                [
                    'q' => 'Product',
                    'indexes' => ['Product'],
                    'locale' => 'fr',
                ],
                [
                    'page' => 1,
                    'limit' => 10,
                    'pages' => 1,
                    '_embedded' => [
                        'result' => [
                            [
                                'id' => null,
                                'document' => [
                                    'id' => 6,
                                    'title' => 'Product Xeon',
                                    'description' => 'To be or not to be, that is the question',
                                    'url' => '/foobar',
                                    'locale' => 'fr',
                                    'imageUrl' => null,
                                    'index' => 'product',
                                    'created' => '2015-04-10T00:00:00+00:00',
                                    'changed' => '2015-04-12T00:00:00+00:00',
                                    'creatorName' => 'dantleech',
                                    'changerName' => 'dantleech',
                                    'properties' => [
                                        'title' => 'Product Xeon',
                                        'body' => 'To be or not to be, that is the question',
                                    ],
                                ],
                                'score' => -1,
                            ],
                        ],
                    ],
                    'totals' => [
                        'product' => 1,
                        'contact' => 0,
                    ],
                    'total' => 1,
                ],
            ],
            [
                [
                    'q' => 'Xeon',
                    'limit' => 1,
                    'page' => 2,
                ],
                [
                    'page' => 2,
                    'limit' => 1,
                    'pages' => 2,
                    '_embedded' => [
                        'result' => [
                            [
                                'id' => null,
                                'document' => [
                                    'id' => 7,
                                    'title' => 'Car Xeon',
                                    'description' => 'To be or not to be, that is the question',
                                    'url' => '/foobar',
                                    'locale' => 'fr',
                                    'imageUrl' => null,
                                    'index' => 'product',
                                    'created' => '2015-04-10T00:00:00+00:00',
                                    'changed' => '2015-04-12T00:00:00+00:00',
                                    'creatorName' => 'dantleech',
                                    'changerName' => 'dantleech',
                                    'properties' => [
                                        'title' => 'Car Xeon',
                                        'body' => 'To be or not to be, that is the question',
                                    ],
                                ],
                                'score' => -1,
                            ],
                        ],
                    ],
                    'totals' => [
                        'product' => 2,
                        'contact' => 0,
                    ],
                    'total' => 2,
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideSearch
     */
    public function testSearch($params, $expectedResult)
    {
        foreach ($expectedResult['_embedded']['result'] as &$hitResult) {
            $hitResult['document']['creatorId'] = $this->user->getId();
            $hitResult['document']['changerId'] = $this->user->getId();
        }

        $this->client->request('GET', '/search/query', $params);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $result = json_decode($response->getContent(), true);
        unset($result['_links']);

        $this->assertArrayHasKey('time', $result);
        unset($result['time']);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetIndexes()
    {
        $this->client->request('GET', '/search/indexes');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $result = json_decode($response->getContent(), true);

        $this->assertEquals('product', $result[0]['indexName']);
        $this->assertEquals([], $result[0]['contexts']);
    }

    private function createUser()
    {
        $user = new User();
        $user->setUsername('dantleech');
        $user->setPassword('mypassword');
        $user->setLocale('en');
        $user->setSalt('12345');
        $contact = new Contact();
        $contact->setFirstName('Daniel');
        $contact->setLastName('Leech');
        $user->setContact($contact);
        $this->entityManager->persist($contact);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->user = $user;
    }

    private function indexProducts()
    {
        $product = new Product();
        $product->id = 6;
        $product->title = 'Product Xeon';
        $product->body = 'To be or not to be, that is the question';
        $product->url = '/foobar';
        $product->locale = 'fr';
        $product->created = new \DateTime('2015-04-10T00:00:00+00:00');
        $product->changed = new \DateTime('2015-04-12T00:00:00+00:00');
        $product->changer = $this->user;
        $product->creator = $this->user;

        $this->searchManager->index($product);

        $product = new Product();
        $product->id = 7;
        $product->title = 'Car Xeon';
        $product->body = 'To be or not to be, that is the question';
        $product->url = '/foobar';
        $product->locale = 'fr';
        $product->created = new \DateTime('2015-04-10T00:00:00+00:00');
        $product->changed = new \DateTime('2015-04-12T00:00:00+00:00');
        $product->changer = $this->user;
        $product->creator = $this->user;

        $this->searchManager->index($product);
    }
}
