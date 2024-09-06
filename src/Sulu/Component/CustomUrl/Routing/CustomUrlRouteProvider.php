<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Routing;

use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepositoryInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Generates a list of custom routes based on custom url redirects that could match the current request.
 */
class CustomUrlRouteProvider implements RouteProviderInterface
{
    /**
     * @param array<string, string> $defaultOptions
     */
    public function __construct(
        private CustomUrlRepositoryInterface $customUrlRepository,
        private GeneratorInterface $generator,
        private WebspaceManagerInterface $webspaceManager,
        private array $defaultOptions,
        private string $environment,
        private CustomUrlDefaultsProvider $customUrlDefaultsProvider
    ) {
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $url = $this->sanitizeUrl($request);
        $portalInformations = $this->webspaceManager->findPortalInformationsByUrl($url, $this->environment);

        /** @var CustomUrl $customUrl */
        $customUrl = null;
        foreach ($portalInformations as $portalInformation) {
            if (!$portalInformation->getWebspace() || RequestAnalyzerInterface::MATCH_TYPE_WILDCARD !== $portalInformation->getType()) {
                continue;
            }

            if (null !== $customUrl = $this->matchCustomUrl($url, $portalInformation, $request)) {
                break;
            }
        }

        if (null === $customUrl) {
            return new RouteCollection();
        }

        $defaults = $this->customUrlDefaultsProvider->provideDefault($request, $customUrl);

        if ($customUrl->isRedirect()) {
            $defaults = \array_merge(
                $defaults,
                $this->customUrlDefaultsProvider->provideForRedirect($request, $customUrl)
            );
        } else {
            $defaults = \array_merge(
                $defaults,
                $this->customUrlDefaultsProvider->provideForForward($request, $customUrl),
            );
        }

        // Forwarding the route to the normal request handling
        $collection = new RouteCollection();
        $collection->add(
            'Custom Url: ' . $customUrl->getTitle(),
            new Route(
                path: $this->decodePathInfo($request->getPathInfo()),
                defaults: $defaults,
                requirements: [],
                options: $this->defaultOptions
            )
        );

        return $collection;
    }

    /**
     * @param string $name
     */
    public function getRouteByName($name): Route
    {
        throw new RouteNotFoundException();
    }

    public function getRoutesByNames($names = null): iterable
    {
        return [];
    }

    private function matchCustomUrl(string $url, PortalInformation $portalInformation, Request $request): ?CustomUrl
    {
        $webspace = $portalInformation->getWebspace();
        $customUrl = $this->customUrlRepository->findNewestPublishedByUrl(\rawurldecode($url), $webspace->getKey());

        if (!$customUrl) {
            return null;
        }

        $localization = Localization::createFromString($customUrl->getTargetLocale());

        // TODO: Fix this
        $attributes = $request->attributes->get(RequestAnalyzer::SULU_ATTRIBUTE)->merge(new RequestAttributes([
            'portalInformation' => $portalInformation,
            'localization' => $localization,
            'locale' => $localization->getLocale(),
            'customUrl' => $customUrl,
            'urlExpression' => $this->generator->generate($customUrl->getBaseDomain(), $customUrl->getDomainParts()),
        ]));

        $request->attributes->set(RequestAnalyzer::SULU_ATTRIBUTE, $attributes);

        return $customUrl;
    }

    /**
     * Server encodes the url and symfony does not encode it
     * Symfony decodes this data here https://github.com/symfony/symfony/blob/3.3/src/Symfony/Component/Routing/Matcher/UrlMatcher.php#L91.
     */
    private function decodePathInfo(string $pathInfo): string
    {
        return \rawurldecode($pathInfo);
    }

    private function sanitizeUrl(Request $request): string
    {
        $pathInfo = $request->getPathInfo();

        // If the string contains a dot, strip everything after the dot. This should strip things like index.html to just index
        $position = \strrpos($pathInfo, '.');
        if ($position) {
            $pathInfo = \substr($pathInfo, 0, $position);
        }
        $pathInfo = \rtrim($pathInfo, '/');

        $queryString = $request->getQueryString() ?? '';
        if ('' !== $queryString) {
            $queryString = '?' . $queryString;
        }

        return $this->decodePathInfo(\sprintf('%s%s%s', $request->getHost(), $pathInfo, $queryString));
    }
}
