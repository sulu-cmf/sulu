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

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\EventListener\SegmentSubscriber;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SegmentSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SegmentSubscriber
     */
    private $segmentSubscriber;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    public function setUp(): void
    {
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->segmentSubscriber = new SegmentSubscriber('X-Sulu-Segment', $this->requestAnalyzer->reveal(), '_ss');
    }

    public function testAddVaryHeaderWithSegmentedWebspace(): void
    {
        $request = new Request();
        $response = new Response();

        $webspace = new Webspace();
        $webspace->addSegment(new Segment());
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->segmentSubscriber->addVaryHeader($this->createResponseEvent($request, $response));

        $this->assertEquals(['X-Sulu-Segment'], $response->getVary());
    }

    public function testAddVaryHeaderWithoutSegmentedWebspace(): void
    {
        $request = new Request();
        $response = new Response();
        $webspace = new Webspace();
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->segmentSubscriber->addVaryHeader($this->createResponseEvent($request, $response));

        $this->assertEquals([], $response->getVary());
    }

    public static function provideAddCookieHeader()
    {
        return [
            [['s', 'w'], 's', 's', 'w', null, 0],
            [['s', 'w'], 's', 'w', 'w', null, 1],
            [['s', 'w'], 's', 'w', 's', 'w', 1],
            [['s', 'w'], null, null, 's', null, 0],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAddCookieHeader')]
    public function testAddCookieHeader(
        $segmentKeys,
        $cookieSegmentKey,
        $currentSegmentKey,
        $defaultSegmentKey,
        $expectedSegmentKey,
        $expecedCookiCount
    ): void {
        $request = new Request();
        $response = new Response();

        $webspace = new Webspace();
        $segments = [];
        foreach ($segmentKeys as $segmentKey) {
            $segment = new Segment();
            $segment->setKey($segmentKey);
            $segments[$segmentKey] = $segment;
            $webspace->addSegment($segment);
        }

        $webspace->setDefaultSegment($segments[$defaultSegmentKey]);

        $request->cookies->add(['_ss' => $cookieSegmentKey]);
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getSegment()->willReturn($segments[$currentSegmentKey] ?? null);

        $this->segmentSubscriber->addCookieHeader($this->createResponseEvent($request, $response));

        if ($expecedCookiCount > 0) {
            $cookie = $response->headers->getCookies()[0];
            $this->assertEquals('_ss', $cookie->getName());
            $this->assertEquals($expectedSegmentKey, $cookie->getValue());
        } else {
            $this->assertCount(0, $response->headers->getCookies());
        }
    }

    private function createResponseEvent(Request $request, Response $response): ResponseEvent
    {
        $kernel = $this->prophesize(HttpKernelInterface::class);

        return new ResponseEvent(
            $kernel->reveal(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );
    }
}
