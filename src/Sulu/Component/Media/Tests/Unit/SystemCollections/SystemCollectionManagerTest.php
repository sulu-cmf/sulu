<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\Tests\Unit\SystemCollections;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Component\Cache\CacheInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManager;
use Sulu\Component\Media\SystemCollections\UnrecognizedSystemCollection;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SystemCollectionManagerTest extends TestCase
{
    use ProphecyTrait;

    public static function configProvider()
    {
        return [
            // existing 1 namespace / 1 collection
            [
                [
                    'sulu_test' => [
                        'meta_title' => ['en' => 'Sulu test'],
                        'collections' => [
                            'test1' => [
                                'meta_title' => ['en' => 'Test1'],
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'id' => 1,
                        'key' => 'system_collections',
                        'title' => 'System',
                        'locale' => 'en',
                        'setTitle' => 'System',
                        'parent' => null,
                    ],
                    [
                        'id' => 2,
                        'key' => 'sulu_test',
                        'title' => 'Sulu test',
                        'locale' => 'en',
                        'setTitle' => 'Sulu test',
                        'parent' => 1,
                    ],
                    [
                        'id' => 3,
                        'key' => 'sulu_test.test1',
                        'title' => 'Test1',
                        'locale' => 'en',
                        'setTitle' => 'Test1',
                        'parent' => 2,
                    ],
                ],
                [],
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3],
            ],
            // update existing 2 namespace / 1 collection
            [
                [
                    'sulu_test' => [
                        'meta_title' => ['en' => 'Sulu test'],
                        'collections' => [
                            'test1' => [
                                'meta_title' => ['en' => 'Test1'],
                            ],
                        ],
                    ],
                    'sulu_media' => [
                        'meta_title' => ['en' => 'Sulu media'],
                        'collections' => [
                            'test1' => [
                                'meta_title' => ['en' => 'Test1'],
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'id' => 1,
                        'key' => 'system_collections',
                        'title' => 'System',
                        'locale' => 'en',
                        'setTitle' => 'System',
                        'parent' => null,
                    ],
                    [
                        'id' => 2,
                        'key' => 'sulu_test',
                        'title' => 'Sulu test',
                        'locale' => 'en',
                        'setTitle' => 'Sulu test',
                        'parent' => 1,
                    ],
                    [
                        'id' => 3,
                        'key' => 'sulu_test.test1',
                        'title' => 'Test1',
                        'locale' => 'en',
                        'setTitle' => 'Test1',
                        'parent' => 2,
                    ],
                ],
                [
                    [
                        'id' => 4,
                        'key' => 'sulu_media',
                        'title' => 'Sulu media',
                        'locale' => 'en',
                        'setTitle' => 'Sulu media',
                        'parent' => 1,
                    ],
                    [
                        'id' => 5,
                        'key' => 'sulu_media.test1',
                        'title' => 'Test1',
                        'locale' => 'en',
                        'setTitle' => 'Test1',
                        'parent' => 4,
                    ],
                ],
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3, 'sulu_media' => 4, 'sulu_media.test1' => 5],
            ],
            // existing 1 namespace / 2 collection
            [
                [
                    'sulu_test' => [
                        'meta_title' => ['en' => 'Sulu test'],
                        'collections' => [
                            'test1' => [
                                'meta_title' => ['en' => 'Test1'],
                            ],
                            'test2' => [
                                'meta_title' => ['en' => 'Test2'],
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'id' => 1,
                        'key' => 'system_collections',
                        'title' => 'System',
                        'locale' => 'en',
                        'setTitle' => 'System',
                        'parent' => null,
                    ],
                    [
                        'id' => 2,
                        'key' => 'sulu_test',
                        'title' => 'Sulu test',
                        'locale' => 'en',
                        'setTitle' => 'Sulu test',
                        'parent' => 1,
                    ],
                    [
                        'id' => 3,
                        'key' => 'sulu_test.test1',
                        'title' => 'Test1',
                        'locale' => 'en',
                        'setTitle' => 'Test1',
                        'parent' => 2,
                    ],
                    [
                        'id' => 4,
                        'key' => 'sulu_test.test2',
                        'title' => 'Test2',
                        'locale' => 'en',
                        'setTitle' => 'Test2',
                        'parent' => 2,
                    ],
                ],
                [],
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3, 'sulu_test.test2' => 4],
            ],
            // not existing 1 namespace / 1 collection
            [
                [
                    'sulu_test' => [
                        'meta_title' => ['en' => 'Sulu test'],
                        'collections' => [
                            'test1' => [
                                'meta_title' => ['en' => 'Test1'],
                            ],
                        ],
                    ],
                ],
                [],
                [
                    [
                        'id' => 1,
                        'key' => 'system_collections',
                        'title' => 'System',
                        'locale' => 'en',
                        'setTitle' => 'System',
                        'parent' => null,
                    ],
                    [
                        'id' => 2,
                        'key' => 'sulu_test',
                        'title' => 'Sulu test',
                        'locale' => 'en',
                        'setTitle' => 'Sulu test',
                        'parent' => 1,
                    ],
                    [
                        'id' => 3,
                        'key' => 'sulu_test.test1',
                        'title' => 'Test1',
                        'locale' => 'en',
                        'setTitle' => 'Test1',
                        'parent' => 2,
                    ],
                ],
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3],
            ],
            // multiple-translations
            [
                [
                    'sulu_test' => [
                        'meta_title' => ['en' => 'Sulu test EN', 'de' => 'Sulu test DE'],
                        'collections' => [
                            'test1' => [
                                'meta_title' => ['en' => 'EN', 'de' => 'DE'],
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'id' => 2,
                        'key' => 'sulu_test',
                        'title' => 'Sulu test EN',
                        'locale' => 'de',
                        'setTitle' => 'Sulu test DE',
                        'parent' => 1,
                    ],
                    [
                        'id' => 3,
                        'key' => 'sulu_test.test1',
                        'title' => 'EN',
                        'locale' => 'de',
                        'setTitle' => 'DE',
                        'parent' => 2,
                    ],
                ],
                [
                    [
                        'id' => 1,
                        'key' => 'system_collections',
                        'title' => 'System',
                        'locale' => 'en',
                        'setTitle' => 'System',
                        'parent' => null,
                    ],
                    [
                        'id' => 2,
                        'key' => 'sulu_test',
                        'title' => 'Sulu test EN',
                        'locale' => 'en',
                        'setTitle' => 'Sulu test EN',
                        'parent' => 1,
                    ],
                    [
                        'id' => 3,
                        'key' => 'sulu_test.test1',
                        'title' => 'EN',
                        'locale' => 'en',
                        'setTitle' => 'EN',
                        'parent' => 2,
                    ],
                ],
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3],
            ],
        ];
    }

    public static function getProvider()
    {
        return [
            [
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3],
                'sulu_test',
                2,
            ],
            [
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3],
                'sulu_test.test1',
                3,
            ],
            [
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3],
                'sulu_test.test2',
                null,
                UnrecognizedSystemCollection::class,
            ],
        ];
    }

    public static function isProvider()
    {
        return [
            [
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3],
                1,
                true,
            ],
            [
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3],
                2,
                true,
            ],
            [
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3],
                3,
                true,
            ],
            [
                ['root' => 1, 'sulu_test' => 2, 'sulu_test.test1' => 3],
                5,
                false,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('configProvider')]
    public function testWarmUp($config, $existingCollections, $notExistingCollections, $data): void
    {
        $tokenStorage = $this->getTokenStorage();
        $collectionManager = $this->getCollectionManager($existingCollections, $notExistingCollections);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $cache = $this->prophesize(CacheInterface::class);
        $cache->invalidate()->shouldBeCalled();
        $cache->isFresh()->shouldBeCalled()->willReturn(false);
        $cache->write($data)->shouldBeCalled();
        $cache->read()->shouldBeCalled()->willReturn($data);

        $manager = new SystemCollectionManager(
            $config,
            $collectionManager->reveal(),
            $entityManager->reveal(),
            $tokenStorage->reveal(),
            $cache->reveal(),
            'en'
        );

        $manager->warmUp();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getProvider')]
    public function testGetSystemCollection($data, $key, $expected, $exception = null): void
    {
        if (null !== $exception) {
            $this->expectException($exception);
        }

        $tokenStorage = $this->getTokenStorage();
        $collectionManager = $this->getCollectionManager();

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $cache = $this->prophesize(CacheInterface::class);
        $cache->isFresh()->shouldBeCalled()->willReturn(true);
        $cache->read()->shouldBeCalled()->willReturn($data);

        $manager = new SystemCollectionManager(
            [],
            $collectionManager->reveal(),
            $entityManager->reveal(),
            $tokenStorage->reveal(),
            $cache->reveal(),
            'en'
        );
        $result = $manager->getSystemCollection($key);

        if (null === $exception) {
            $this->assertEquals($expected, $result);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isProvider')]
    public function testIsSystemCollection($data, $id, $expected): void
    {
        $tokenStorage = $this->getTokenStorage();
        $collectionManager = $this->getCollectionManager();

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $cache = $this->prophesize(CacheInterface::class);
        $cache->isFresh()->shouldBeCalled()->willReturn(true);
        $cache->read()->shouldBeCalled()->willReturn($data);

        $manager = new SystemCollectionManager(
            [],
            $collectionManager->reveal(),
            $entityManager->reveal(),
            $tokenStorage->reveal(),
            $cache->reveal(),
            'en'
        );

        $this->assertEquals($expected, $manager->isSystemCollection($id));
    }

    private function getCollectionManager(
        $existingCollections = [],
        $notExistingCollections = [],
        $shouldBeCalled = true
    ) {
        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        foreach ($existingCollections as $item) {
            $this->appendExistingCollection(
                $collectionManager,
                $item['id'],
                $item['key'],
                $item['title'],
                $item['locale'],
                $item['setTitle'],
                $item['parent']
            );
        }
        foreach ($notExistingCollections as $item) {
            $this->appendNotExistingCollection(
                $collectionManager,
                $item['id'],
                $item['key'],
                $item['title'],
                $item['locale'],
                $item['setTitle'],
                $item['parent'],
                $shouldBeCalled
            );
        }

        return $collectionManager;
    }

    private function getTokenStorage()
    {
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(UserInterface::class);
        $user->getId()->willReturn(1);
        $token->getUser()->willReturn($user->reveal());
        $tokenStorage->getToken()->willReturn($token->reveal());

        return $tokenStorage;
    }

    private function getCollection($id, $key, $title, $setTitle)
    {
        $collection = $this->prophesize(Collection::class);
        $collection->getId()->willReturn($id);
        $collection->getKey()->willReturn($key);
        $collection->getTitle()->willReturn($title);
        $collection->setTitle($setTitle)->willReturn($collection->reveal());

        return $collection->reveal();
    }

    private function appendExistingCollection($collectionManager, $id, $key, $title, $locale, $setTitle, $parent)
    {
        $data = [
            'id' => $id,
            'title' => $setTitle,
            'key' => $key,
            'type' => ['id' => 2],
            'locale' => $locale,
        ];

        if (null !== $parent) {
            $data['parent'] = $parent;
        }

        $collection = $this->getCollection($id, $key, $title, $setTitle);
        $collectionManager->getByKey($key, $locale)->willReturn($collection);
        $collectionManager->save($data, 1)->willReturn($collection);
    }

    private function appendNotExistingCollection(
        $collectionManager,
        $id,
        $key,
        $title,
        $locale,
        $setTitle,
        $parent,
        $shouldBeCalled
    ) {
        $data = [
            'title' => $setTitle,
            'key' => $key,
            'type' => ['id' => 2],
            'locale' => $locale,
        ];

        if (null !== $parent) {
            $data['parent'] = $parent;
        }

        $collectionManager->getByKey($key, $locale)->willReturn(null);
        $tmp = $collectionManager->save($data, 1)->willReturn($this->getCollection($id, $key, $title, $setTitle));
        if (true === $shouldBeCalled) {
            $tmp->shouldBeCalled();
        }
    }
}
