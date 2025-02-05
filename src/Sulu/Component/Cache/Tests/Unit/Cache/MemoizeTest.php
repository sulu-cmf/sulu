<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache\Tests\Unit;

use Doctrine\Common\Cache\CacheProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Cache\Memoize;
use Sulu\Component\Cache\MemoizeInterface;

class MemoizeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var MemoizeInterface
     */
    private $mem;

    /**
     * @var ObjectProphecy<CacheProvider>
     */
    private $cache;

    /**
     * @var int
     */
    private $defaultLifeTime = 600;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->prophesize(CacheProvider::class);

        $this->mem = new Memoize($this->cache->reveal(), $this->defaultLifeTime);
    }

    public function testMemoizeByIdFirstCall(): void
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function($a, $b) use ($mem, &$called) {
            return $mem->memoizeById(
                'mem',
                [$a, $b],
                function($a, $b) use (&$called) {
                    ++$called;

                    return $a + $b;
                }
            );
        };

        $id12 = \md5(\sprintf('mem(%s)', \serialize([1, 2])));
        $id23 = \md5(\sprintf('mem(%s)', \serialize([2, 3])));

        $this->cache->save($id12, 3, $this->defaultLifeTime)->willReturn(null);
        $this->cache->contains($id12)->willReturn(false);

        $this->cache->save($id23, 5, $this->defaultLifeTime)->willReturn(null);
        $this->cache->contains($id23)->willReturn(false);

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(2, $called);
    }

    public function testMemoizeByIdFirstCallWithLifeTime(): void
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function($a, $b) use ($mem, &$called) {
            return $mem->memoizeById(
                'mem',
                [$a, $b],
                function($a, $b) use (&$called) {
                    ++$called;

                    return $a + $b;
                },
                100
            );
        };

        $id12 = \md5(\sprintf('mem(%s)', \serialize([1, 2])));
        $id23 = \md5(\sprintf('mem(%s)', \serialize([2, 3])));

        $this->cache->save($id12, 3, 100)->willReturn(null);
        $this->cache->contains($id12)->willReturn(false);

        $this->cache->save($id23, 5, 100)->willReturn(null);
        $this->cache->contains($id23)->willReturn(false);

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(2, $called);
    }

    public function testMemoizeByIdSecondCall(): void
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function($a, $b) use ($mem, &$called) {
            return $mem->memoizeById(
                'mem',
                [$a, $b],
                function() use ($a, $b, &$called) {
                    ++$called;

                    return $a + $b;
                }
            );
        };

        $id12 = \md5(\sprintf('mem(%s)', \serialize([1, 2])));
        $id23 = \md5(\sprintf('mem(%s)', \serialize([2, 3])));

        $this->cache->fetch($id12)->wilLReturn(3);
        $this->cache->contains($id12)->willReturn(true);

        $this->cache->fetch($id23)->wilLReturn(5);
        $this->cache->contains($id23)->willReturn(true);

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(0, $called);
    }
}
