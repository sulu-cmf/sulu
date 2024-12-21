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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\EventListener\AppendAnalyticsListener;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class AppendAnalyticsListenerTest extends TestCase
{
    use ProphecyTrait;

    public static function formatProvider()
    {
        return [['json'], ['xml']];
    }

    /**
     * @param string $format
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('formatProvider')]
    public function testAppendFormatNoEffect($format): void
    {
        $engine = $this->prophesize(Environment::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepositoryInterface::class);
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod'
        );

        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->willReturn($format);
        $response = $this->prophesize(Response::class);
        $response->reveal()->headers = new ResponseHeaderBag(['Content-Type' => 'text/plain']);
        $response->getContent()->shouldNotBeCalled();
        $requestAnalyzer->getPortalInformation()->shouldNotBeCalled();
        $event = $this->createResponseEvent($request->reveal(), $response->reveal());

        $listener->onResponse($event);

        $engine->render(Argument::any(), Argument::any())->shouldNotBeCalled();
        $response->setContent(Argument::any())->shouldNotBeCalled()
            ->willReturn($response->reveal());
    }

    public function testBinaryFileResponse(): void
    {
        $engine = $this->prophesize(Environment::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepositoryInterface::class);
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod'
        );

        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->willReturn('html');
        $response = $this->prophesize(BinaryFileResponse::class);
        $response->reveal()->headers = new ResponseHeaderBag(['Content-Type' => 'text/html']);
        $response->getContent()->willReturn(false)->shouldBeCalled();
        $requestAnalyzer->getPortalInformation()->shouldNotBeCalled();
        $event = $this->createResponseEvent($request->reveal(), $response->reveal());

        $listener->onResponse($event);

        $engine->render(Argument::any(), Argument::any())->shouldNotBeCalled();
        $response->setContent(Argument::any())->shouldNotBeCalled()
            ->willReturn($response->reveal());
    }

    public function testAppendFormat(): void
    {
        $engine = $this->prophesize(Environment::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepositoryInterface::class);
        $analytics = $this->prophesize(AnalyticsInterface::class);

        $analytics->getType()->willReturn('google');
        $analytics->getContent()->willReturn('<script>var i = 0;</script>');
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('sulu.lo/{localization}');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')->willReturn('sulu.lo/{localization}');

        $analyticsRepository->findByUrl('sulu.lo/{localization}', 'sulu_io', 'prod')
            ->willReturn([$analytics])
            ->shouldBeCalled();
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod'
        );

        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->willReturn('html');
        $response = $this->prophesize(Response::class);
        $response->reveal()->headers = new ResponseHeaderBag(['Content-Type' => 'text/html']);
        $event = $this->createResponseEvent($request->reveal(), $response->reveal());

        $loader = $this->prophesize(FilesystemLoader::class);
        $engine->getLoader()->shouldBeCalled()->willReturn($loader->reveal());

        $loader->exists('@SuluWebsite/Analytics/google/head-open.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('@SuluWebsite/Analytics/google/head-open.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $loader->exists('@SuluWebsite/Analytics/google/head-close.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('@SuluWebsite/Analytics/google/head-close.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<script>var i = 0;</script>');

        $loader->exists('@SuluWebsite/Analytics/google/body-open.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('@SuluWebsite/Analytics/google/body-open.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $loader->exists('@SuluWebsite/Analytics/google/body-close.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('@SuluWebsite/Analytics/google/body-close.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $response->getContent()->willReturn('<html><head><title>Test</title></head><body><h1>Title</h1></body></html>');
        $response->setContent(
            '<html><head><title>Test</title><script>var i = 0;</script></head><body><h1>Title</h1></body></html>'
        )->shouldBeCalled()->willReturn($response->reveal());
        $listener->onResponse($event);
    }

    public function testAppendWildcard(): void
    {
        $engine = $this->prophesize(Environment::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepositoryInterface::class);
        $analytics = $this->prophesize(AnalyticsInterface::class);
        $analytics->getType()->willReturn('google');
        $analytics->getContent()->willReturn('<script>var i = 0;</script>');

        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('*.sulu.lo/*');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_WILDCARD);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')->willReturn('1.sulu.lo/2');

        $analyticsRepository->findByUrl('1.sulu.lo/2', 'sulu_io', 'prod')->willReturn([$analytics]);
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod'
        );

        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->willReturn('html');
        $request->getHost()->willReturn('1.sulu.lo');
        $request->getRequestUri()->willReturn('/2');
        $response = $this->prophesize(Response::class);
        $response->reveal()->headers = new ResponseHeaderBag(['Content-Type' => 'text/html']);
        $event = $this->createResponseEvent($request->reveal(), $response->reveal());

        $loader = $this->prophesize(FilesystemLoader::class);
        $engine->getLoader()->shouldBeCalled()->willReturn($loader->reveal());

        $loader->exists('@SuluWebsite/Analytics/google/head-open.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('@SuluWebsite/Analytics/google/head-open.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $loader->exists('@SuluWebsite/Analytics/google/head-close.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('@SuluWebsite/Analytics/google/head-close.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<script>var i = 0;</script>');

        $loader->exists('@SuluWebsite/Analytics/google/body-open.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('@SuluWebsite/Analytics/google/body-open.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $loader->exists('@SuluWebsite/Analytics/google/body-close.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('@SuluWebsite/Analytics/google/body-close.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $response->getContent()->willReturn('<html><head><title>Test</title></head><body><h1>Title</h1></body></html>');
        $response->setContent(
            '<html><head><title>Test</title><script>var i = 0;</script></head><body><h1>Title</h1></body></html>'
        )->shouldBeCalled()->willReturn($response->reveal());

        $listener->onResponse($event);
    }

    public function testAppendNoUrlExpression(): void
    {
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('sulu.lo/{localization}');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')
            ->willReturn(null)
            ->shouldBeCalled();

        $engine = $this->prophesize(Environment::class);

        $analyticsRepository = $this->prophesize(AnalyticsRepositoryInterface::class);
        $analyticsRepository->findByUrl(Argument::cetera())
            ->shouldNotBeCalled();

        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->willReturn('html');

        $response = $this->prophesize(Response::class);
        $response->getContent()->willReturn('<html><head><title>Test</title></head><body><h1>Title</h1></body></html>');
        $response->reveal()->headers = new ResponseHeaderBag(['Content-Type' => 'text/html']);
        $response->setContent(Argument::cetera())
            ->shouldNotBeCalled();

        $event = $this->createResponseEvent($request->reveal(), $response->reveal());

        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod'
        );

        $listener->onResponse($event);
    }

    public function testAppendGoogleTagManager(): void
    {
        $engine = $this->prophesize(Environment::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepositoryInterface::class);
        $analytics = $this->prophesize(AnalyticsInterface::class);

        $analytics->getType()->willReturn('google_tag_manager');
        $analytics->getContent()->willReturn('<script>var i = 0;</script>');
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('sulu.lo/{localization}');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')->willReturn('sulu.lo/{localization}');

        $analyticsRepository->findByUrl('sulu.lo/{localization}', 'sulu_io', 'prod')
            ->willReturn([$analytics])
            ->shouldBeCalled();
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod'
        );

        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->willReturn('html');
        $response = $this->prophesize(Response::class);
        $response->reveal()->headers = new ResponseHeaderBag(['Content-Type' => 'text/html']);
        $event = $this->createResponseEvent($request->reveal(), $response->reveal());

        $loader = $this->prophesize(FilesystemLoader::class);
        $engine->getLoader()->shouldBeCalled()->willReturn($loader->reveal());

        $loader->exists('@SuluWebsite/Analytics/google_tag_manager/head-open.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('@SuluWebsite/Analytics/google_tag_manager/head-open.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<script>var i = 0;</script>');

        $loader->exists('@SuluWebsite/Analytics/google_tag_manager/head-close.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('@SuluWebsite/Analytics/google_tag_manager/head-close.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $loader->exists('@SuluWebsite/Analytics/google_tag_manager/body-open.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('@SuluWebsite/Analytics/google_tag_manager/body-open.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<noscript><div>Blabla</div></noscript>');

        $loader->exists('@SuluWebsite/Analytics/google_tag_manager/body-close.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('@SuluWebsite/Analytics/google_tag_manager/body-close.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $response->getContent()
            ->willReturn('<html><head><title>Test</title></head><body class="test"><h1>Title</h1></body></html>');
        $response->setContent(
            '<html><head><script>var i = 0;</script><title>Test</title></head><body class="test"><noscript><div>Blabla</div></noscript><h1>Title</h1></body></html>'
        )->shouldBeCalled()->willReturn($response->reveal());
        $listener->onResponse($event);
    }

    public function testAppendPiwik(): void
    {
        $engine = $this->prophesize(Environment::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepositoryInterface::class);
        $analytics = $this->prophesize(AnalyticsInterface::class);

        $analytics->getType()->willReturn('piwik');
        $analytics->getContent()->willReturn('<script>var i = 0;</script>');
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('sulu.lo/{localization}');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')->willReturn('sulu.lo/{localization}');

        $analyticsRepository->findByUrl('sulu.lo/{localization}', 'sulu_io', 'prod')
            ->willReturn([$analytics])
            ->shouldBeCalled();
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod'
        );

        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->willReturn('html');
        $response = $this->prophesize(Response::class);
        $response->reveal()->headers = new ResponseHeaderBag(['Content-Type' => 'text/html']);
        $event = $this->createResponseEvent($request->reveal(), $response->reveal());

        $loader = $this->prophesize(FilesystemLoader::class);
        $engine->getLoader()->shouldBeCalled()->willReturn($loader->reveal());

        $loader->exists('@SuluWebsite/Analytics/piwik/head-open.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('@SuluWebsite/Analytics/piwik/head-open.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $loader->exists('@SuluWebsite/Analytics/piwik/head-close.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('@SuluWebsite/Analytics/piwik/head-close.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<script>var i = 0;</script>');

        $loader->exists('@SuluWebsite/Analytics/piwik/body-open.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('@SuluWebsite/Analytics/piwik/body-open.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<noscript><div>Blabla</div></noscript>');

        $loader->exists('@SuluWebsite/Analytics/piwik/body-close.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('@SuluWebsite/Analytics/piwik/body-close.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $response->getContent()
            ->willReturn('<html><head><title>Test</title></head><body class="test"><h1>Title</h1></body></html>');
        $response->setContent(
            '<html><head><title>Test</title><script>var i = 0;</script></head><body class="test"><noscript><div>Blabla</div></noscript><h1>Title</h1></body></html>'
        )->shouldBeCalled()->willReturn($response->reveal());
        $listener->onResponse($event);
    }

    public function testAppendCustom(): void
    {
        $engine = $this->prophesize(Environment::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepositoryInterface::class);
        $analytics = $this->prophesize(AnalyticsInterface::class);

        $analytics->getType()->willReturn('custom');
        $analytics->getContent()->willReturn('{"position":"headOpen","value":"<script>XYZ</script>"}');
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('sulu.lo/{localization}');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')->willReturn('sulu.lo/{localization}');

        $analyticsRepository->findByUrl('sulu.lo/{localization}', 'sulu_io', 'prod')
            ->willReturn([$analytics])
            ->shouldBeCalled();
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod'
        );

        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->willReturn('html');
        $response = $this->prophesize(Response::class);
        $response->reveal()->headers = new ResponseHeaderBag(['Content-Type' => 'text/html']);
        $event = $this->createResponseEvent($request->reveal(), $response->reveal());

        $loader = $this->prophesize(FilesystemLoader::class);
        $engine->getLoader()->shouldBeCalled()->willReturn($loader->reveal());

        $loader->exists('@SuluWebsite/Analytics/custom/head-open.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('@SuluWebsite/Analytics/custom/head-open.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<script>var nice_var = false;</script>');

        $loader->exists('@SuluWebsite/Analytics/custom/head-close.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('@SuluWebsite/Analytics/custom/head-close.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('');

        $loader->exists('@SuluWebsite/Analytics/custom/body-open.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('@SuluWebsite/Analytics/custom/body-open.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('');

        $loader->exists('@SuluWebsite/Analytics/custom/body-close.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('@SuluWebsite/Analytics/custom/body-close.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('');

        $response->getContent()
            ->willReturn('<html><head maybe-a-attribute-here="true"><title>Test</title></head><body><header><h1>Title</h1></header></body></html>');
        $response->setContent(
            '<html><head maybe-a-attribute-here="true"><script>var nice_var = false;</script><title>Test</title></head><body><header><h1>Title</h1></header></body></html>'
        )->shouldBeCalled()->willReturn($response->reveal());
        $listener->onResponse($event);
    }

    public function testAppendPreview(): void
    {
        $engine = $this->prophesize(Environment::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepositoryInterface::class);

        $analyticsRepository->findByUrl(Argument::cetera())
            ->shouldNotBeCalled();
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod',
            true
        );

        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->shouldNotBeCalled();
        $response = $this->prophesize(Response::class);
        $response->getContent()->shouldNotBeCalled();
        $event = $this->createResponseEvent($request->reveal(), $response->reveal());

        $listener->onResponse($event);
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
