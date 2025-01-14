<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager;

use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Util\WildcardUrlUtil;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\Dumper\PhpWebspaceCollectionDumper;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This class is responsible for loading, reading and caching the portal configuration files.
 */
class WebspaceManager implements WebspaceManagerInterface
{
    /**
     * @var WebspaceCollection
     */
    private $webspaceCollection;

    /**
     * @var array
     */
    private $options;

    /**
     * @var mixed[]
     */
    private $portalUrlCache = [];

    public function __construct(
        private LoaderInterface $loader,
        private ReplacerInterface $urlReplacer,
        private RequestStack $requestStack,
        array $options,
        private string $environment,
        private string $defaultHost,
        private string $defaultScheme,
        private StructureMetadataFactoryInterface $structureMetadataFactory
    ) {
        $this->setOptions($options);
    }

    public function findWebspaceByKey(?string $key): ?Webspace
    {
        if (!$key) {
            return null;
        }

        return $this->getWebspaceCollection()->getWebspace($key);
    }

    public function findPortalByKey(?string $key): ?Portal
    {
        if (!$key) {
            return null;
        }

        return $this->getWebspaceCollection()->getPortal($key);
    }

    public function findPortalInformationByUrl(string $url, ?string $environment = null): ?PortalInformation
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        $portalInformations = $this->getWebspaceCollection()->getPortalInformations($environment);
        foreach ($portalInformations as $portalInformation) {
            if ($this->matchUrl($url, $portalInformation->getUrl())) {
                return $portalInformation;
            }
        }

