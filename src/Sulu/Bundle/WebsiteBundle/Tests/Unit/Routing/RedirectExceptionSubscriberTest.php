<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\EventListener\RedirectExceptionSubscriber;
use Sulu\Bundle\WebsiteBundle\Locale\DefaultLocaleProviderInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Route;

class RedirectExceptionSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestMatcherInterface>
     */
    private $router;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<DefaultLocaleProviderInterface>
     */
    private $defaultLocaleProvider;

    /**
     * @var ObjectProphecy<ReplacerInterface>
     */
    private $urlReplacer;

    /**
     * @var RedirectExceptionSubscriber
     */
    private $exceptionListener;

    /**
     * @var ObjectProphecy<RequestAttributes>
     */
    private $attributes;

    /**
     * @var ObjectProphecy<Request>
     */
    private $request;

    /**
     * @var ExceptionEvent
     */
    private $event;

    /**
     * @var ObjectProphecy<HttpKernelInterface>
     */
    private $kernel;

    protected function setUp(): void
    {
        $this->kernel = $this->prophesize(HttpKernelInterface::class);
        $this->router = $this->prophesize(RequestMatcherInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $this->urlReplacer = $this->prophesize(ReplacerInterface::class);

        $this->exceptionListener = new RedirectExceptionSubscriber(
            $this->router->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->defaultLocaleProvider->reveal(),
            $this->urlReplacer->reveal()
        );

        $this->attributes = $this->prophesize(RequestAttributes::class);
        $this->request = $this->prophesize(Request::class);
        $this->request->getUri()->willReturn('sulu.lo');
        $this->request->getSchemeAndHttpHost()->willReturn('http://sulu.lo');
        $this->request->reveal()->attributes = new ParameterBag(['_sulu' => $this->attributes->reveal()]);

        $this->event = new ExceptionEvent(
            $this->kernel->reveal(),
            $this->request->reveal(),
            HttpKernelInterface::MAIN_REQUEST,
            new NotFoundHttpException()
        );
    }

    public function testRedirectTrailingSlash(): void
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/test/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->request->getPathInfo()->willReturn('/de/test/');
        $this->request->getRequestFormat()->willReturn('html');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('/de/test', $redirectResponse->getTargetUrl());
    }

    public function testRedirectSlashToHomepage(): void
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn(null);

        $this->request->getPathInfo()->willReturn('//');
        $this->request->getRequestFormat()->willReturn('html');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('/', $redirectResponse->getTargetUrl());
    }

    public function testRedirectDoubleSlashToHomepage(): void
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('//');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn(null);

        $this->request->getPathInfo()->willReturn('///');
        $this->request->getRequestFormat()->willReturn('html');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('/', $redirectResponse->getTargetUrl());
    }

    public function testRedirectTrailingHtml(): void
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/test');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->request->getPathInfo()->willReturn('/de/test.html');
        $this->request->getRequestFormat()->willReturn('html');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('/de/test', $redirectResponse->getTargetUrl());
    }

    public function testRedirectJson(): void
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/test');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->request->getPathInfo()->willReturn('/de/test.json');
        $this->request->getRequestFormat()->willReturn('json');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event);

        $this->assertNull($this->event->getResponse());
    }

    public function testRedirectTrailingHtmlNotExists(): void
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/test');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->request->getPathInfo()->willReturn('/de/test.html');
        $this->request->getRequestFormat()->willReturn('html');

        $this->router->matchRequest(Argument::type(Request::class))->willThrow(new ResourceNotFoundException());

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event);

        $this->assertNull($this->event->getResponse());
    }

    public function testRedirectPartialMatchNoLocalization(): void
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn(null);
        $this->attributes->getAttribute('portalUrl', null)->willReturn(null);

        $this->defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de'));

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectPartialMatch($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('http://sulu.lo', $redirectResponse->getTargetUrl());
    }

    public function testRedirectPartialMatchNoLocalizationRedirect(): void
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_REDIRECT);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn(null);
        $this->attributes->getAttribute('portalUrl', null)->willReturn(null);

        $this->defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de'));

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectPartialMatch($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('http://sulu.lo', $redirectResponse->getTargetUrl());
    }

    public function testRedirectPartialMatchSlashOnly(): void
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_REDIRECT);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo/de');
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo/de/');

        $this->defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de'));

        $this->urlReplacer->replaceCountry(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');
        $this->urlReplacer->replaceLanguage(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');
        $this->urlReplacer->replaceLocalization(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectPartialMatch($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('http://sulu.lo/de', $redirectResponse->getTargetUrl());
    }

    public function testRedirectPartialMatchSlashOnlyWithFormat(): void
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_REDIRECT);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo/de');
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo/de/');

        $this->defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de'));

        $this->urlReplacer->replaceCountry(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');
        $this->urlReplacer->replaceLanguage(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');
        $this->urlReplacer->replaceLocalization(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->request->getUri()->willReturn('sulu.lo/.json');

        $this->exceptionListener->redirectPartialMatch($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('http://sulu.lo/de.json', $redirectResponse->getTargetUrl());
    }

    public function testRedirectPartialMatch(): void
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo/{localization}');
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo');

        $localization = new Localization('de', 'at');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->urlReplacer->replaceCountry('sulu.lo/{localization}', 'at')
            ->shouldBeCalled()
            ->willReturn('sulu.lo/{localization}');
        $this->urlReplacer->replaceLanguage('sulu.lo/{localization}', 'de')
            ->shouldBeCalled()
            ->willReturn('sulu.lo/{localization}');
        $this->urlReplacer->replaceLocalization('sulu.lo/{localization}', 'de-at')
            ->shouldBeCalled()
            ->willReturn('sulu.lo/de-at');

        $this->router->matchRequest(
            Argument::that(
                function(Request $request) {
                    return 'http://sulu.lo/de-at' === $request->getUri();
                }
            )
        )->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectPartialMatch($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('http://sulu.lo/de-at', $redirectResponse->getTargetUrl());
    }

    public function testRedirectPartialMatchNotExists(): void
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo/{localization}');
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo');

        $localization = new Localization('de', 'at');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->urlReplacer->replaceCountry('sulu.lo/{localization}', 'at')->shouldBeCalled()->willReturn(
            'sulu.lo/{localization}'
        );
        $this->urlReplacer->replaceLanguage('sulu.lo/{localization}', 'de')->shouldBeCalled()->willReturn(
            'sulu.lo/{localization}'
        );
        $this->urlReplacer->replaceLocalization('sulu.lo/{localization}', 'de-at')->shouldBeCalled()->willReturn(
            'sulu.lo/de-at'
        );

        $this->router->matchRequest(Argument::type(Request::class))->willThrow(new ResourceNotFoundException());

        $this->exceptionListener->redirectPartialMatch($this->event);

        $this->assertNull($this->event->getResponse());
    }

    public function testRedirectPartialMatchForRedirect(): void
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_REDIRECT);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu-redirect.lo');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo');

        $this->defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de', 'at'));

        $this->urlReplacer->replaceCountry('sulu.lo', 'at')->shouldBeCalled()->willReturn('sulu.lo');
        $this->urlReplacer->replaceLanguage('sulu.lo', 'de')->shouldBeCalled()->willReturn('sulu.lo');
        $this->urlReplacer->replaceLocalization('sulu.lo', 'de-at')->shouldBeCalled()->willReturn('sulu.lo');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectPartialMatch($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('http://sulu.lo', $redirectResponse->getTargetUrl());
    }

    public function testRedirectPartialMatchWithDoubleSlashOnly(): void
    {
        $localization = new Localization('de', 'at');
        $this->attributes->getAttribute('localization', null)->willReturn($localization);
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('');
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo/de-at');

        $this->urlReplacer->replaceCountry(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de-at');
        $this->urlReplacer->replaceLanguage(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de-at');
        $this->urlReplacer->replaceLocalization(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de-at');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectPartialMatch($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame('http://sulu.lo/de-at', $redirectResponse->getTargetUrl());
    }

    /**
     * @return mixed[]
     */
    public static function provideResolveData(): array
    {
        return [
            ['http://sulu.lo/articles?foo=bar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar'],
            [
                'http://sulu.lo/articles?foo=bar&bar=boo',
                'sulu.lo',
                'sulu.lo/en',
                'http://sulu.lo/en/articles?foo=bar&bar=boo',
            ],
            ['http://sulu.lo/articles/?foo=bar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar'],
            [
                'http://sulu.lo/articles/?foo=bar&bar=boo',
                'sulu.lo',
                'sulu.lo/en',
                'http://sulu.lo/en/articles?foo=bar&bar=boo',
            ],
            ['http://sulu.lo/en/articles/?foo=bar', 'sulu.lo', null, 'http://sulu.lo/en/articles?foo=bar'],
            [
                'http://sulu.lo/en/articles/?foo=bar&bar=boo',
                'sulu.lo',
                null,
                'http://sulu.lo/en/articles?foo=bar&bar=boo',
            ],
            ['sulu.lo:8001/', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en'],
            ['sulu.lo:8001/#foobar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en#foobar'],
            ['sulu.lo:8001/articles#foobar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en/articles#foobar'],
            ['sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo', 'http://sulu.lo'],
            ['sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo', 'http://sulu.lo'],
            ['http://sulu.lo:8002/', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8002/en'],
            ['http://sulu.lo/articles', 'sulu.lo/en', 'sulu.lo/de', 'http://sulu.lo/de/articles'],
            ['http://sulu.lo/events', 'sulu.lo/events', 'sulu.lo/events/de', 'http://sulu.lo/events/de', '/events'],
            [
                'http://sulu.lo/events/articles',
                'sulu.lo/events',
                'sulu.lo/events/de',
                'http://sulu.lo/events/de/articles',
                '/events',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideResolveData')]
    public function testRedirectPartialMatchResolve(
        $requestUri,
        $portalUrl,
        $redirectUrl,
        $expectedTargetUrl,
        $prefix = ''
    ): void {
        $this->request->getUri()->willReturn($requestUri);

        $localization = new Localization('de');
        $this->attributes->getAttribute('localization', null)->willReturn($localization);
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn($prefix);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->attributes->getAttribute('portalUrl', null)->willReturn($portalUrl);
        $this->attributes->getAttribute('redirect', null)->willReturn($redirectUrl);

        $this->urlReplacer->replaceCountry(Argument::cetera())->shouldBeCalled()->willReturn($redirectUrl);
        $this->urlReplacer->replaceLanguage(Argument::cetera())->shouldBeCalled()->willReturn($redirectUrl);
        $this->urlReplacer->replaceLocalization(Argument::cetera())->shouldBeCalled()->willReturn($redirectUrl);

        $this->router->matchRequest(Argument::type(Request::class))->willReturn([
            $this->prophesize(Route::class)->reveal(),
        ]);

        $this->exceptionListener->redirectPartialMatch($this->event);

        $redirectResponse = $this->event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $redirectResponse);
        $this->assertSame($expectedTargetUrl, $redirectResponse->getTargetUrl());
    }
}
