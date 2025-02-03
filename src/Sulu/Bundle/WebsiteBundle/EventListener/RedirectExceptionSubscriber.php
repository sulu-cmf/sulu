<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Bundle\WebsiteBundle\Locale\DefaultLocaleProviderInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * This event-listener redirect trailing slashes and ".html" and redirects to default locale for partial-matches.
 */
class RedirectExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestMatcherInterface
     */
    private $router;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var DefaultLocaleProviderInterface
     */
    private $defaultLocaleProvider;

    /**
     * @var ReplacerInterface
     */
    private $urlReplacer;

    public function __construct(
        RequestMatcherInterface $router,
        RequestAnalyzerInterface $requestAnalyzer,
        DefaultLocaleProviderInterface $defaultLocaleProvider,
        ReplacerInterface $urlReplacer
    ) {
        $this->router = $router;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->defaultLocaleProvider = $defaultLocaleProvider;
        $this->urlReplacer = $urlReplacer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['redirectPartialMatch', 0],
                ['redirectTrailingSlashOrHtml', 0],
            ],
        ];
    }

    /**
     * Redirect trailing slashes or ".html".
     */
    public function redirectTrailingSlashOrHtml(ExceptionEvent $event)
    {
        if (!$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        $request = $event->getRequest();

        /** @var RequestAttributes $attributes */
        $attributes = $request->attributes->get('_sulu');
        if (!$attributes) {
            return;
        }

        $prefix = $attributes->getAttribute('resourceLocatorPrefix');
        $resourceLocator = $attributes->getAttribute('resourceLocator');

        $route = '/' . \trim($prefix . $resourceLocator, '/');
        if (!\in_array($request->getRequestFormat(), ['htm', 'html'])
            || $route === $request->getPathInfo()
            || !$this->matchRoute($request->getSchemeAndHttpHost() . $route)
        ) {
            return;
        }

        $event->setResponse(new RedirectResponse($route, 301));
    }

    /**
     * Redirect partial and redirect matches.
     */
    public function redirectPartialMatch(ExceptionEvent $event)
    {
        if (!$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        $request = $event->getRequest();

        /** @var RequestAttributes $attributes */
        $attributes = $event->getRequest()->attributes->get('_sulu');
        if (!$attributes) {
            return;
        }

        $types = [RequestAnalyzerInterface::MATCH_TYPE_REDIRECT, RequestAnalyzerInterface::MATCH_TYPE_PARTIAL];
        $matchType = $attributes->getAttribute('matchType');
        if (!\in_array($matchType, $types)) {
            return;
        }

        $localization = $this->defaultLocaleProvider->getDefaultLocale();

        $redirect = $attributes->getAttribute('redirect');
        $redirect = $this->urlReplacer->replaceCountry($redirect, $localization->getCountry());
        $redirect = $this->urlReplacer->replaceLanguage($redirect, $localization->getLanguage());
        $redirect = $this->urlReplacer->replaceLocalization($redirect, $localization->getLocale(Localization::DASH));

        $route = $this->resolveRedirectUrl(
            $redirect,
            $request->getUri(),
            $attributes->getAttribute('resourceLocatorPrefix')
        );

        if (!$this->matchRoute($route)) {
            return;
        }

        $event->setResponse(new RedirectResponse($route, 301));
    }

    /**
     * Returns true if given route exists.
     *
     * @param string $route
     *
     * @return bool
     */
    private function matchRoute($route)
    {
        return $this->matchUrl($route);
    }

    /**
     * Returns true if given url exists.
     *
     * @param string $url
     *
     * @return bool
     */
    private function matchUrl($url)
    {
        $request = Request::create($url);
        $this->requestAnalyzer->analyze($request);

        try {
            return null !== $this->router->matchRequest($request);
        } catch (ResourceNotFoundException $exception) {
            return false;
        }
    }

    /**
     * Resolve the redirect URL, appending any additional path data.
     *
     * @param string $redirectUrl Redirect webspace URI
     * @param string $requestUri The actual incoming request URI
     * @param string $resourceLocatorPrefix The prefix of the actual portal
     *
     * @return string URL to redirect to
     */
    private function resolveRedirectUrl($redirectUrl, $requestUri, $resourceLocatorPrefix)
    {
        $redirectInfo = $this->parseUrl($redirectUrl);
        $requestInfo = $this->parseUrl($requestUri);

        $url = \sprintf('%s://%s', $requestInfo['scheme'], $requestInfo['host']);

        if (isset($redirectInfo['host'])) {
            $url = \sprintf('%s://%s', $requestInfo['scheme'], $redirectInfo['host']);
        }

        if (isset($requestInfo['port'])) {
            $url .= ':' . $requestInfo['port'];
        }

        if (isset($redirectInfo['path'])
            && (// if requested url not starting with redirectUrl it need to be added
                !isset($requestInfo['path'])
                || 0 !== \strpos($requestInfo['path'], $redirectInfo['path'] . '/'))
        ) {
            $url .= $redirectInfo['path'];
        }

        if (isset($requestInfo['path']) && $resourceLocatorPrefix !== $requestInfo['path']) {
            $path = $requestInfo['path'];
            if ($resourceLocatorPrefix && 0 === \strpos($path, $resourceLocatorPrefix)) {
                $path = \substr($path, \strlen($resourceLocatorPrefix));
            }

            [$resourceLocator, $formatResult] = $this->getResourceLocatorFromRequest($path);

            $url .= $resourceLocator;
            $url = \rtrim($url, '/') . (null !== $formatResult ? ('.' . $formatResult) : '');
        }

        if (isset($requestInfo['query'])) {
            $url .= '?' . $requestInfo['query'];
        }

        if (isset($requestInfo['fragment'])) {
            $url .= '#' . $requestInfo['fragment'];
        }

        return $url;
    }

    /**
     * Prefix http to the URL if it is missing and
     * then parse the string using parse_url.
     *
     * @param string $url
     *
     * @return array
     */
    private function parseUrl($url)
    {
        if (!\preg_match('{^https?://}', $url)) {
            $url = 'http://' . $url;
        }

        return \parse_url($url);
    }

    /**
     * @return array<int, string|null>
     */
    private function getResourceLocatorFromRequest(string $path): array
    {
        // extract file and extension info
        $pathParts = \explode('/', $path);
        $fileInfo = \explode('.', \array_pop($pathParts));

        $resourceLocator = \rtrim(\implode('/', $pathParts), '/') . '/' . $fileInfo[0];
        $formatResult = null;
        if (\count($fileInfo) > 1) {
            $formatResult = \end($fileInfo);
        }

        return [$resourceLocator, $formatResult];
    }
}
