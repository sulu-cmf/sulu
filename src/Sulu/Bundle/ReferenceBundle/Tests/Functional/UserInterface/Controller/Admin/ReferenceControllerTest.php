<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Tests\Functional\UserInterface\Controller\Admin;

use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ReferenceControllerTest extends SuluTestCase
{
    public function testCgetActionEmpty(): void
    {
        $client = $this->createAuthenticatedClient();

        self::purgeDatabase();

        $client->request('GET', '/admin/api/references?resourceKey=media&resourceId=1');

        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $json */
        $json = \json_decode($response->getContent() ?: '', true, \JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                '_embedded' => [
                    'references' => [],
                ],
                'limit' => 10,
                'total' => 0,
                'page' => 1,
                'pages' => 1,
            ],
            $json
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataCgetActionFiltersAndFields')]
    public function testCgetActionFiltersAndFields(string $url): void
    {
        $client = $this->createAuthenticatedClient();

        self::purgeDatabase();

        $this->createReference('media', '1', 'pages', '123-123');
        $reference2 = $this->createReference('media', '1', 'pages', '123-123');
        $reference2->setReferenceContext('excerpt');
        $reference2->setReferenceProperty('icon');
        $this->createReference('media', '1', 'pages', '456-789');
        $this->createReference('media', '2', 'pages', '890-123');

        self::getEntityManager()->flush();

        // represents a real request of the admin list UI
        $client->request('GET', $url);
        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $json */
        $json = \json_decode($response->getContent() ?: '', true, \JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                '_embedded' => [
                    'references' => [
                        [
                            'referenceTitle' => 'Title',
                            'referenceLocale' => 'en',
                            'referenceResourceKey' => 'pages',
                            'referenceResourceId' => '123-123',
                            'referenceRouterAttributes' => ['webspace' => 'sulu'],
                            'id' => 'pages__123-123__en',
                            'hasChildren' => true,
                        ],
                        [
                            'referenceTitle' => 'Title',
                            'referenceLocale' => 'en',
                            'referenceResourceKey' => 'pages',
                            'referenceResourceId' => '456-789',
                            'referenceRouterAttributes' => ['webspace' => 'sulu'],
                            'id' => 'pages__456-789__en',
                            'hasChildren' => true,
                        ],
                    ],
                ],
                'limit' => 10,
                'total' => 2,
                'page' => 1,
                'pages' => 1,
            ],
            $json,
        );
    }

    public static function dataCgetActionFiltersAndFields(): \Generator
    {
        yield 'no sorting' => [
            '/admin/api/references?resourceKey=media&resourceId=1&fields=referenceTitle,referenceLocale,referenceResourceKey,referenceContext,referenceProperty,id',
        ];

        yield 'non existing sorting' => [
            '/admin/api/references?resourceKey=media&resourceId=1&fields=referenceTitle,referenceLocale,referenceResourceKey,referenceContext,referenceProperty,id&sortBy=does_not_exist&sortOrder=asc',
        ];
    }

    public function testCgetActionChild(): void
    {
        $client = $this->createAuthenticatedClient();

        self::purgeDatabase();

        $this->createReference('media', '1', 'pages', '123-123');
        $reference2 = $this->createReference('media', '1', 'pages', '123-123');
        $reference2->setReferenceContext('excerpt');
        $reference2->setReferenceProperty('icon');
        $this->createReference('media', '1', 'pages', '456-789');

        self::getEntityManager()->flush();

        // represents a real request of the admin list UI
        $client->request('GET', '/admin/api/references?resourceKey=media&resourceId=1&fields=referenceTitle,referenceLocale,referenceResourceKey,referenceContext,referenceProperty,id');
        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $json */
        $json = \json_decode($response->getContent() ?: '', true, \JSON_THROW_ON_ERROR);

        $this->assertTrue(isset($json['_embedded']));
        $this->assertIsArray($json['_embedded']);
        $this->assertTrue(isset($json['_embedded']['references']));
        $this->assertIsArray($json['_embedded']['references']);
        $this->assertTrue(isset($json['_embedded']['references'][0]['id']));
        $parentId = $json['_embedded']['references'][0]['id'];
        $this->assertIsString($parentId);

        $client->request('GET', '/admin/api/references?resourceKey=media&resourceId=1&fields=referenceTitle,referenceLocale,referenceResourceKey,referenceContext,referenceProperty,id&parentId=' . $parentId);
        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $json */
        $json = \json_decode($response->getContent() ?: '', true, \JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                '_embedded' => [
                    'references' => [
                        [
                            'referenceContext' => 'default',
                            'referenceProperty' => 'image',
                            'referenceRouterAttributes' => ['webspace' => 'sulu'],
                            'id' => 'pages__123-123__en__1',
                        ],
                        [
                            'referenceContext' => 'excerpt',
                            'referenceProperty' => 'icon',
                            'referenceRouterAttributes' => ['webspace' => 'sulu'],
                            'id' => 'pages__123-123__en__2',
                        ],
                    ],
                ],
            ],
            $json,
        );
    }

    private function createReference(
        string $resourceKey,
        string $resourceId,
        string $referenceResourceKey,
        string $referenceResourceId,
    ): Reference {
        $reference = new Reference();
        $reference->setResourceKey($resourceKey);
        $reference->setResourceId($resourceId);
        $reference->setReferenceResourceKey($referenceResourceKey);
        $reference->setReferenceResourceId($referenceResourceId);
        $reference->setReferenceLocale('en');
        $reference->setReferenceTitle('Title');
        $reference->setReferenceProperty('image');
        $reference->setReferenceContext('default');
        $reference->setReferenceRouterAttributes(['webspace' => 'sulu']);

        self::getEntityManager()->persist($reference);

        return $reference;
    }
}
