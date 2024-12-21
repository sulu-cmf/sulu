<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\CacheLifetime;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestStore;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CacheLifetimeRequestStoreTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var CacheLifetimeRequestStore
     */
    private $cacheLifetimeRequestStore;

    public function setUp(): void
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->cacheLifetimeRequestStore = new CacheLifetimeRequestStore($this->requestStack->reveal());
    }

    public static function provideSetCacheLifetime()
    {
        return [
            [null, 200, 200],
            [400, 100, 100],
            [300, 700, 300],
            [null, -500, null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSetCacheLifetime')]
    public function testSetCacheLifetime($previousCacheLifetime, $newCacheLifetime, $expectedCacheLifetime): void
    {
        $request = new Request([], [], $previousCacheLifetime ? ['_cacheLifetime' => $previousCacheLifetime] : []);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->cacheLifetimeRequestStore->setCacheLifetime($newCacheLifetime);

        $this->assertEquals($expectedCacheLifetime, $this->cacheLifetimeRequestStore->getCacheLifetime());
    }

    public function testGetCacheLifetimeWithoutRequest(): void
    {
        $this->assertNull($this->cacheLifetimeRequestStore->getCacheLifetime());
    }
}
