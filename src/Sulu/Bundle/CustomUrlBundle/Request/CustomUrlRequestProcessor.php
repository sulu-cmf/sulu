<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Request;

use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestProcessorInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set localization in case of custom-url route.
 */
class CustomUrlRequestProcessor implements RequestProcessorInterface
{
    public function __construct(
        private CustomUrlManagerInterface $customUrlManager,
        private GeneratorInterface $generator,
        private WebspaceManagerInterface $webspaceManager,
        private ?string $environment
    ) {
    }

    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $pathInfo = $request->getPathInfo();
        $position = \strrpos($pathInfo, '.');
        if ($position) {
            $pathInfo = \substr($pathInfo, 0, $position);
        }

        $queryString = $request->getQueryString();
        if (!empty($queryString)) {
            $queryString = '?' . $queryString;
        }

        $url = $this->decodeUrl(\rtrim(\sprintf('%s%s%s', $request->getHost(), $pathInfo, $queryString), '/'));
        $portalInformations = $this->webspaceManager->findPortalInformationsByUrl($url, $this->environment);

        if (0 === \count($portalInformations)) {
            return new RequestAttributes();
        }

        /** @var PortalInformation[] $portalInformations */
        $portalInformations = \array_filter(
            $portalInformations,
            function(PortalInformation $portalInformation) {
                return RequestAnalyzer::MATCH_TYPE_WILDCARD === $portalInformation->getType();
            }
        );

        foreach ($portalInformations as $portalInformation) {
            if (!$portalInformation->getWebspace()) {
                continue;
            }

            if (null !== $attributes = $this->matchCustomUrl($url, $portalInformation, $request)) {
                return new RequestAttributes($attributes);
            }
        }

        return new RequestAttributes();
    }

    public function validate(RequestAttributes $attributes)
    {
        return true;
    }

    /**
     * Matches given url to portal-information.
     */
    private function matchCustomUrl(string $url, PortalInformation $portalInformation, Request $request): array
    {
        $webspace = $portalInformation->getWebspace();
        $routeDocument = $this->customUrlManager->findRouteByUrl(
            \rawurldecode($url),
            $webspace->getKey()
        );

        if (!$routeDocument) {
            return [];
        } elseif ($routeDocument->isHistory()) {
            // redirect happen => no portal is needed
            return ['customUrlRoute' => $routeDocument];
        }

        $customUrlDocument = $this->customUrlManager->findByUrl(
            \rawurldecode($url),
            $webspace->getKey(),
            $routeDocument->getTargetDocument()->getTargetLocale()
        );

        if (null === $customUrlDocument
            || false === $customUrlDocument->isPublished()
            || null === $customUrlDocument->getTargetDocument()
            || WorkflowStage::PUBLISHED !== $customUrlDocument->getTargetDocument()->getWorkflowStage()
        ) {
            // error happen because this custom-url is not published => no portal is needed
            return ['customUrlRoute' => $routeDocument, 'customUrl' => $customUrlDocument];
        }

        $localization = Localization::createFromString($customUrlDocument->getTargetLocale());

        $portalInformations = $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale(
            $portalInformation->getWebspace()->getKey(),
            $localization->getLocale(),
            $this->environment
        );

        if (0 === \count($portalInformations)) {
            return ['customUrlRoute' => $routeDocument, 'customUrl' => $customUrlDocument];
        }

        return [
            'portalInformation' => $portalInformation,
            'localization' => $localization,
            'locale' => $localization->getLocale(),
            'customUrlRoute' => $routeDocument,
            'customUrl' => $customUrlDocument,
            'urlExpression' => $this->generator->generate(
                $customUrlDocument->getBaseDomain(),
                $customUrlDocument->getDomainParts()
            ),
        ];
    }

    /**
     * Server encodes the url and symfony does not encode it
     * Symfony decodes this data here https://github.com/symfony/symfony/blob/3.3/src/Symfony/Component/Routing/Matcher/UrlMatcher.php#L91.
     */
    private function decodeUrl(string $pathInfo): string
    {
        return \rawurldecode($pathInfo);
    }
}
