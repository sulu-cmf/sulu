<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Preview\Renderer;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PreviewBundle\Preview\Events;
use Sulu\Bundle\PreviewBundle\Preview\Events\PreRenderEvent;
use Sulu\Bundle\PreviewBundle\Preview\Exception\RouteDefaultsProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TemplateNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TwigException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\UnexpectedException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\WebspaceLocalizationNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\WebspaceNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\KernelFactoryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRenderer;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Error\RuntimeError;

class PreviewRendererTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RouteDefaultsProviderInterface>
     */
    private $routeDefaultsProvider;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var ObjectProphecy<KernelFactoryInterface>
     */
    private $kernelFactory;

    /**
     * @var ObjectProphecy<HttpKernelInterface>
     */
    private $httpKernel;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $eventDispatcher;

    /**
     * @var PreviewRendererInterface
     */
    private $renderer;

    /**
     * @var array
     */
    private $previewDefault = [];

    /**
     * @var string
     */
    private $environment = 'prod';

    public function setUp(): void
    {
        $this->routeDefaultsProvider = $this->prophesize(RouteDefaultsProviderInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->kernelFactory = $this->prophesize(KernelFactoryInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->httpKernel = $this->prophesize(HttpKernelInterface::class);
        $this->kernelFactory->create($this->environment)->willReturn($this->httpKernel->reveal());

        $this->renderer = new PreviewRenderer(
            $this->routeDefaultsProvider->reveal(),
            $this->requestStack->reveal(),
            $this->kernelFactory->reveal(),
            $this->webspaceManager->reveal(),
            $this->eventDispatcher->reveal(),
            $this->previewDefault,
            $this->environment,
            'X-Sulu-Target-Group'
        );
    }

    public static function portalDataProvider()
    {
        return [
            [
                'http',
                'sulu.lo',
            ],
            [
                'https',
                'sulu.lo',
            ],
        ];
    }

    public function portalWithoutRequestDataProvider()
    {
        return [
            [
                'sulu.lo',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('portalDataProvider')]
    public function testRender($scheme, $portalUrl): void
    {
        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn($portalUrl);
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldBeCalled();

        $this->render($object, $scheme, $portalUrl);
    }

    /**
     * @param string $expectedScheme
     * @param string $expectedHost
     * @param int $expectedPort
     * @param bool $hasRequest
     */
    protected function render(
        ObjectProphecy $object,
        $expectedScheme = 'http',
        $expectedHost = 'sulu.lo',
        $expectedPort = 8080,
        $hasRequest = true
    ) {
        $server = [
            'SERVER_NAME' => 'admin-sulu.io',
            'HTTP_HOST' => 'admin-sulu.io:' . $expectedPort,
            'SERVER_PORT' => $expectedPort,
        ];

        if ('https' === $expectedScheme) {
            $server['HTTPS'] = true;
        }

        $this->httpKernel->handle(
            Argument::that(
                function(Request $request) use ($expectedScheme, $expectedHost, $expectedPort) {
                    return $request->getHost() === $expectedHost
                        && $request->getPort() === $expectedPort
                        && $request->getScheme() === $expectedScheme;
                }
            ),
            HttpKernelInterface::MAIN_REQUEST,
            false
        )->shouldBeCalled()->willReturn(new Response('<title>Hallo</title>'));

        $request = null;

        if ($hasRequest) {
            $request = new Request([], [], [], [], [], $server);
        }

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $response = $this->renderer->render($object->reveal(), 1, true, ['webspaceKey' => 'sulu_io', 'locale' => 'de']);
        $this->assertEquals('<title>Hallo</title>', $response);
    }

    public function testRenderWithTargetGroup(): void
    {
        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldBeCalled();

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->httpKernel->handle(
            Argument::that(function(Request $request) {
                return 2 == $request->headers->get('X-Sulu-Target-Group');
            }),
            HttpKernelInterface::MAIN_REQUEST,
            false
        )->shouldBeCalled()->willReturn(new Response('<title>Hallo</title>'));

        $response = $this->renderer->render(
            $object->reveal(),
            1,
            true,
            ['webspaceKey' => 'sulu_io', 'locale' => 'de', 'targetGroupId' => 2]
        );
        $this->assertEquals('<title>Hallo</title>', $response);
    }

    public function testRenderWithSegment(): void
    {
        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $segment = new Segment();
        $segment->setKey('w');
        $webspace->getSegment('w')->willReturn($segment);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldBeCalled();

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->httpKernel->handle(
            Argument::that(function(Request $request) use ($segment) {
                return $request->attributes->get('_sulu')->getAttribute('segment') === $segment;
            }),
            HttpKernelInterface::MAIN_REQUEST,
            false
        )->shouldBeCalled()->willReturn(new Response('<title>Hallo</title>'));

        $response = $this->renderer->render(
            $object->reveal(),
            1,
            true,
            ['webspaceKey' => 'sulu_io', 'locale' => 'de', 'segmentKey' => 'w']
        );
        $this->assertEquals('<title>Hallo</title>', $response);
    }

    public function testRenderWithDateTime(): void
    {
        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldBeCalled();

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $dateTimeString = '2020-12-10T18:29:15';

        $this->httpKernel->handle(
            Argument::that(function(Request $request) use ($dateTimeString) {
                $dateTime = $request->attributes->get('_sulu')->getAttribute('dateTime');

                return $dateTime->getTimestamp() === (new \DateTime($dateTimeString))->getTimestamp();
            }),
            HttpKernelInterface::MAIN_REQUEST,
            false
        )->shouldBeCalled()->willReturn(new Response('<title>Hallo</title>'));

        $response = $this->renderer->render(
            $object->reveal(),
            1,
            true,
            ['webspaceKey' => 'sulu_io', 'locale' => 'de', 'dateTime' => $dateTimeString]
        );
        $this->assertEquals('<title>Hallo</title>', $response);
    }

    public function testRenderPortalNotFound(): void
    {
        $object = $this->prophesize(\stdClass::class);

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([]);

        $webspace = new Webspace();
        $localization = new Localization('de');
        $webspace->addLocalization($localization);
        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MAIN_REQUEST, false)
            ->willReturn(new Response('<title>Hallo</title>'));

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $response = $this->renderer->render($object->reveal(), 1, true, ['webspaceKey' => 'sulu_io', 'locale' => 'de']);

        $this->assertEquals('<title>Hallo</title>', $response);
    }

    public function testRenderWebspaceNotFound(): void
    {
        $this->expectException(WebspaceNotFoundException::class);
        $object = new \stdClass();

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->routeDefaultsProvider->supports(\get_class($object))->willReturn(true);

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('not_existing', 'de', $this->environment)
            ->willReturn([]);

        $this->webspaceManager->findWebspaceByKey('not_existing')->willReturn(null);

        $this->renderer->render($object, 1, true, ['webspaceKey' => 'not_existing', 'locale' => 'de']);
    }

    public function testRenderWebspaceLocalizationNotFound(): void
    {
        $this->expectException(WebspaceLocalizationNotFoundException::class);

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $object = new \stdClass();
        $this->routeDefaultsProvider->supports(\get_class($object))->willReturn(true);

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([]);

        $webspace = new Webspace();
        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace);

        $this->renderer->render($object, 1, true, ['webspaceKey' => 'sulu_io', 'locale' => 'de']);
    }

    public function testRenderRouteDefaultsProviderNotFound(): void
    {
        $this->expectException(RouteDefaultsProviderNotFoundException::class);
        $this->expectExceptionCode(9902);

        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(false);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->shouldNotBeCalled();

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldNotBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MAIN_REQUEST, false)
            ->shouldNotBeCalled();

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, true, ['webspaceKey' => 'sulu_io', 'locale' => 'de']);
    }

    public function testRenderTwigError(): void
    {
        $this->expectException(TwigException::class);
        $this->expectExceptionCode(9903);

        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MAIN_REQUEST, false)
            ->shouldBeCalled()->willThrow(new RuntimeError('Test error'));

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, true, ['webspaceKey' => 'sulu_io', 'locale' => 'de']);
    }

    public function testRenderInvalidArgumentException(): void
    {
        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionCode(9904);

        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MAIN_REQUEST, false)
            ->shouldBeCalled()->willThrow(new \InvalidArgumentException());

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, true, ['webspaceKey' => 'sulu_io', 'locale' => 'de']);
    }

    public function testRenderHttpExceptionWithPreviousException(): void
    {
        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionCode(9904);

        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MAIN_REQUEST, false)
            ->shouldBeCalled()->willThrow(
                new HttpException(406, 'Error encountered when rendering content', new \InvalidArgumentException())
            );

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, true, ['webspaceKey' => 'sulu_io', 'locale' => 'de']);
    }

    public function testRenderHttpExceptionWithoutPreviousException(): void
    {
        $this->expectException(UnexpectedException::class);
        $this->expectExceptionCode(9905);

        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu.lo');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldBeCalled();

        $this->httpKernel->handle(Argument::type(Request::class), HttpKernelInterface::MAIN_REQUEST, false)
            ->shouldBeCalled()->willThrow(
                new HttpException(406, 'Error encountered when rendering content')
            );

        $request = new Request();
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, true, ['webspaceKey' => 'sulu_io', 'locale' => 'de']);
    }

    public function testRenderRequestWithServerAttributes(): void
    {
        $object = $this->prophesize(\stdClass::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $webspace = $this->prophesize(Webspace::class);
        $localization = new Localization('de');
        $webspace->getLocalization('de')->willReturn($localization);
        $portalInformation->getWebspace()->willReturn($webspace->reveal());
        $portalInformation->getPortal()->willReturn($this->prophesize(Portal::class)->reveal());
        $portalInformation->getUrl()->willReturn('sulu-preview-test.io');
        $portalInformation->getPrefix()->willReturn('/de');

        $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale('sulu_io', 'de', $this->environment)
            ->willReturn([$portalInformation->reveal()]);

        $this->routeDefaultsProvider->supports(\get_class($object->reveal()))->willReturn(true);
        $this->routeDefaultsProvider->getByEntity(\get_class($object->reveal()), 1, 'de', $object)
            ->willReturn(['object' => $object, '_controller' => 'SuluTestBundle:Test:render']);

        $this->eventDispatcher->dispatch(Argument::type(PreRenderEvent::class), Events::PRE_RENDER)
            ->shouldBeCalled();

        $server = [
            'SERVER_NAME' => 'sulu-preview-test.io',
            'HOST_NAME' => 'sulu-preview-test.io',
            'SERVER_PORT' => 8080,
            'X-Forwarded-Host' => 'forwarded.sulu.io',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Port' => 8081,
            'HTTP_X_REQUESTED_WITH' => 'XmlHttpRequest',
            'HTTP_USER_AGENT' => 'Sulu/Preview',
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
        ];

        $this->httpKernel->handle(
            Argument::that(
                function(Request $request) use ($server) {
                    foreach ($server as $key => $expectedValue) {
                        $value = $request->server->get($key);

                        if ('HTTP_X_REQUESTED_WITH' === $key) {
                            $expectedValue = null;
                        }

                        $this->assertEquals(
                            $expectedValue,
                            $value,
                            \sprintf(
                                'Expected for $_SERVER["%s"]: "%s" but "%s" was given',
                                $key,
                                $expectedValue,
                                $value
                            )
                        );
                    }

                    $requestAttributes = $request->attributes->get('_sulu');

                    $this->assertTrue($request->attributes->get('preview'));
                    $this->assertTrue($request->attributes->get('partial'));
                    $this->assertSame(['noIndex' => true, 'noFollow' => true], $request->attributes->get('_seo'));
                    $this->assertEquals('sulu-preview-test.io', $requestAttributes->getAttribute('host'));
                    $this->assertEquals(8080, $requestAttributes->getAttribute('port'));
                    $this->assertEqualsWithDelta(new \DateTime(), $requestAttributes->getAttribute('dateTime'), 1);

                    // Assert equals will throw exception so also true can be returned.
                    return true;
                }
            ),
            HttpKernelInterface::MAIN_REQUEST,
            false
        )->shouldBeCalled()->willReturn(new Response('<title>Hallo</title>'));

        $request = new Request([], [], [], [], [], $server, []);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->renderer->render($object->reveal(), 1, true, ['webspaceKey' => 'sulu_io', 'locale' => 'de']);
    }
}