        return null;
    }

    public function findPortalInformationsByHostIncludingSubdomains(string $host, ?string $environment = null): array
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return \array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function(PortalInformation $portalInformation) use ($host) {
                $portalHost = $portalInformation->getHost();

                // add a slash to avoid problems with "example.co" and "example.com"
                return false !== \strpos($portalHost . '/', $host . '/');
            }
        );
    }

    public function findPortalInformationsByUrl(string $url, ?string $environment = null): array
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return \array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function(PortalInformation $portalInformation) use ($url) {
                return $this->matchUrl($url, $portalInformation->getUrl());
            }
        );
    }

    public function findPortalInformationsByWebspaceKeyAndLocale(
        string $webspaceKey,
        string $locale,
        ?string $environment = null
    ): array {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return \array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function(PortalInformation $portalInformation) use ($webspaceKey, $locale) {
                return $portalInformation->getWebspace()->getKey() === $webspaceKey
                    && $portalInformation->getLocale() === $locale;
            }
        );
    }

    public function findPortalInformationsByPortalKeyAndLocale(
        string $portalKey,
        string $locale,
        ?string $environment = null
    ): array {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return \array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function(PortalInformation $portalInformation) use ($portalKey, $locale) {
                return $portalInformation->getPortal()
                    && $portalInformation->getPortal()->getKey() === $portalKey
                    && $portalInformation->getLocale() === $locale;
            }
        );
    }

    public function findUrlsByResourceLocator(
        string $resourceLocator,
        ?string $environment,
        string $languageCode,
        ?string $webspaceKey = null,
        ?string $domain = null,
        ?string $scheme = null
    ): array {
        if (null === $environment) {
            $environment = $this->environment;
        }
        if (null === $webspaceKey) {
            $currentWebspace = $this->getCurrentWebspace();
            $webspaceKey = $currentWebspace ? $currentWebspace->getKey() : $webspaceKey;
        }

        $urls = [];
        $portals = $this->getWebspaceCollection()->getPortalInformations(
            $environment,
            [RequestAnalyzerInterface::MATCH_TYPE_FULL]
        );
        foreach ($portals as $portalInformation) {
            $sameLocalization = $portalInformation->getLocalization()->getLocale() === $languageCode;
            $sameWebspace = null === $webspaceKey || $portalInformation->getWebspace()->getKey() === $webspaceKey;
            $url = $this->createResourceLocatorUrl($portalInformation->getUrl(), $resourceLocator, $scheme);
            if ($sameLocalization && $sameWebspace && $this->isFromDomain($url, $domain)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function findUrlByResourceLocator(
        ?string $resourceLocator,
        ?string $environment,
        string $languageCode,
        ?string $webspaceKey = null,
        ?string $domain = null,
        ?string $scheme = null
    ): ?string {
        if (null === $environment) {
            $environment = $this->environment;
        }
        if (null === $webspaceKey) {
            $currentWebspace = $this->getCurrentWebspace();
            $webspaceKey = $currentWebspace ? $currentWebspace->getKey() : $webspaceKey;
        }
        if (null === $resourceLocator) {
            $resourceLocator = '/';
        }

        if (isset($this->portalUrlCache[$webspaceKey][$domain][$environment][$languageCode])) {
            $portalUrl = $this->portalUrlCache[$webspaceKey][$domain][$environment][$languageCode];

            if (!$portalUrl) {
                return null;
            }

            return $this->createResourceLocatorUrl($portalUrl, $resourceLocator, $scheme);
        }

        $sameDomainUrl = null;
        $fullMatchedUrl = null;
        $partialMatchedUrl = null;

        $portals = $this->getWebspaceCollection()->getPortalInformations(
            $environment
        );

        foreach ($portals as $portalInformation) {
            if (!\in_array($portalInformation->getType(), [
                RequestAnalyzerInterface::MATCH_TYPE_FULL,
                RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                RequestAnalyzerInterface::MATCH_TYPE_REDIRECT,
            ])) {
                continue;
            }

            $sameWebspace = null === $webspaceKey || $portalInformation->getWebspace()->getKey() === $webspaceKey;

            if (!$sameWebspace) {
                continue;
            }

            $portalLocalization = $portalInformation->getLocalization();

            $sameLocalization = (
                null === $portalLocalization
                || $portalLocalization->getLocale() === $languageCode
            );

            if (!$sameLocalization) {
                continue;
            }

            $portalUrl = $portalInformation->getUrl();
            if (RequestAnalyzerInterface::MATCH_TYPE_FULL === $portalInformation->getType()) {
                if ($this->isFromDomain('http://' . $portalUrl, $domain)) {
                    if ($portalInformation->isMain()) {
                        $sameDomainUrl = $portalUrl;
                    } elseif (!$sameDomainUrl) {
                        $sameDomainUrl = $portalUrl;
                    }
                } elseif ($sameDomainUrl) {
                    continue;
                } elseif ($portalInformation->isMain()) {
                    $fullMatchedUrl = $portalUrl;
                } elseif (!$fullMatchedUrl) {
                    $fullMatchedUrl = $portalUrl;
                }
            } elseif ($fullMatchedUrl || $sameDomainUrl) {
                continue;
            } elseif (!$partialMatchedUrl) {
                $partialMatchedUrl = $portalUrl;
            }
        }

        if ($sameDomainUrl) {
            $portalUrl = $sameDomainUrl;
        } elseif ($fullMatchedUrl) {
            $portalUrl = $fullMatchedUrl;
        } elseif ($partialMatchedUrl) {
            $portalUrl = $partialMatchedUrl;
        } else {
            $portalUrl = null;
        }

        $this->portalUrlCache[$webspaceKey][$domain][$environment][$languageCode] = $portalUrl;

        if (!$portalUrl) {
            return null;
        }

        return $this->createResourceLocatorUrl($portalUrl, $resourceLocator, $scheme);
    }

    public function getPortals(): array
    {
        return $this->getWebspaceCollection()->getPortals();
    }

    public function getUrls(?string $environment = null): array
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        $urls = [];

        foreach ($this->getWebspaceCollection()->getPortalInformations($environment) as $portalInformation) {
            $urls[] = $portalInformation->getUrl();
        }

        return $urls;
    }

    public function getPortalInformations(?string $environment = null): array
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return $this->getWebspaceCollection()->getPortalInformations($environment);
    }

    public function getPortalInformationsByWebspaceKey(?string $environment, string $webspaceKey): array
    {
        if (null === $environment) {
            $environment = $this->environment;
        }

        return \array_filter(
            $this->getWebspaceCollection()->getPortalInformations($environment),
            function(PortalInformation $portal) use ($webspaceKey) {
                return $portal->getWebspaceKey() === $webspaceKey;
            }
        );
    }

    public function getAllLocalizations(): array
    {
        $localizations = [];

        /** @var Webspace $webspace */
        foreach ($this->getWebspaceCollection() as $webspace) {
            foreach ($webspace->getAllLocalizations() as $localization) {
                $localizations[$localization->getLocale()] = $localization;
            }
        }

        return $localizations;
    }

    public function getAllLocales(): array
    {
        return \array_values(
            \array_map(
                function(Localization $localization) {
                    return $localization->getLocale();
                },
                $this->getAllLocalizations()
            )
        );
    }

    /**
     * @return array<string, array<string, Localization>>
     */
    public function getAllLocalesByWebspaces(): array
    {
        $webspaces = [];
        foreach ($this->getWebspaceCollection() as $webspace) {
            /** @var Webspace $webspace */
            $locales = [];
            $defaultLocale = $webspace->getDefaultLocalization();
            $locales[$defaultLocale->getLocale()] = $defaultLocale;
            foreach ($webspace->getAllLocalizations() as $localization) {
                if (!\array_key_exists($localization->getLocale(), $locales)) {
                    $locales[$localization->getLocale()] = $localization;
                }
            }
            $webspaces[$webspace->getKey()] = $locales;
        }

        return $webspaces;
    }

    public function getWebspaceCollection(): WebspaceCollection
    {
        if (null === $this->webspaceCollection) {
            /** @var class-string<WebspaceCollection> $class */
            $class = $this->options['cache_class'];
            $cache = new ConfigCache(
                $this->options['cache_dir'] . '/' . $class . '.php',
                $this->options['debug']
            );

            if (!$cache->isFresh()) {
                $availableTemplates = \array_map(
                    function(StructureMetadata $structure) {
                        return $structure->getName();
                    },
                    $this->structureMetadataFactory->getStructures('page')
                );
                $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
                    $this->loader,
                    $this->urlReplacer,
                    $this->options['config_dir'],
                    $availableTemplates
                );
                $webspaceCollection = $webspaceCollectionBuilder->build();
                $dumper = new PhpWebspaceCollectionDumper($webspaceCollection);
                $cache->write(
                    $dumper->dump(
                        [
                            'cache_class' => $class,
                            'base_class' => $this->options['base_class'],
                        ]
                    ),
                    $webspaceCollection->getResources()
                );
            }

            require_once $cache->getPath();

            $this->webspaceCollection = new $class();

            $currentRequest = $this->requestStack->getCurrentRequest();

            $host = $currentRequest ? $currentRequest->getHost() : $this->defaultHost;
            foreach ($this->getPortalInformations() as $portalInformation) {
                $portalInformation->setUrl($this->urlReplacer->replaceHost($portalInformation->getUrl(), $host));
                $portalInformation->setUrlExpression(
                    $this->urlReplacer->replaceHost($portalInformation->getUrlExpression(), $host)
                );
                $portalInformation->setRedirect(
                    $this->urlReplacer->replaceHost($portalInformation->getRedirect(), $host)
                );
            }
        }

        return $this->webspaceCollection;
    }

    /**
     * Sets the options for the manager.
     *
     * @param mixed[] $options
     */
    public function setOptions($options)
    {
        $this->options = [
            'config_dir' => null,
            'cache_dir' => null,
            'debug' => false,
            'cache_class' => 'WebspaceCollectionCache',
            'base_class' => 'WebspaceCollection',
        ];

        // overwrite the default values with the given options
        $this->options = \array_merge($this->options, $options);
    }

    /**
     * Url is from domain.
     *
     * @param string $url
     * @param string $domain
     *
     * @return bool
     */
    protected function isFromDomain($url, $domain)
    {
        if (!$domain) {
            return true;
        }

        $parsedUrl = \parse_url($url);
        // if domain or subdomain
        if (
            isset($parsedUrl['host'])
            && (
                $parsedUrl['host'] == $domain
                || \fnmatch('*.' . $domain, $parsedUrl['host'])
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Matches given url with portal-url.
     *
     * @param string $url
     * @param string $portalUrl
     *
     * @return bool
     */
    protected function matchUrl($url, $portalUrl)
    {
        return WildcardUrlUtil::match($url, $portalUrl);
    }

    private function getCurrentWebspace(): ?Webspace
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest) {
            return null;
        }

        $suluAttributes = $currentRequest->attributes->get('_sulu');
        if (!$suluAttributes instanceof RequestAttributes) {
            return null;
        }

        return $suluAttributes->getAttribute('webspace');
    }

    /**
     * Return a valid resource locator url.
     *
     * @param string $portalUrl
     * @param string $resourceLocator
     * @param string|null $scheme
     *
     * @return string
     */
    private function createResourceLocatorUrl($portalUrl, $resourceLocator, $scheme = null)
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if (!$scheme) {
            $scheme = $currentRequest ? $currentRequest->getScheme() : $this->defaultScheme;
        }

        if (false !== \strpos($portalUrl, '/')) {
            // trim slash when resourceLocator is not domain root
            $resourceLocator = \rtrim($resourceLocator, '/');
        }

        $url = \rtrim(\sprintf('%s://%s', $scheme, $portalUrl), '/') . $resourceLocator;

        // add port if url points to host of current request and current request uses a custom port
        if ($currentRequest) {
            $host = $currentRequest->getHost();
            $port = $currentRequest->getPort();

            if ($url && $host && false !== \strpos($url, $host)) {
                if (!('http' == $scheme && 80 == $port) && !('https' == $scheme && 443 == $port)) {
                    $url = \str_replace($host, $host . ':' . $port, $url);
                }
            }
        }

        return $url;
    }
}
