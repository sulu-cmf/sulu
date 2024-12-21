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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeEnhancer;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestStore;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolver;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CacheLifetimeEnhancerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var CacheLifetimeEnhancer
     */
    private $cacheLifetimeEnhancer;

    /**
     * @var ObjectProphecy<CacheLifetimeRequestStore>
     */
    private $cacheLifetimeRequestStore;

    /**
     * @var ObjectProphecy<CacheLifetimeResolver>
     */
    private $cacheLifetimeResolver;

    /**
     * @var ObjectProphecy<PageBridge>
     */
    private $page;

    /**
     * @var ObjectProphecy<SnippetBridge>
     */
    private $snippet;

    /**
     * @var ObjectProphecy<Response>
     */
    private $response;

    /**
     * @var ObjectProphecy<ResponseHeaderBag>
     */
    private $responseHeaderBag;

    /**
     * @var int
     */
    private $maxAge = 200;

    /**
     * @var int
     */
    private $sharedMaxAge = 300;

    public function setUp(): void
    {
        $this->cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolver::class);
        $this->cacheLifetimeRequestStore = $this->prophesize(CacheLifetimeRequestStore::class);

        $this->page = $this->prophesize(PageBridge::class);
        $this->snippet = $this->prophesize(SnippetBridge::class);
        $this->response = $this->prophesize(Response::class);
        $this->responseHeaderBag = $this->prophesize(ResponseHeaderBag::class);
        $this->response->headers = $this->responseHeaderBag;

        $this->cacheLifetimeEnhancer = new CacheLifetimeEnhancer(
            $this->cacheLifetimeResolver->reveal(),
            $this->maxAge,
            $this->sharedMaxAge,
            $this->cacheLifetimeRequestStore->reveal()
        );
    }

    public static function provideCacheLifeTime()
    {
        return [
            [50, null, 50],
            [500, null, 500],
            [0, null, 0],
            [700, 800, 700],
            [600, 400, 400],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideCacheLifeTime')]
    public function testEnhance(int $cacheLifetime, ?int $requestCacheLifetime, int $expectedCacheLifetime): void
    {
        $this->page->getCacheLifeTime()->willReturn(
            ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => $cacheLifetime]
        );

        if ($expectedCacheLifetime > 0) {
            $this->responseHeaderBag
                ->set(SuluHttpCache::HEADER_REVERSE_PROXY_TTL, $expectedCacheLifetime)
                ->shouldBeCalled();

            $this->response->setPublic()->shouldBeCalled()->willReturn($this->response->reveal());
            $this->response->setMaxAge($this->maxAge)->shouldBeCalled()->willReturn($this->response->reveal());
            $this->response->setSharedMaxAge($this->sharedMaxAge)->shouldBeCalled()->willReturn($this->response->reveal());
        } else {
            $this->responseHeaderBag->set(Argument::cetera())->shouldNotBeCalled();
            $this->response->setPublic()->shouldNotBeCalled()->willReturn($this->response->reveal());
            $this->response->setMaxAge(Argument::any())->shouldNotBeCalled()->willReturn($this->response->reveal());
            $this->response->setSharedMaxAge(Argument::any())->shouldNotBeCalled()->willReturn($this->response->reveal());
        }

        if ($requestCacheLifetime) {
            $this->cacheLifetimeRequestStore->getCacheLifetime()->willReturn($requestCacheLifetime);
        }

        $this->cacheLifetimeResolver->resolve(Argument::cetera())->willReturn($cacheLifetime);
        $this->cacheLifetimeEnhancer->enhance($this->response->reveal(), $this->page->reveal());
    }

    public function testEnhanceSnippet(): void
    {
        $this->cacheLifetimeEnhancer->enhance($this->response->reveal(), $this->snippet->reveal());
        $this->response->setPublic()->shouldNotBeCalled();
    }
}
