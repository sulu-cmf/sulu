<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\RoleRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Component\Cache\CacheInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class CollectionControllerTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Collection
     */
    private $collection1;

    /**
     * @var CollectionType
     */
    private $collectionType1;

    /**
     * @var CollectionType
     */
    private $collectionType2;

    /**
     * @var MediaType
     */
    private $mediaType;

    /**
     * @var CacheInterface
     */
    private $systemCollectionCache;

    /**
     * @var array
     */
    private $systemCollectionConfig;

    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var AccessControlManager
     */
    private $accessControlManager;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();

        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->initOrm();

        $this->systemCollectionCache = $this->getContainer()->get('sulu_media_test.system_collections.cache');
        $this->systemCollectionConfig = $this->getContainer()->getParameter('sulu_media.system_collections');
        $this->roleRepository = $this->getContainer()->get('sulu.repository.role');
        $this->accessControlManager = $this->getContainer()->get('sulu_security.access_control_manager');

        // to be sure that the system collections will rebuild after purge database
        $this->systemCollectionCache->invalidate();
    }

    /**
     * Returns amount of system collections.
     *
     * @param int|null $depth
     *
     * @return int
     */
    protected function getAmountOfSystemCollections($depth = null)
    {
        $amount = 1; // 1 root collection

        if ($depth > 0 || null === $depth) {
            $amount += $this->iterateOverSystemCollections($this->systemCollectionConfig, $depth);
        }

        return $amount;
    }

    /**
     * Loops thru all system collections until reach the depth.
     *
     * @param int|null $depth
     *
     * @return int
     */
    protected function iterateOverSystemCollections($config, $depth = null)
    {
        $amount = \count($config);

        if ($depth > 0 || null === $depth) {
            foreach ($config as $child) {
                if (\array_key_exists('collections', $child)) {
                    $amount += $this->iterateOverSystemCollections($child['collections'], $depth - 1);
                }
            }
        }

        return $amount;
    }

    protected function initOrm()
    {
        // force id = 1
        $metadata = $this->em->getClassMetaData(CollectionType::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $this->collectionType1 = $this->createCollectionType(
            1,
            'collection.default',
            'Default Collection Type',
            'Default Collection Type'
        );
        $this->collectionType2 = $this->createCollectionType(
            2,
            SystemCollectionManagerInterface::COLLECTION_TYPE,
            'System Collections'
        );
        $this->mediaType = $this->createMediaType();
        $this->em->persist($this->mediaType);
        $this->em->persist($this->collectionType1);
        $this->em->persist($this->collectionType2);
        $this->em->flush();

        $this->collection1 = $this->createCollection(
            $this->collectionType1,
            ['en-gb' => 'Test Collection', 'de' => 'Test Kollektion'],
            null,
            null,
            5
        );
    }

    private function createCollection(
        CollectionType $collectionType,
        $title = [],
        $parent = null,
        $key = null,
        $numberOfMedia = 0
    ) {
        // Collection
        $collection = new Collection();

        $style = [
            'type' => 'circle',
            'color' => '#ffcc00',
        ];

        $collection->setStyle(\json_encode($style) ?: null);
        $collection->setKey($key);
        $collection->setType($collectionType);

        // Collection Meta 1
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle(isset($title['en-gb']) ? $title['en-gb'] : 'Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');
        $collectionMeta->setCollection($collection);

        $collection->addMeta($collectionMeta);

        // Collection Meta 2
        $collectionMeta2 = new CollectionMeta();
        $collectionMeta2->setTitle(isset($title['de']) ? $title['de'] : 'Kollection');
        $collectionMeta2->setDescription('Dies ist eine Test Beschreibung');
        $collectionMeta2->setLocale('de');
        $collectionMeta2->setCollection($collection);

        $collection->addMeta($collectionMeta2);

        $collection->setParent($parent);

        $this->em->persist($collection);
        $this->em->persist($collectionType);
        $this->em->persist($collectionMeta);
        $this->em->persist($collectionMeta2);
        $collection->setDefaultMeta($collectionMeta);

        $this->em->flush();

        $this->addMedia($collection, $numberOfMedia);

        return $collection;
    }

    private function addMedia(Collection $collection, $numberOfMedia): void
    {
        for ($i = 0; $i < $numberOfMedia; ++$i) {
            $media = new Media();
            $media->setType($this->mediaType);
            $media->setCollection($collection);
            $collection->addMedia($media);
            $this->em->persist($media);
        }

        $this->em->flush();
    }

    private function createCollectionType($id, $key, $name, $description = '')
    {
        $collectionType = new CollectionType();
        $collectionType->setId($id);
        $collectionType->setName($name);
        $collectionType->setKey($key);
        $collectionType->setDescription($description);

        return $collectionType;
    }

    private function createMediaType()
    {
        $mediaType = new MediaType();
        $mediaType->setName('image');
        $mediaType->setDescription('This is an image');

        return $mediaType;
    }

    private function createRole(string $name = 'Role', string $system = 'Website')
    {
        $role = new Role();
        $role->setName($name);
        $role->setSystem($system);

        $this->em->persist($role);

        return $role;
    }

    private function mapCollections($collections)
    {
        $result = [];
        if (null !== $collections) {
            foreach ($collections as $collection) {
                $result[$collection->title] = [
                    'title' => $collection->title,
                    'parent' => $collection->_embedded->parent ? $collection->_embedded->parent->title : null,
                    'collections' => $this->mapCollections($collection->_embedded->collections),
                ];
            }
            \ksort($result);
        }

        return \array_values($result);
    }

    private function mapCollectionsFlat($collections)
    {
        $result = [];
        foreach ($collections as $collection) {
            $children = [];
            foreach ($collection->_embedded->collections as $child) {
                $children[$child->title] = $child->title;
            }
            \ksort($children);

            $result[$collection->title] = [
                'title' => $collection->title,
                'parent' => $collection->_embedded->parent ? $collection->_embedded->parent->title : null,
                'collections' => \array_values($children),
            ];

            $result = \array_merge($result, $this->mapCollectionsFlat($collection->_embedded->collections));
        }
        \ksort($result);

        return \array_values($result);
    }

    public function testGetById(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/collections/' . $this->collection1->getId(),
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $style = \json_decode(
            \json_encode(
                [
                    'type' => 'circle',
                    'color' => '#ffcc00',
                ]
            ),
            false
        );

        $this->assertEquals($style, $response->style);
        $this->assertEquals('This Description is only for testing', $response->description);
        $this->assertNotNull($response->id);
        $this->assertNull($response->_embedded->collections);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('Test Collection', $response->title);
        $this->assertNotNull($response->type->id);
        $this->assertEquals('Default Collection Type', $response->type->name);
        $this->assertEquals('Default Collection Type', $response->type->description);
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($response->created)));
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($response->changed)));
        $this->assertFalse($response->_hasPermissions);
    }

    public function testCGet(): void
    {
        for ($i = 1; $i <= 15; ++$i) {
            $this->createCollection(
                $this->collectionType1,
                ['en-gb' => 'Test Collection ' . $i, 'de' => 'Test Kollektion ' . $i]
            );
        }

        $this->client->jsonRequest(
            'GET',
            '/api/collections',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response->_embedded->collections);

        $this->assertCount(16, $response->_embedded->collections);
    }

    public function testCGetPaginatedFlat(): void
    {
        for ($i = 1; $i < 8; ++$i) {
            $this->createCollection(
                $this->collectionType1,
                ['en-gb' => 'Test Collection ' . $i, 'de' => 'Test Kollektion ' . $i]
            );
        }

        $this->client->jsonRequest(
            'GET',
            '/api/collections?page=3&limit=3&flat=true',
            [
                'locale' => 'en-gb',
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertInstanceOf(\stdClass::class, $response);
        $this->assertNotEmpty($response->_embedded->collections);

        $this->assertCount(2, $response->_embedded->collections);
        $this->assertSame(8, $response->total);
        $this->assertSame(3, $response->page);
        $this->assertSame(3, $response->pages);
    }

    public function testCGetFlatWithRootParent(): void
    {
        $collection = $this->createCollection($this->collectionType1);

        $this->client->jsonRequest(
            'GET',
            '/api/collections',
            [
                'locale' => 'en-gb',
                'parentId' => 'root',
                'flat' => 'true',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response->_embedded->collections);

        $this->assertCount(2, $response->_embedded->collections);
    }

    public function testCGetFlatWithRootParentAndIncludeRoot(): void
    {
        $collection = $this->createCollection($this->collectionType1);

        $this->client->jsonRequest(
            'GET',
            '/api/collections',
            [
                'locale' => 'en-gb',
                'parentId' => 'root',
                'flat' => 'true',
                'includeRoot' => 'true',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response->_embedded->collections);

        $this->assertCount(2, $response->_embedded->collections);
    }

    public function testCGetFlatWithParent(): void
    {
        $collection = $this->createCollection($this->collectionType1);

        for ($i = 1; $i <= 5; ++$i) {
            $this->createCollection(
                $this->collectionType1,
                ['en-gb' => 'Test Collection ' . $i, 'de' => 'Test Kollektion ' . $i],
                $collection
            );
        }

        $this->client->jsonRequest(
            'GET',
            '/api/collections',
            [
                'locale' => 'en-gb',
                'parentId' => $collection->getId(),
                'flat' => 'true',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response->_embedded->collections);

        $this->assertCount(5, $response->_embedded->collections);
    }

    public function testcGetPaginated(): void
    {
        $this->createCollection(
            $this->collectionType1,
            ['en-gb' => 'Test Collection A', 'de' => 'Test Kollektion A']
        );
        $this->createCollection(
            $this->collectionType1,
            ['en-gb' => 'Test Collection C', 'de' => 'Test Kollektion C']
        );
        $this->createCollection(
            $this->collectionType1,
            ['en-gb' => 'Test Collection B', 'de' => 'Test Kollektion B']
        );

        $this->client->jsonRequest(
            'GET',
            '/api/collections?sortBy=title&page=1&limit=2',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(2, $response->pages);
        $this->assertEquals(1, $response->page);
        $this->assertEquals(2, $response->limit);
        $this->assertEquals(4, $response->total);
        $this->assertNotEmpty($response->_embedded->collections);
        $this->assertCount(2, $response->_embedded->collections);
        $this->assertEquals('Test Collection', $response->_embedded->collections[0]->title);
        $this->assertEquals(5, $response->_embedded->collections[0]->mediaCount);
    }

    public function testcGetPaginatedWithRoot(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/collections?sortBy=title&page=1&limit=2&includeRoot=true',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(1, $response->pages);
        $this->assertEquals(1, $response->page);
        $this->assertEquals(2, $response->limit);
        $this->assertEquals(1, $response->total);
        $this->assertNotEmpty($response->_embedded->collections);
        $this->assertCount(1, $response->_embedded->collections);
        $this->assertEquals('All collections', $response->_embedded->collections[0]->title);
        $this->assertCount(1, $response->_embedded->collections[0]->_embedded->collections);
        $this->assertEquals('Test Collection', $response->_embedded->collections[0]->_embedded->collections[0]->title);
    }

    public function testcGetPaginatedWithParentAndIgnoredRoot(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/collections?sortBy=title&page=1&limit=2&includeRoot=true&parentId=' . $this->collection1->getId(),
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(1, $response->pages);
        $this->assertEquals(1, $response->page);
        $this->assertEquals(2, $response->limit);
        $this->assertEquals(1, $response->total);
        $this->assertNotEmpty($response->_embedded->collections);
        $this->assertCount(1, $response->_embedded->collections);
        $this->assertEquals('Test Collection', $response->_embedded->collections[0]->title);
    }

    public function testcGetPaginatedWithChildren(): void
    {
        $parent = $this->createCollection(
            $this->collectionType1,
            ['en-gb' => 'Test Collection 1', 'de' => 'Test Kollektion 1']
        );
        $this->createCollection(
            $this->collectionType1,
            ['en-gb' => 'Test Collection 2', 'de' => 'Test Kollektion 2']
        );
        $this->createCollection(
            $this->collectionType1,
            ['en-gb' => 'Test Collection child', 'de' => 'Test Kollektion Kind'],
            $parent
        );

        $this->client->jsonRequest(
            'GET',
            '/api/collections?sortBy=title&page=1&limit=2',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(3, $response->total);
        $this->assertEquals(2, \count($response->_embedded->collections));
        $this->assertEquals(5, $response->_embedded->collections[0]->mediaCount);
        $this->assertEquals(0, $response->_embedded->collections[0]->subCollectionCount);
        $this->assertEquals(5, $response->_embedded->collections[0]->objectCount);
        $this->assertEquals(0, $response->_embedded->collections[1]->mediaCount);
        $this->assertEquals(1, $response->_embedded->collections[1]->subCollectionCount);
        $this->assertEquals(1, $response->_embedded->collections[1]->objectCount);
    }

    public function testGetByIdNotExisting(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/collections/10?locale=en'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(5005, $response->code);
        $this->assertObjectHasProperty('message', $response);
    }

    public function testPost(): void
    {
        $generateColor = '#ffcc00';

        $this->assertNotEmpty($generateColor);
        $this->assertEquals(7, \strlen($generateColor));

        $this->client->jsonRequest(
            'POST',
            '/api/collections',
            [
                'locale' => 'en-gb',
                'style' => [
                    'type' => 'circle',
                    'color' => $generateColor,
                ],
                'type' => [
                    'id' => $this->collectionType1->getId(),
                ],
                'title' => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
                'parent' => null,
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = $generateColor;

        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals($style, $response->style);
        $this->assertNotNull($response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($response->created)));
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($response->changed)));
        $this->assertEquals('Test Collection 2', $response->title);
        $this->assertEquals('This Description 2 is only for testing', $response->description);
        /*
        $this->assertNotEmpty($response->creator);
        $this->assertNotEmpty($response->changer);
        */

        $this->client->jsonRequest(
            'GET',
            '/api/collections?flat=true',
            [
                'locale' => 'en-gb',
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(2, $response->total);

        // check if first entity is unchanged
        $this->assertTrue(isset($response->_embedded->collections[0]));
        $responseFirstEntity = $response->_embedded->collections[0];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#ffcc00';

        $this->assertEquals($this->collection1->getId(), $responseFirstEntity->id);
        $this->assertEquals('en-gb', $responseFirstEntity->locale);
        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertEquals($this->collectionType1->getId(), $responseFirstEntity->type->id);
        $this->assertNotEmpty($responseFirstEntity->created);
        $this->assertNotEmpty($responseFirstEntity->changed);
        $this->assertEquals('Test Collection', $responseFirstEntity->title);
        $this->assertEquals('This Description is only for testing', $responseFirstEntity->description);

        // check second entity was created right
        $this->assertTrue(isset($response->_embedded->collections[1]));
        $responseSecondEntity = $response->_embedded->collections[1];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = $generateColor;

        $this->assertNotNull($responseSecondEntity->id);
        $this->assertEquals('en-gb', $responseSecondEntity->locale);
        $this->assertEquals($style, $responseSecondEntity->style);
        $this->assertNotNull($responseSecondEntity->type->id);
        $this->assertNotEmpty($responseSecondEntity->created);
        $this->assertNotEmpty($responseSecondEntity->changed);
        $this->assertEquals('Test Collection 2', $responseSecondEntity->title);
        $this->assertEquals('This Description 2 is only for testing', $responseSecondEntity->description);
    }

    public function testPostNested(): void
    {
        $this->getContainer()->get('sulu_media.system_collections.manager')->warmUp();
        $this->client->getContainer()->get('sulu_media.system_collections.manager')->warmUp();

        $generateColor = '#ffcc00';

        $this->client->jsonRequest(
            'POST',
            '/api/collections',
            [
                'locale' => 'en-gb',
                'style' => [
                    'type' => 'circle',
                    'color' => $generateColor,
                ],
                'type' => [
                    'id' => $this->collectionType1->getId(),
                ],
                'title' => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
                'parent' => $this->collection1->getId(),
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = $generateColor;

        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals($style, $response->style);
        $this->assertNotNull($response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($response->created)));
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($response->changed)));
        $this->assertEquals('Test Collection 2', $response->title);
        $this->assertEquals('This Description 2 is only for testing', $response->description);
        $this->assertEquals($this->collection1->getId(), $response->_embedded->parent->id);
        $this->assertFalse($response->_hasPermissions);

        $this->assertEquals(
            [],
            $this->accessControlManager->getPermissions(Collection::class, $response->id)
        );

        $this->client->jsonRequest(
            'GET',
            '/api/collections/' . $this->collection1->getId() . '?depth=1&children=true',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response);

        // check if first entity is unchanged
        $this->assertTrue(isset($response->_embedded->collections[0]));
        $responseFirstEntity = $response->_embedded->collections[0];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = $generateColor;

        $this->assertNotNull($responseFirstEntity->id);
        $this->assertEquals('en-gb', $responseFirstEntity->locale);
        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertNotNull($responseFirstEntity->type->id);
        $this->assertNotEmpty($responseFirstEntity->created);
        $this->assertNotEmpty($responseFirstEntity->changed);
        $this->assertEquals('Test Collection 2', $responseFirstEntity->title);
        $this->assertEquals('This Description 2 is only for testing', $responseFirstEntity->description);
        $this->assertFalse($response->_hasPermissions);

        $this->client->jsonRequest(
            'GET',
            '/api/collections?flat=true&depth=2',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response);
        $this->assertEquals(2 + $this->getAmountOfSystemCollections(), $response->total);
    }

    public function testPostWithPermissions(): void
    {
        $this->getContainer()->get('sulu_media.system_collections.manager')->warmUp();
        $this->client->getContainer()->get('sulu_media.system_collections.manager')->warmUp();
        $role = $this->createRole();

        $this->em->flush();

        $generateColor = '#ffcc00';

        $permissions = [
            $role->getId() => [
                'view' => true,
                'edit' => true,
                'add' => true,
                'delete' => false,
                'archive' => false,
                'live' => false,
                'security' => false,
            ],
        ];

        $this->accessControlManager->setPermissions(Collection::class, (string) $this->collection1->getId(), $permissions);

        $this->client->request(
            'POST',
            '/api/collections',
            [
                'locale' => 'en-gb',
                'style' => [
                    'type' => 'circle',
                    'color' => $generateColor,
                ],
                'type' => [
                    'id' => $this->collectionType1->getId(),
                ],
                'title' => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
                'parent' => $this->collection1->getId(),
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertTrue($response->_hasPermissions);

        $this->assertEquals(
            $permissions,
            $this->accessControlManager->getPermissions(Collection::class, $response->id)
        );
    }

    public function testPostWithoutDetails(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/collections',
            [
                'locale' => 'en',
                'title' => 'Test Collection 2',
                'type' => [
                    'id' => $this->collectionType1->getId(),
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('en', $response->locale);
        $this->assertNotNull($response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($response->created)));
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($response->changed)));
        $this->assertEquals('Test Collection 2', $response->title);

        // get collection in locale 'en-gb'

        $this->client->jsonRequest(
            'GET',
            '/api/collections',
            [
                'locale' => 'en-gb',
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(2, $response->total);

        // check if first entity is unchanged
        $this->assertTrue(isset($response->_embedded->collections[0]));
        $responseFirstEntity = $response->_embedded->collections[0];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#ffcc00';

        $this->assertEquals($this->collection1->getId(), $responseFirstEntity->id);
        $this->assertEquals('en-gb', $responseFirstEntity->locale);
        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertNotNull($responseFirstEntity->type->id);
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($responseFirstEntity->created)));
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($responseFirstEntity->changed)));
        $this->assertEquals('Test Collection', $responseFirstEntity->title);
        $this->assertEquals('This Description is only for testing', $responseFirstEntity->description);

        // check second entity was created right
        $this->assertTrue(isset($response->_embedded->collections[1]));
        $responseSecondEntity = $response->_embedded->collections[1];

        $this->assertNotNull($responseSecondEntity->id);
        $this->assertEquals('en-gb', $responseSecondEntity->locale);
        $this->assertNotNull($responseSecondEntity->type->id);
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($responseSecondEntity->created)));
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($responseSecondEntity->changed)));
        $this->assertEquals('Test Collection 2', $responseSecondEntity->title);

        // get collection in locale 'en'

        $this->client->jsonRequest(
            'GET',
            '/api/collections',
            [
                'locale' => 'en',
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(2, $response->total);

        // check if first entity is unchanged
        $this->assertTrue(isset($response->_embedded->collections[0]));
        $responseFirstEntity = $response->_embedded->collections[0];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#ffcc00';

        $this->assertNotNull($responseFirstEntity->id);
        $this->assertEquals('en', $responseFirstEntity->locale);
        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertNotNull($responseFirstEntity->type->id);
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($responseFirstEntity->created)));
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($responseFirstEntity->changed)));
        $this->assertEquals('Test Collection', $responseFirstEntity->title);
        $this->assertEquals('This Description is only for testing', $responseFirstEntity->description);

        // check second entity was created right
        $this->assertTrue(isset($response->_embedded->collections[1]));
        $responseSecondEntity = $response->_embedded->collections[1];

        $this->assertNotNull($responseSecondEntity->id);
        $this->assertEquals('en', $responseSecondEntity->locale);
        $this->assertNotNull($responseSecondEntity->type->id);
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($responseSecondEntity->created)));
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($responseSecondEntity->changed)));
        $this->assertEquals('Test Collection 2', $responseSecondEntity->title);
    }

    public function testPostWithNotExistingType(): void
    {
        $generateColor = '#ffcc00';

        $this->assertNotEmpty($generateColor);
        $this->assertEquals(7, \strlen($generateColor));

        $this->client->jsonRequest(
            'POST',
            '/api/collections',
            [
                'style' => [
                    'type' => 'circle',
                    'color' => $generateColor,
                ],
                'type' => [
                    'id' => 91283,
                ],
                'title' => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertObjectHasProperty('message', $response);
    }

    public function testPut(): void
    {
        $this->getContainer()->get('sulu_media.system_collections.manager')->warmUp();
        $this->client->getContainer()->get('sulu_media.system_collections.manager')->warmUp();

        $this->client->jsonRequest(
            'PUT',
            '/api/collections/' . $this->collection1->getId(),
            [
                'style' => [
                    'type' => 'circle',
                    'color' => '#00ccff',
                ],
                'type' => $this->collectionType1->getId(),
                'title' => 'Test Collection changed',
                'description' => 'This Description is only for testing changed',
                'locale' => 'en-gb',
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/collections/' . $this->collection1->getId(),
            [
                'locale' => 'en-gb',
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#00ccff';

        $id = $response->id;

        $this->assertEquals($style, $response->style);
        $this->assertEquals($this->collection1->getId(), $response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($response->created)));
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($response->changed)));
        $this->assertEquals('Test Collection changed', $response->title);
        $this->assertEquals('This Description is only for testing changed', $response->description);
        $this->assertEquals('en-gb', $response->locale);

        $this->client->jsonRequest(
            'GET',
            '/api/collections?locale=en-gb'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(
            1 + $this->getAmountOfSystemCollections(0),
            $response->total
        );

        $this->assertTrue(isset($response->_embedded->collections[0]));
        $this->assertTrue(isset($response->_embedded->collections[1]));
        $responseFirstEntity = $response->_embedded->collections[0];
        if ($responseFirstEntity->id !== $id) {
            $responseFirstEntity = $response->_embedded->collections[1];
        }

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#00ccff';

        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertEquals($this->collection1->getId(), $responseFirstEntity->id);
        $this->assertNotNull($responseFirstEntity->type->id);
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($responseFirstEntity->created)));
        $this->assertEquals(\date('Y-m-d'), \date('Y-m-d', \strtotime($responseFirstEntity->changed)));
        $this->assertEquals('Test Collection changed', $responseFirstEntity->title);
        $this->assertEquals('This Description is only for testing changed', $responseFirstEntity->description);
        $this->assertEquals('en-gb', $responseFirstEntity->locale);
    }

    public function testPutWithoutLocale(): void
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/collections/' . $this->collection1->getId(),
            [
                'style' => [
                    'type' => 'circle',
                    'color' => '#00ccff',
                ],
                'type' => 1,
                'title' => 'Test Collection changed',
                'description' => 'This Description is only for testing changed',
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testPutWithChildCollection(): void
    {
        $childCollection = $this->createCollection(
            $this->collectionType1,
            ['en-gb' => 'Test Child Collection', 'de' => 'Test Kind Kollektion'],
            $this->collection1,
            null,
            5
        );

        $this->client->jsonRequest(
            'PUT',
            '/api/collections/' . $childCollection->getId() . '?breadcrumb=true',
            [
                'style' => [
                    'type' => 'circle',
                    'color' => '#00ccff',
                ],
                'type' => $this->collectionType1->getId(),
                'title' => 'Test Child Collection changed',
                'description' => 'This Description is only for testing changed',
                'locale' => 'en-gb',
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->collection1->getId(), $response->_embedded->parent->id);
        $this->assertEquals($this->collection1->getId(), $response->_embedded->breadcrumb[0]->id);
    }

    public function testPutWithoutBreadcrumb(): void
    {
        $childCollection = $this->createCollection(
            $this->collectionType1,
            ['en-gb' => 'Test Child Collection', 'de' => 'Test Kind Kollektion'],
            $this->collection1,
            null,
            5
        );

        $this->client->jsonRequest(
            'PUT',
            '/api/collections/' . $childCollection->getId(),
            [
                'style' => [
                    'type' => 'circle',
                    'color' => '#00ccff',
                ],
                'type' => $this->collectionType1->getId(),
                'title' => 'Test Child Collection changed',
                'description' => 'This Description is only for testing changed',
                'locale' => 'en-gb',
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNull($response['_embedded']['breadcrumb']);
    }

    public function testPutNoDetails(): void
    {
        // Add New Collection Type
        $collectionType = new CollectionType();
        $collectionType->setId(3);
        $collectionType->setName('Second Collection Type');
        $collectionType->setKey('my-type');
        $collectionType->setDescription('Second Collection Type');

        $this->em->persist($collectionType);
        $this->em->flush();

        // Test put with only details
        $this->client->jsonRequest(
            'PUT',
            '/api/collections/' . $this->collection1->getId(),
            [
                'locale' => 'en',
                'style' => [
                    'type' => 'quader',
                    'color' => '#00ccff',
                ],
                'type' => [
                    'id' => $collectionType->getId(),
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/collections/' . $this->collection1->getId() . '?locale=en-gb'
        );
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $style = new \stdClass();
        $style->type = 'quader';
        $style->color = '#00ccff';

        $this->assertEquals($style, $response->style);

        $this->assertNotNull($response->type->id);
    }

    public function testPutNotExisting(): void
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/collections/404',
            [
                'locale' => 'en',
                'style' => [
                    'type' => 'quader',
                    'color' => '#00ccff',
                ],
                'type' => $this->collectionType1->getId(),
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testDeleteById(): void
    {
        $collection = $this->createCollection($this->collectionType1);
        $collectionId = $collection->getId();

        $this->em->clear();

        $this->client->jsonRequest('DELETE', '/api/collections/' . $collectionId);
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/collections/' . $collectionId . '?locale=en'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(5005, $response->code);
        $this->assertObjectHasProperty('message', $response);

        $trashItemRepository = $this->em->getRepository(TrashItemInterface::class);
        $trashItem = $trashItemRepository->findOneBy(['resourceKey' => 'collections', 'resourceId' => $collectionId]);
        $this->assertNotNull($trashItem);
    }

    public function testDeleteByIdWithChildren(): void
    {
        $collection = $this->createCollection($this->collectionType1);
        $this->addMedia($collection, 1);
        $collectionId = $collection->getId();

        $child1 = $this->createCollection($this->collectionType1, [], $collection);
        $this->addMedia($child1, 1);

        $child11 = $this->createCollection($this->collectionType1, [], $child1);
        $this->addMedia($child11, 1);

        $child2 = $this->createCollection($this->collectionType1, [], $collection);
        $this->addMedia($child2, 1);

        $this->em->clear();

        $this->client->jsonRequest('DELETE', '/api/collections/' . $collectionId);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(409, $response);

        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('errors', $content);
        unset($content['errors']);

        $this->assertEquals([
            'code' => 1105,
            'message' => 'Resource has 7 dependant resources.',
            'resource' => [
                'id' => $collectionId,
                'resourceKey' => 'collections',
            ],
            'dependantResourcesCount' => 7,
            'dependantResourceBatches' => [
                [
                    [
                        'id' => $child11->getMedia()->first()->getId(),
                        'resourceKey' => 'media',
                    ],
                ],
                [
                    [
                        'id' => $child11->getId(),
                        'resourceKey' => 'collections',
                    ],
                    [
                        'id' => $child1->getMedia()->first()->getId(),
                        'resourceKey' => 'media',
                    ],
                    [
                        'id' => $child2->getMedia()->first()->getId(),
                        'resourceKey' => 'media',
                    ],
                ],
                [
                    [
                        'id' => $child1->getId(),
                        'resourceKey' => 'collections',
                    ],
                    [
                        'id' => $child2->getId(),
                        'resourceKey' => 'collections',
                    ],
                    [
                        'id' => $collection->getMedia()->first()->getId(),
                        'resourceKey' => 'media',
                    ],
                ],
            ],
            'title' => 'Delete 7 subelements?',
            'detail' => 'Are you sure that you also want to delete 7 subcollection or media?',
        ], $content);
    }

    public function testDeleteByIdWithChildrenWithoutPermissions(): void
    {
        // warmup system collections to prevent error when setting permissions to collections
        $this->getContainer()->get('sulu_media.system_collections.manager')->warmUp();

        $role = $this->createRole('User', 'Sulu');

        $userRole = new UserRole();
        $userRole->setUser($this->getTestUser());
        $userRole->setLocale('["en-gb", "de"]');
        $userRole->setRole($role);
        $this->em->persist($userRole);

        $this->getTestUser()->addUserRole($userRole);
        $this->em->flush();

        $permissions = [
            $role->getId() => [
                'view' => true,
                'edit' => true,
                'add' => true,
                'delete' => false,
                'archive' => true,
                'live' => true,
                'security' => true,
            ],
        ];

        $fullPermissions = [
            $role->getId() => [
                'view' => true,
                'edit' => true,
                'add' => true,
                'delete' => true,
                'archive' => true,
                'live' => true,
                'security' => true,
            ],
        ];

        $collection = $this->createCollection($this->collectionType1);
        $collectionId = $collection->getId();

        $child1 = $this->createCollection($this->collectionType1, ['en-gb' => 'Child 1'], $collection);
        $this->accessControlManager->setPermissions(Collection::class, (string) $child1->getId(), $permissions);

        $child11 = $this->createCollection($this->collectionType1, ['en-gb' => 'Child 1-1'], $child1);
        $this->accessControlManager->setPermissions(Collection::class, (string) $child11->getId(), $fullPermissions);

        $child111 = $this->createCollection($this->collectionType1, ['en-gb' => 'Child 1-1-1'], $child11);
        $this->accessControlManager->setPermissions(Collection::class, (string) $child111->getId(), $permissions);

        $child12 = $this->createCollection($this->collectionType1, ['en-gb' => 'Child 1-2'], $child1);
        $this->accessControlManager->setPermissions(Collection::class, (string) $child12->getId(), $permissions);

        $child2 = $this->createCollection($this->collectionType1, ['en-gb' => 'Child 2'], $collection);
        $this->accessControlManager->setPermissions(Collection::class, (string) $child2->getId(), $permissions);

        $this->em->clear();

        $this->client->jsonRequest('DELETE', '/api/collections/' . $collectionId);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(403, $response);

        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('errors', $content);
        unset($content['errors']);

        $this->assertEquals([
            'code' => 1104,
            'message' => 'Insufficient permissions for 4 descendant elements.',
            'detail' => 'Insufficient permissions for 4 descendant elements.',
        ], $content);
    }

    public function testDeleteByIdNotExisting(): void
    {
        $this->client->jsonRequest('DELETE', '/api/collections/404');
        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/collections?locale=en&flat=true');
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(2, $response->total);
    }

    /**
     * @return mixed[]
     */
    private function prepareTree(): array
    {
        $collection1 = $this->collection1;
        $collection2 = $this->createCollection(
            $this->createCollectionType(3, 'my-type-1', 'My Type 1'),
            ['en-gb' => 'col2'],
            $collection1
        );
        $collection3 = $this->createCollection(
            $this->createCollectionType(4, 'my-type-2', 'My Type 2'),
            ['en-gb' => 'col3'],
            $collection1
        );
        $collection4 = $this->createCollection(
            $this->createCollectionType(5, 'my-type-3', 'My Type 3'),
            ['en-gb' => 'col4']
        );
        $collection5 = $this->createCollection(
            $this->createCollectionType(6, 'my-type-4', 'My Type 4'),
            ['en-gb' => 'col5'],
            $collection4
        );
        $collection6 = $this->createCollection(
            $this->createCollectionType(7, 'my-type-5', 'My Type 5'),
            ['en-gb' => 'col6'],
            $collection4
        );
        $collection7 = $this->createCollection(
            $this->createCollectionType(8, 'my-type-6', 'My Type 6'),
            ['en-gb' => 'col7'],
            $collection6
        );

        return [
            [
                'Test Collection',
                'col2',
                'col3',
                'col4',
                'col5',
                'col6',
                'col7',
            ],
            [
                $collection1->getId(),
                $collection2->getId(),
                $collection3->getId(),
                $collection4->getId(),
                $collection5->getId(),
                $collection6->getId(),
                $collection7->getId(),
            ],
            [
                $collection1,
                $collection2,
                $collection3,
                $collection4,
                $collection5,
                $collection6,
                $collection7,
            ],
        ];
    }

    public function testCGetNestedFlat(): void
    {
        list($titles) = $this->prepareTree();

        $this->client->jsonRequest(
            'GET',
            '/api/collections?flat=true',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response);
        $this->assertEquals(2, $response->total);
        $this->assertCount(2, $response->_embedded->collections);
        $items = $this->mapCollections($response->_embedded->collections);

        $this->assertContains(['title' => $titles[0], 'parent' => null, 'collections' => []], $items);
        $this->assertContains(['title' => $titles[3], 'parent' => null, 'collections' => []], $items);

        $this->client->jsonRequest(
            'GET',
            '/api/collections?flat=true&depth=1',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response);
        $this->assertEquals(6, $response->total);
        $this->assertCount(6, $response->_embedded->collections);
        $items = $this->mapCollections($response->_embedded->collections);

        $this->assertContains(['title' => $titles[0], 'parent' => null, 'collections' => []], $items);
        $this->assertContains(['title' => $titles[1], 'parent' => $titles[0], 'collections' => []], $items);
        $this->assertContains(['title' => $titles[2], 'parent' => $titles[0], 'collections' => []], $items);
        $this->assertContains(['title' => $titles[3], 'parent' => null, 'collections' => []], $items);
        $this->assertContains(['title' => $titles[4], 'parent' => $titles[3], 'collections' => []], $items);
        $this->assertContains(['title' => $titles[5], 'parent' => $titles[3], 'collections' => []], $items);

        $this->client->jsonRequest(
            'GET',
            '/api/collections?flat=true&depth=2',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response);
        $this->assertEquals(7, $response->total);
        $this->assertCount(7, $response->_embedded->collections);
        $items = $this->mapCollections($response->_embedded->collections);

        $this->assertContains(['title' => $titles[0], 'parent' => null, 'collections' => []], $items);
        $this->assertContains(['title' => $titles[1], 'parent' => $titles[0], 'collections' => []], $items);
        $this->assertContains(['title' => $titles[2], 'parent' => $titles[0], 'collections' => []], $items);
        $this->assertContains(['title' => $titles[3], 'parent' => null, 'collections' => []], $items);
        $this->assertContains(['title' => $titles[4], 'parent' => $titles[3], 'collections' => []], $items);
        $this->assertContains(['title' => $titles[5], 'parent' => $titles[3], 'collections' => []], $items);
        $this->assertContains(['title' => $titles[6], 'parent' => $titles[5], 'collections' => []], $items);
    }

    public function testCGetNestedTree(): void
    {
        list($titles) = $this->prepareTree();

        $this->client->jsonRequest(
            'GET',
            '/api/collections',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response);
        $this->assertCount(2, $response->_embedded->collections);
        $items = $this->mapCollections($response->_embedded->collections);

        $this->assertContains(['title' => $titles[0], 'parent' => null, 'collections' => []], $items);
        $this->assertContains(['title' => $titles[3], 'parent' => null, 'collections' => []], $items);

        $this->client->jsonRequest(
            'GET',
            '/api/collections?depth=1',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response);
        $this->assertCount(2, $response->_embedded->collections);
        $items = $this->mapCollections($response->_embedded->collections);

        $this->assertContains(
            [
                'title' => $titles[0],
                'parent' => null,
                'collections' => [
                    ['title' => $titles[1], 'parent' => $titles[0], 'collections' => []],
                    ['title' => $titles[2], 'parent' => $titles[0], 'collections' => []],
                ],
            ],
            $items
        );
        $this->assertContains(
            [
                'title' => $titles[3],
                'parent' => null,
                'collections' => [
                    ['title' => $titles[4], 'parent' => $titles[3], 'collections' => []],
                    ['title' => $titles[5], 'parent' => $titles[3], 'collections' => []],
                ],
            ],
            $items
        );

        $this->client->jsonRequest(
            'GET',
            '/api/collections?depth=2',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotEmpty($response);
        $this->assertCount(2, $response->_embedded->collections);
        $items = $this->mapCollections($response->_embedded->collections);

        $this->assertContains(
            [
                'title' => $titles[0],
                'parent' => null,
                'collections' => [
                    ['title' => $titles[1], 'parent' => $titles[0], 'collections' => []],
                    ['title' => $titles[2], 'parent' => $titles[0], 'collections' => []],
                ],
            ],
            $items
        );
        $this->assertContains(
            [
                'title' => $titles[3],
                'parent' => null,
                'collections' => [
                    ['title' => $titles[4], 'parent' => $titles[3], 'collections' => []],
                    [
                        'title' => $titles[5],
                        'parent' => $titles[3],
                        'collections' => [
                            ['title' => $titles[6], 'parent' => $titles[5], 'collections' => []],
                        ],
                    ],
                ],
            ],
            $items
        );
    }

    public function testGetBreadcrumb(): void
    {
        list($titles, $ids) = $this->prepareTree();

        $this->client->jsonRequest(
            'GET',
            '/api/collections/' . $ids[6],
            [
                'locale' => 'en-gb',
                'breadcrumb' => 'true',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($titles[6], $response['title']);

        $breadcrumb = $response['_embedded']['breadcrumb'];
        $this->assertCount(2, $breadcrumb);
        $this->assertEquals($titles[3], $breadcrumb[0]['title']);
        $this->assertEquals($ids[3], $breadcrumb[0]['id']);
        $this->assertEquals($titles[5], $breadcrumb[1]['title']);
        $this->assertEquals($ids[5], $breadcrumb[1]['id']);
    }

    public function testGetByIdWithDepth(): void
    {
        list($titles, $ids) = $this->prepareTree();

        $this->client->jsonRequest(
            'GET',
            '/api/collections/' . $ids[3],
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($titles[3], $response->title);
        $this->assertNull($response->_embedded->parent);
        $this->assertEmpty($response->_embedded->collections);

        $this->client->jsonRequest(
            'GET',
            '/api/collections/' . $ids[3] . '?depth=1&children=true',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($titles[3], $response->title);
        $this->assertNull($response->_embedded->parent);
        $this->assertNotEmpty($response->_embedded->collections);

        $items = $this->mapCollections($response->_embedded->collections);
        $this->assertCount(2, $items);
        $this->assertContains(['title' => $titles[4], 'parent' => $titles[3], 'collections' => []], $items);
        $this->assertContains(['title' => $titles[5], 'parent' => $titles[3], 'collections' => []], $items);

        $this->client->jsonRequest(
            'GET',
            '/api/collections/' . $ids[3] . '?depth=2&children=true',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($titles[3], $response->title);
        $this->assertNull($response->_embedded->parent);
        $this->assertNotEmpty($response->_embedded->collections);

        $items = $this->mapCollections($response->_embedded->collections);
        $this->assertCount(2, $items);

        $this->assertContains(['title' => $titles[4], 'parent' => $titles[3], 'collections' => []], $items);
        $this->assertContains(
            [
                'title' => $titles[5],
                'parent' => $titles[3],
                'collections' => [
                    ['title' => $titles[6], 'parent' => $titles[5], 'collections' => []],
                ],
            ],
            $items
        );
    }

    public function testMove(): void
    {
        list($titles, $ids) = $this->prepareTree();

        $this->client->jsonRequest(
            'POST',
            '/api/collections/' . $ids[3] . '?action=move&destination=' . $ids[0],
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($ids[0], $response->_embedded->parent->id);

        $this->client->jsonRequest(
            'GET',
            '/api/collections?depth=3',
            [
                'locale' => 'en-gb',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $items = $this->mapCollectionsFlat($response->_embedded->collections);
        $this->assertEquals(7, $response->total);
        $this->assertCount(7, $items);

        $this->assertContains(
            ['title' => $titles[0], 'parent' => null, 'collections' => ['col2', 'col3', 'col4']],
            $items
        );
        $this->assertContains(['title' => $titles[1], 'parent' => $titles[0], 'collections' => []], $items);
        $this->assertContains(['title' => $titles[2], 'parent' => $titles[0], 'collections' => []], $items);
        $this->assertContains(
            ['title' => $titles[3], 'parent' => $titles[0], 'collections' => [$titles[4], $titles[5]]],
            $items
        );
        $this->assertContains(['title' => $titles[4], 'parent' => $titles[3], 'collections' => []], $items);
        $this->assertContains(['title' => $titles[5], 'parent' => $titles[3], 'collections' => [$titles[6]]], $items);
        $this->assertContains(['title' => $titles[6], 'parent' => $titles[5], 'collections' => []], $items);
    }

    public function testPostParentIsSystemCollection(): void
    {
        $collectionId = $this->client->getContainer()->get('sulu_media.system_collections.manager')->getSystemCollection(
            'sulu_media'
        );

        $this->client->jsonRequest(
            'POST',
            '/api/collections',
            [
                'locale' => 'en-gb',
                'type' => [
                    'id' => $this->collectionType1->getId(),
                ],
                'title' => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
                'parent' => $collectionId,
            ]
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
    }

    public function testPutSystemCollection(): void
    {
        $collectionId = $this->client->getContainer()->get('sulu_media.system_collections.manager')->getSystemCollection(
            'sulu_media'
        );

        $this->client->jsonRequest(
            'PUT',
            '/api/collections/' . $collectionId,
            [
                'locale' => 'en-gb',
                'type' => [
                    'id' => $this->collectionType1->getId(),
                ],
                'title' => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
            ]
        );

        $this->assertHttpStatusCode(403, $this->client->getResponse());
    }

    public function testDeleteSystemCollection(): void
    {
        $collectionId = $this->client->getContainer()->get('sulu_media.system_collections.manager')->getSystemCollection(
            'sulu_media'
        );

        $this->client->jsonRequest('DELETE', '/api/collections/' . $collectionId);
        $this->assertHttpStatusCode(403, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/collections/' . $collectionId . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }
}
