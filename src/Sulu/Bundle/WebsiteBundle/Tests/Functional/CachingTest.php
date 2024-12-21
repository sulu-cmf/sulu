<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Tests\Application\AppCache;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\CookieJar;

class CachingTest extends SuluTestCase
{
    public static function getSuluContext(): string
    {
        return SuluKernel::CONTEXT_WEBSITE;
    }

    public function testFirstRequestIsACacheMiss()
    {
        $this->purgeDatabase();

        $cacheKernel = new AppCache(self::bootKernel());
        $cookieJar = new CookieJar();
        $client = new KernelBrowser($cacheKernel, [], null, $cookieJar);
        $client->disableReboot();

        $client->request('PURGE', 'http://sulu.lo');

        $cookieJar->clear();

        // first request should be cache miss
        $client->request('GET', 'http://sulu.lo');
        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertContains('X-Sulu-Segment', $response->getVary());
        $this->assertStringContainsString('miss', $response->headers->get('x-symfony-cache'));
        $this->assertCount(0, $response->headers->getCookies());
        $this->assertEquals(0, $response->headers->getCacheControlDirective('max-age'));
        $this->assertEquals(0, $response->headers->getCacheControlDirective('s-maxage'));

        return [$client, $cookieJar];
    }

    #[\PHPUnit\Framework\Attributes\Depends('testFirstRequestIsACacheMiss')]
    public function testSecondRequestIsACacheHit($arguments)
    {
        list($client, $cookieJar) = $arguments;

        $client->request('GET', 'http://sulu.lo');
        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $this->assertStringContainsString('fresh', $response->headers->get('x-symfony-cache'));
        $this->assertCount(0, $response->headers->getCookies());

        return [$client, $cookieJar];
    }

    #[\PHPUnit\Framework\Attributes\Depends('testSecondRequestIsACacheHit')]
    public function testSwitchSegmentIsACacheMiss($arguments)
    {
        list($client, $cookieJar) = $arguments;

        $client->request('GET', 'http://sulu.lo/_sulu_segment_switch?segment=s&url=http://sulu.lo');
        $response = $client->getResponse();

        $this->assertHttpStatusCode(302, $response);
        $this->assertStringContainsString('miss', $response->headers->get('x-symfony-cache'));
        $this->assertCount(1, $response->headers->getCookies());

        $segmentCookie = $response->headers->getCookies()[0];
        $this->assertEquals('_ss', $segmentCookie->getName());
        $this->assertEquals('s', $segmentCookie->getValue());

        $client->request('GET', 'http://sulu.lo');
        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertStringContainsString('miss', $response->headers->get('x-symfony-cache'));

        return [$client, $cookieJar];
    }

    #[\PHPUnit\Framework\Attributes\Depends('testSwitchSegmentIsACacheMiss')]
    public function testSwitchedSegmentIsACachHit($arguments)
    {
        list($client, $cookieJar) = $arguments;

        $client->request('GET', 'http://sulu.lo');
        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $this->assertStringContainsString('fresh', $response->headers->get('x-symfony-cache'));

        return [$client, $cookieJar];
    }

    #[\PHPUnit\Framework\Attributes\Depends('testSwitchedSegmentIsACachHit')]
    public function testSwitchSegmentBackIsACacheHit($arguments)
    {
        list($client, $cookieJar) = $arguments;

        $client->request('GET', 'http://sulu.lo/_sulu_segment_switch?segment=w&url=http://sulu.lo');
        $response = $client->getResponse();

        $this->assertHttpStatusCode(302, $response);
        $this->assertStringContainsString('miss', $response->headers->get('x-symfony-cache'));
        $this->assertCount(1, $response->headers->getCookies());

        $client->request('GET', 'http://sulu.lo');
        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $this->assertStringContainsString('fresh', $response->headers->get('x-symfony-cache'));

        return [$client, $cookieJar];
    }
}
