<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Resolves the request_analyzer to an array.
 */
class RequestAnalyzerResolver implements RequestAnalyzerResolverInterface
{
    private \Sulu\Component\Webspace\Manager\WebspaceManagerInterface $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    public function __construct(WebspaceManagerInterface $webspaceManager, $environment)
    {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    public function resolve(RequestAnalyzerInterface $requestAnalyzer)
    {
        // determine default locale (if one exists)
        $defaultLocalization = $requestAnalyzer->getPortal()->getDefaultLocalization();
        $defaultLocale = $defaultLocalization ? $defaultLocalization->getLocale() : null;

        $segment = $requestAnalyzer->getSegment();
        $segmentKey = $segment ? $segment->getKey() : null;

        return [
            'request' => [
                'webspaceKey' => $requestAnalyzer->getWebspace()->getKey(),
                'webspaceName' => $requestAnalyzer->getWebspace()->getName(),
                'segmentKey' => $segmentKey,
                'portalKey' => $requestAnalyzer->getPortal()->getKey(),
                'portalName' => $requestAnalyzer->getPortal()->getName(),
                'defaultLocale' => $defaultLocale,
                'portalUrl' => $requestAnalyzer->getPortalUrl(),
                'resourceLocatorPrefix' => $requestAnalyzer->getResourceLocatorPrefix(),
                'resourceLocator' => $requestAnalyzer->getResourceLocator(),
            ],
        ];
    }
}
