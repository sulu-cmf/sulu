<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager;

use Psr\Log\LoggerInterface;
use Sulu\Component\Webspace\Manager\Dumper\PhpWebspaceCollectionDumper;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;

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
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoaderInterface $loader, LoggerInterface $logger, $options = array())
    {
        $this->loader = $loader;
        $this->logger = $logger;
        $this->setOptions($options);
    }

    /**
     * Returns the webspace with the given key.
     *
     * @param $key string The key to search for
     *
     * @return Webspace
     */
    public function findWebspaceByKey($key)
    {
        return $this->getWebspaceCollection()->getWebspace($key);
    }

    /**
     * Return true if the webspace with the given key exists.
     *
     * @return boolean
     */
    public function hasWebspace($key)
    {
        return $this->getWebspaceCollection()->hasWebspace($key);
    }

    /**
     * Returns the portal with the given key.
     *
     * @param string $key The key to search for
     *
     * @return Portal
     */
    public function findPortalByKey($key)
    {
        return $this->getWebspaceCollection()->getPortal($key);
    }

    /**
     * Return true if the portal with the given key exists.
     *
     * @return boolean
     */
    public function hasPortal($key)
    {
        return $this->getWebspaceCollection()->hasPortal($key);
    }

    /**
     * Returns the portal with the given url (which has not necessarily to be the main url).
     *
     * @param string $url The url to search for
     * @param string $environment The environment in which the url should be searched
     *
     * @return array|null
     */
    public function findPortalInformationByUrl($url, $environment)
    {
        foreach (
            $this->getWebspaceCollection()->getPortalInformations($environment) as $portalUrl => $portalInformation
        ) {
            $nextChar = substr($url, strlen($portalUrl), 1);
            if (strpos($url, $portalUrl) === 0 && ($nextChar === '/' || $nextChar === '.' || $nextChar === false)) {
                return $portalInformation;
            }
        }

        return;
    }

    /**
     * Returns all possible urls for resourcelocator.
     *
     * @param string $resourceLocator
     * @param string $environment
     * @param string $languageCode
     * @param null|string $webspaceKey
     * @param null|string $domain
     *
     * @return array
     */
    public function findUrlsByResourceLocator($resourceLocator, $environment, $languageCode, $webspaceKey = null, $domain = null)
    {
        $urls = array();
        $portals = $this->getWebspaceCollection()->getPortalInformations($environment);
        foreach ($portals as $url => $portalInformation) {
            $sameLocalization = $portalInformation->getLocalization()->getLocalization() === $languageCode;
            $sameWebspace = $webspaceKey === null || $portalInformation->getWebspace()->getKey() === $webspaceKey;
            // TODO protocol
            $url = rtrim('http://' . $url . $resourceLocator, '/');
            if ($sameLocalization && $sameWebspace && $this->isFromDomain($url, $domain)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * {@inheritDoc}
     */
    public function getPortals()
    {
        return $this->getWebspaceCollection()->getPortals();
    }

    /**
     * {@inheritDoc}
     */
    public function getUrls($environment)
    {
        $urls = array();

        foreach ($this->getWebspaceCollection()->getPortalInformations($environment) as $portalInformation) {
            $urls[] = $portalInformation->getUrl();
        }

        return $urls;
    }

    /**
     * {@inheritDoc}
     */
    public function getPortalInformations($environment)
    {
        return $this->getWebspaceCollection()->getPortalInformations($environment);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllLocalizations()
    {
        $localizations = array();

        foreach ($this->getWebspaceCollection() as $webspace) {
            /** @var Webspace $webspace */
            foreach ($webspace->getAllLocalizations() as $localization) {
                $localizations[$localization->getLocalization()] = $localization;
            }
        }

        return $localizations;
    }

    /**
     * Returns all the webspaces managed by this specific instance.
     *
     * @return WebspaceCollection
     */
    public function getWebspaceCollection()
    {
        if ($this->webspaceCollection === null) {
            $class = $this->options['cache_class'];
            $cache = new ConfigCache(
                $this->options['cache_dir'] . '/' . $class . '.php',
                $this->options['debug']
            );

            if (!$cache->isFresh()) {
                $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
                    $this->loader,
                    $this->logger,
                    $this->options['config_dir']
                );
                $webspaceCollection = $webspaceCollectionBuilder->build();
                $dumper = new PhpWebspaceCollectionDumper($webspaceCollection);
                $cache->write(
                    $dumper->dump(
                        array(
                            'cache_class' => $class,
                            'base_class'  => $this->options['base_class'],
                        )
                    ),
                    $webspaceCollection->getResources()
                );
            }

            require_once $cache;

            $this->webspaceCollection = new $class();
        }

        return $this->webspaceCollection;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Sets the options for the manager.
     *
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = array(
            'config_dir'  => null,
            'cache_dir'   => null,
            'debug'       => false,
            'cache_class' => 'WebspaceCollectionCache',
            'base_class'  => 'WebspaceCollection',
        );

        // overwrite the default values with the given options
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Url is from domain
     * @param $url
     * @param $domain
     * @return array
     */
    protected function isFromDomain($url, $domain)
    {
        if (!$domain) {
            return true;
        }

        $parsedUrl = parse_url($url);
        // if domain or subdomain
        if (
            isset($parsedUrl['host'])
            && (
                $parsedUrl['host'] == $domain
                || fnmatch('*.' . $domain, $parsedUrl['host'])
            )
        ) {
            return true;
        }

        return false;
    }
}
