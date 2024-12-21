<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\WebsiteBundle\Controller\RedirectController;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RedirectControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var RedirectController
     */
    private $controller;

    protected function setUp(): void
    {
        /** @var RouterInterface $router */
        $router = $this->prophesize(RouterInterface::class);
        $router->generate(Argument::any(), Argument::any(), Argument::any())->willReturn('/test-route');
        $this->controller = new RedirectController($router->reveal());
    }

    private function getRequestMock($requestUrl, $portalUrl, $redirectUrl = null, $prefix = '')
    {
        $request = $this->prophesize(Request::class);
        $request->get('url', Argument::cetera())->willReturn($portalUrl);
        $request->get('redirect', Argument::cetera())->willReturn($redirectUrl);
        $request->get('_sulu', Argument::cetera())->willReturn(
            new RequestAttributes(['resourceLocatorPrefix' => $prefix])
        );
        $request->getUri()->willReturn($requestUrl);

        return $request->reveal();
    }

    public static function provideRedirectAction()
    {
        return [
            ['http://sulu.lo/articles?foo=bar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar'],
            ['http://sulu.lo/articles?foo=bar&bar=boo', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar&bar=boo'],
            ['http://sulu.lo/articles/?foo=bar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar'],
            ['http://sulu.lo/articles/?foo=bar&bar=boo', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar&bar=boo'],
            ['http://sulu.lo/en/articles/?foo=bar', 'sulu.lo', null, 'http://sulu.lo/en/articles?foo=bar'],
            ['http://sulu.lo/en/articles/?foo=bar&bar=boo', 'sulu.lo', null, 'http://sulu.lo/en/articles?foo=bar&bar=boo'],
            ['sulu.lo:8001/', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en'],
            ['sulu.lo:8001/#foobar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en#foobar'],
            ['sulu.lo:8001/articles#foobar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en/articles#foobar'],
            ['sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo', 'http://sulu.lo'],
            ['sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo', 'http://sulu.lo'],
            ['http://sulu.lo:8002/', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8002/en'],
            ['http://sulu.lo/articles', 'sulu.lo/en', 'sulu.lo/de', 'http://sulu.lo/de/articles'],
            ['http://sulu.lo/events', 'sulu.lo/events', 'sulu.lo/events/de', 'http://sulu.lo/events/de', '/events'],
            ['http://sulu.lo/events/articles', 'sulu.lo/events', 'sulu.lo/events/de', 'http://sulu.lo/events/de/articles', '/events'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRedirectAction')]
    public function testRedirectAction($requestUri, $portalUrl, $redirectUrl, $expectedTargetUrl, $prefix = ''): void
    {
        $request = $this->getRequestMock($requestUri, $portalUrl, $redirectUrl, $prefix);

        $response = $this->controller->redirectWebspaceAction($request);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals($expectedTargetUrl, $response->getTargetUrl());
    }

    public static function provideRedirectWebspaceAction()
    {
        return [
            ['sulu.lo/de', 'http://sulu.lo', 'http://sulu.lo/de'],
            ['http://sulu.lo/de', 'http://sulu.lo', 'http://sulu.lo/de'],
            ['http://sulu.lo', 'http://sulu.lo/de/test', 'http://sulu.lo/de/test'],
            ['sulu.lo/de', 'http://sulu.lo?test1=value1', 'http://sulu.lo/de?test1=value1'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRedirectWebspaceAction')]
    public function testRedirectWebspaceAction($uri, $redirectUri, $expectedTargetUrl): void
    {
        $request = $this->getRequestMock($redirectUri, null, $uri);

        $response = $this->controller->redirectWebspaceAction($request);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals($expectedTargetUrl, $response->getTargetUrl());
    }

    public static function provideRedirectToRouteAction()
    {
        return [
            ['', 410],
            ['', 404, false],
            ['test'],
            ['test', 302, false],
            ['test', 302, false, ['test' => 1]],
            ['test', 301, true, ['test' => 1]],
            ['test', 302, false, [], ['test' => 1]],
            ['test', 301, true, [], ['test' => 1]],
            ['test', 302, false, ['test-1' => 1], ['test-2' => 1]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRedirectToRouteAction')]
    public function testRedirectToRouteAction(
        $route,
        $statusCode = 301,
        $permanent = true,
        $attributesData = [],
        $queryData = []
    ): void {
        if ($statusCode >= 400) {
            $this->expectException(HttpException::class);
        }

        $attributes = $this->prophesize(ParameterBag::class);
        $attributes->get('_route_params')->willReturn(
            \array_merge($attributesData, ['route' => $route, 'permanent' => $permanent])
        );

        $router = $this->prophesize(RouterInterface::class);
        $router->generate($route, \array_merge($attributesData, $queryData), UrlGeneratorInterface::ABSOLUTE_URL)->willReturn('/test-route');

        $request = $this->prophesize(Request::class);
        $request->reveal()->attributes = $attributes->reveal();
        $request->reveal()->query = new InputBag([]);

        $response = $this->controller->redirectToRouteAction($request->reveal(), $route, $permanent);

        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals('/test-route', $response->getTargetUrl());
    }
}
