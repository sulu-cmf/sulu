<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemPoolInterface;
use Sulu\Bundle\AdminBundle\Entity\Collaboration;
use Sulu\Bundle\AdminBundle\Entity\CollaborationRepository;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\Cache\CacheItem;

class CollaborationRepositoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CacheItemPoolInterface>
     */
    private $cache;

    public static function setUpBeforeClass(): void
    {
        ClockMock::withClockMock(true);
    }

    public function setUp(): void
    {
        $this->cache = $this->prophesize(CacheItemPoolInterface::class);
    }

    public static function tearDownAfterClass(): void
    {
        ClockMock::withClockMock(false);
    }

    public function testFind(): void
    {
        $cacheItem = new CacheItem();
        $this->cache->getItem('page_8')->willReturn($cacheItem);
        $collaborationRepository = new CollaborationRepository($this->cache->reveal(), 20);

        $collaboration1 = new Collaboration(
            'collaboration1',
            1,
            'max',
            'Max Mustermann',
            'page',
            8
        );

        $this->cache->save($cacheItem)->shouldBeCalled();
        $collaborationRepository->update($collaboration1);

        $result = $collaborationRepository->find('page', 8, 'collaboration1');
        $this->assertEquals($collaboration1, $result);
    }

    public function testFindWithNotExistingCollaboration(): void
    {
        $cacheItem = new CacheItem();
        $this->cache->getItem('page_8')->willReturn($cacheItem);
        $collaborationRepository = new CollaborationRepository($this->cache->reveal(), 20);

        $collaboration1 = new Collaboration(
            'collaboration1',
            1,
            'max',
            'Max Mustermann',
            'page',
            8
        );

        $this->cache->save($cacheItem)->shouldBeCalled();
        $collaborationRepository->update($collaboration1);

        $result = $collaborationRepository->find('page', 8, 'collaboration2');
        $this->assertNull($result);
    }

    public function testFindWithNotExistingCacheItem(): void
    {
        $collaborationRepository = new CollaborationRepository($this->cache->reveal(), 20);

        $cacheItem = new CacheItem();
        $this->cache->getItem('page_8')->willReturn($cacheItem);

        $result = $collaborationRepository->find('page', 8, 'collaboration2');
        $this->assertNull($result);
    }

    public static function provideUpdate()
    {
        return [
            [
                5,
                'collaboration1',
                '1',
                'max',
                'Max Mustermann',
                'collaboration2',
                '2',
                'erika',
                'Erika Mustermann',
                'page',
                5,
            ],
            [
                10,
                'collaboration1',
                '2',
                'erika',
                'Erika Mustermann',
                'collaboration2',
                '1',
                'max',
                'Max Mustermann',
                'snippet',
                7,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideUpdate')]
    public function testUpdate(
        $threshold,
        $collaborationId1,
        $userId1,
        $userName1,
        $fullName1,
        $collaborationId2,
        $userId2,
        $userName2,
        $fullName2,
        $resourceKey,
        $id
    ): void {
        $cacheItem = new CacheItem();
        $this->cache->getItem($resourceKey . '_' . $id)->willReturn($cacheItem);
        $collaborationRepository = new CollaborationRepository($this->cache->reveal(), $threshold);

        $collaboration1 = new Collaboration(
            $collaborationId1,
            $userId1,
            $userName1,
            $fullName1,
            $resourceKey,
            $id
        );

        $this->cache->save($cacheItem)->shouldBeCalled();
        $result = $collaborationRepository->update($collaboration1);

        $this->assertEquals([$collaboration1], $result);

        $collaboration2 = new Collaboration(
            $collaborationId2,
            $userId2,
            $userName2,
            $fullName2,
            $resourceKey,
            $id
        );

        $this->cache->save($cacheItem)->shouldBeCalled();
        $result = $collaborationRepository->update($collaboration2);

        $this->assertEquals([$collaboration1, $collaboration2], $result);

        \sleep($threshold + 1);

        $collaboration2 = new Collaboration(
            $collaborationId2,
            $userId2,
            $userName2,
            $fullName2,
            $resourceKey,
            $id
        );

        $result = $collaborationRepository->update($collaboration2);

        $this->assertEquals([$collaboration2], $result);
    }

    public function testUpdateWithSameCollaborationId(): void
    {
        $cacheItem = new CacheItem();
        $this->cache->getItem('page_8')->willReturn($cacheItem);
        $collaborationRepository = new CollaborationRepository($this->cache->reveal(), 20);

        $collaboration1 = new Collaboration(
            'collaboration1',
            1,
            'max',
            'Max Mustermann',
            'page',
            8
        );

        $this->cache->save($cacheItem)->shouldBeCalled();
        $result = $collaborationRepository->update($collaboration1);

        $this->assertEquals([$collaboration1], $result);

        $this->cache->save($cacheItem)->shouldBeCalled();
        $result = $collaborationRepository->update($collaboration1);

        $this->assertEquals([$collaboration1], $result);
    }

    public function testUpdateWithUpdatedChangedTime(): void
    {
        $cacheItem = new CacheItem();
        $this->cache->getItem('page_8')->willReturn($cacheItem);
        $collaborationRepository = new CollaborationRepository($this->cache->reveal(), 20);

        $collaboration1 = new Collaboration(
            'collaboration1',
            1,
            'max',
            'Max Mustermann',
            'page',
            8
        );

        $started = $collaboration1->getStarted();
        $this->assertEquals($started, $collaboration1->getChanged());

        \sleep(10);

        $this->cache->save($cacheItem)->shouldBeCalled();
        $result = $collaborationRepository->update($collaboration1);

        $this->assertEquals($started, $collaboration1->getStarted());
        $this->assertEquals($started + 10, $collaboration1->getChanged());
    }

    public function testDelete(): void
    {
        $cacheItem = new CacheItem();
        $this->cache->getItem('page_8')->willReturn($cacheItem);
        $collaborationRepository = new CollaborationRepository($this->cache->reveal(), 20);

        $collaboration1 = new Collaboration(
            'collaboration1',
            1,
            'max',
            'Max Mustermann',
            'page',
            8
        );

        $collaboration2 = new Collaboration(
            'collaboration2',
            2,
            'erika',
            'Erika Mustermann',
            'page',
            8
        );

        $this->cache->save($cacheItem)->shouldBeCalled();
        $result = $collaborationRepository->update($collaboration1);
        $result = $collaborationRepository->update($collaboration2);

        $this->assertEquals([$collaboration1, $collaboration2], $result);

        $this->cache->save($cacheItem)->shouldBeCalled();
        $result = $collaborationRepository->delete($collaboration1);

        $this->assertEquals([$collaboration2], $result);
    }
}
