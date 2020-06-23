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

use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Implements logic to resolve parameters for website rendering.
 */
class ParameterResolver implements ParameterResolverInterface
{
    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var RequestAnalyzerResolverInterface
     */
    private $requestAnalyzerResolver;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * ParameterResolver constructor.
     */
    public function __construct(
        StructureResolverInterface $structureResolver,
        RequestAnalyzerResolverInterface $requestAnalyzerResolver,
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->structureResolver = $structureResolver;
        $this->requestAnalyzerResolver = $requestAnalyzerResolver;
        $this->webspaceManager = $webspaceManager;
    }

    public function resolve(
        array $parameter,
        RequestAnalyzerInterface $requestAnalyzer = null,
        StructureInterface $structure = null,
        $preview = false
    ) {
        if (null !== $structure) {
            $structureData = $this->structureResolver->resolve($structure, true);
        } else {
            $structureData = [];
        }

        $requestAnalyzerData = $this->requestAnalyzerResolver->resolve($requestAnalyzer);

        if (null !== ($portal = $requestAnalyzer->getPortal())) {
            $allLocalizations = $portal->getLocalizations();
        } else {
            $allLocalizations = $requestAnalyzer->getWebspace()->getLocalizations();
        }

        $pageUrls = \array_key_exists('urls', $structureData) ? $structureData['urls'] : [];
        $urls = [];
        $localizations = [];

        foreach ($allLocalizations as $localization) {
            /* @var Localization $localization */
            $locale = $localization->getLocale();

            if (\array_key_exists($locale, $pageUrls)) {
                $url = $this->webspaceManager->findUrlByResourceLocator($pageUrls[$locale], null, $locale);
            } else {
                $url = $this->webspaceManager->findUrlByResourceLocator('/', null, $locale);
            }

            $urls[$locale] = $url;
            $localizations[$locale] = [
                'locale' => $locale,
                'url' => $url,
            ];
        }

        $structureData['urls'] = $urls;
        $structureData['localizations'] = $localizations;

        return \array_merge(
            $parameter,
            $structureData,
            $requestAnalyzerData
        );
    }
}
