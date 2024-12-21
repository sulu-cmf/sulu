<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\EventListener;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AudienceTargetingBundle\EventListener\AudienceTargetingCacheListener;
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AudienceTargetingCacheListenerTest extends TestCase
{
    use ProphecyTrait;
    use ReadObjectAttributeTrait;

    public function testHandle(): void
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $httpCache = $this->getHttpCache();

        $audienceTargetingCacheListener = new AudienceTargetingCacheListener();
        $audienceTargetingCacheListener->preHandle($this->getCacheEvent($httpCache, $request, $response));

        $this->assertFalse($this->readObjectAttribute($audienceTargetingCacheListener, 'hadValidTargetGroupCookie'));
        $this->assertEmpty($response->headers->getCookies());

        $audienceTargetingCacheListener->postHandle($this->getCacheEvent($httpCache, $request, $response));

        // check if headers are set
        $this->assertEquals(0, $response->getMaxAge());

        // check if both cookies are set
        $this->assertCount(2, $response->headers->getCookies());

        // check for target group cookie
        $this->assertEquals(
            AudienceTargetingCacheListener::TARGET_GROUP_COOKIE,
            $response->headers->getCookies()[0]->getName()
        );
        $this->assertEquals(
            'TARGET_GROUP_1',
            $response->headers->getCookies()[0]->getValue()
        );
        $this->assertEquals(
            AudienceTargetingCacheListener::TARGET_GROUP_COOKIE_LIFETIME,
            $response->headers->getCookies()[0]->getExpiresTime()
        );

        // check for session cookie
        $this->assertEquals(
            AudienceTargetingCacheListener::VISITOR_SESSION_COOKIE,
            $response->headers->getCookies()[1]->getName()
        );
        $this->assertGreaterThan(
            1,
            $response->headers->getCookies()[1]->getValue()
        );
    }

    public function testHandleInternalRequest(): void
    {
        $request = $this->getRequest();
        $request->attributes->set('internalRequest', true);
        $response = $this->getResponse();
        $httpCache = $this->prophesize(SuluHttpCache::class);
        $httpCache->handle(Argument::any())
            ->shouldNotBeCalled();

        $audienceTargetingCacheListener = new AudienceTargetingCacheListener();
        $audienceTargetingCacheListener->preHandle($this->getCacheEvent($httpCache->reveal(), $request, $response));
    }

    public function testHandleWithCorrectCookies(): void
    {
        $request = $this->getRequest(true);
        $response = $this->getResponse();
        $httpCache = $this->getHttpCache();

        $audienceTargetingCacheListener = new AudienceTargetingCacheListener();

        $audienceTargetingCacheListener->preHandle($this->getCacheEvent($httpCache, $request, $response));

        $this->assertTrue($this->readObjectAttribute($audienceTargetingCacheListener, 'hadValidTargetGroupCookie'));
        $this->assertEmpty($response->headers->getCookies());

        $audienceTargetingCacheListener->postHandle($this->getCacheEvent($httpCache, $request, $response));

        // check if headers are set
        $this->assertEquals(0, $response->getMaxAge());

        // check if cookies are empty
        $this->assertEmpty($response->headers->getCookies());
    }

    protected function getRequest(bool $withCookiesSet = false): Request
    {
        $request = new Request();

        if ($withCookiesSet) {
            $request->cookies->set(AudienceTargetingCacheListener::TARGET_GROUP_COOKIE, 'TARGET_GROUP_1');
            $request->cookies->set(AudienceTargetingCacheListener::VISITOR_SESSION_COOKIE, \time());
        }

        return $request;
    }

    protected function getResponse(): Response
    {
        $response = new Response();

        return $response;
    }

    protected function getCacheEvent(SuluHttpCache $httpCache, Request $request, Response $response): CacheEvent
    {
        return new CacheEvent(
            $httpCache,
            $request,
            $response
        );
    }

    protected function getHttpCache(): SuluHttpCache
    {
        $targetGroupResponse = $this->prophesize(Response::class);

        $responseHeaderBag = $this->prophesize(ResponseHeaderBag::class);
        $responseHeaderBag->get(AudienceTargetingCacheListener::TARGET_GROUP_HEADER)->willReturn('TARGET_GROUP_1');

        $targetGroupResponse->headers = $responseHeaderBag->reveal();

        $httpCache = $this->prophesize(SuluHttpCache::class);
        $httpCache->handle(
            Argument::any(),
            HttpKernelInterface::MAIN_REQUEST,
            false
        )->willReturn($targetGroupResponse->reveal());

        return $httpCache->reveal();
    }
}
