<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\EventListener;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Bundle\WebsiteBundle\EventListener\SegmentCacheListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SegmentCacheListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SegmentCacheListener
     */
    private $segmentCacheListener;

    public function setUp(): void
    {
        $this->segmentCacheListener = new SegmentCacheListener();
    }

    /**
     * @return iterable<array{0: string|null}>
     */
    public static function providePreHandleCookieValue(): iterable
    {
        yield ['s'];

        yield ['w'];

        yield [null];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePreHandleCookieValue')]
    public function testPreHandleCookieValue(?string $cookieValue): void
    {
        $request = new Request([], [], [], ['_ss' => $cookieValue]);
        $response = new Response();

        $this->segmentCacheListener->preHandle($this->getCacheEvent($request, $response));

        $this->assertEquals($cookieValue, $request->headers->get('X-Sulu-Segment'));
    }

    /**
     * @return iterable<array{
     *     0: string|null,
     *     1: int,
     *     2: int,
     *     3: int,
     * }>
     */
    public static function providePostHandleVary(): iterable
    {
        return [
            ['X-Something', 60, 120, 120],
            ['X-Sulu-Segment', 60, 120, 0],
            [null, 60, 120, 120],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePostHandleVary')]
    public function testPostHandleWithVary(?string $header, int $maxAge, int $sharedMaxAge, int $expectedMaxAge): void
    {
        $request = new Request();
        $response = new Response();

        $response->setMaxAge($maxAge);
        $response->setSharedMaxAge($sharedMaxAge);

        if ($header) {
            $response->setVary($header);
        }

        $this->segmentCacheListener->postHandle($this->getCacheEvent($request, $response));

        $this->assertEquals($expectedMaxAge, $response->getMaxAge());
    }

    private function getCacheEvent(Request $request, Response $response): CacheEvent
    {
        $httpCache = $this->prophesize(SuluHttpCache::class);

        return new CacheEvent(
            $httpCache->reveal(),
            $request,
            $response
        );
    }
}
