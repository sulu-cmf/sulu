<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Container for a webspace definition.
 */
class Webspace implements ArrayableInterface
{
    /**
     * The name of the webspace.
     *
     * @var string
     */
    private $name;

    /**
     * The key of the webspace.
     *
     * @var string
     */
    private $key;

    /**
     * The localizations defined for this webspace.
     *
     * @var Localization[]
     */
    private $localizations = [];

    /**
     * The default localization defined for this webspace.
     *
     * @var Localization|null
     */
    private $defaultLocalization;

    /**
     * The segments defined for this webspace.
     *
     * @var Segment[]
     */
    private $segments = [];

    /**
     * The default segment defined for this webspace.
     *
     * @var Segment
     */
    private $defaultSegment;

    /**
     * The theme of the webspace.
     *
     * @var string
     */
    private $theme;

    /**
     * The portals defined for this webspace.
     *
     * @var Portal[]
     */
    private $portals = [];

    /**
     * The security system for this webspace.
     *
     * @var Security|null
     */
    private $security;

    /**
     * Navigation for this webspace.
     *
     * @var Navigation
     */
    private $navigation;

    /**
     * A list of twig templates.
     *
     * @var array
     */
    private $templates = [];

    /**
     * Template which is selected by default if no other template is chosen.
     *
     * @var string[]
     */
    private $defaultTemplates = [];

    /**
     * @var string[]
     */
    private $excludedTemplates = [];

    /**
     * The url generation strategy for this portal.
     *
     * @var string
     */
    private $resourceLocatorStrategy;

    /**
     * Sets the key of the webspace.
     *
     * @param string $key
     *
     * @return void
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Returns the key of the webspace.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Adds a localization to the webspace.
     *
     * @return void
     */
    public function addLocalization(Localization $localization)
    {
        $this->localizations[] = $localization;

        if ($localization->isDefault()) {
            $this->setDefaultLocalization($localization);
        }
    }

    /**
     * Returns the localizations of this webspace.
     *
     * @param Localization[] $localizations
     *
     * @return void
     */
    public function setLocalizations($localizations)
    {
        $this->localizations = $localizations;
    }

    /**
     * Returns the localizations of this webspace.
     *
     * @return Localization[]
     */
    public function getLocalizations()
    {
        return $this->localizations;
    }

    /**
     * Returns a list of all localizations and sublocalizations.
     *
     * @return Localization[]
     */
    public function getAllLocalizations()
    {
        $localizations = [];
        foreach ($this->getLocalizations() as $child) {
            $localizations[] = $child;
            $localizations = \array_merge($localizations, $child->getAllLocalizations());
        }

        return $localizations;
    }

    /**
     * Returns the localization object for a given localization string.
     *
     * @param string $localization
     *
     * @return Localization|null
     */
    public function getLocalization($localization)
    {
        $localizations = $this->getLocalizations();
        if (!empty($localizations)) {
            foreach ($localizations as $webspaceLocalization) {
                $result = $webspaceLocalization->findLocalization($localization);

                if ($result) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Sets the default localization for this webspace.
     *
     * @param Localization $defaultLocalization
     *
     * @return void
     */
    public function setDefaultLocalization($defaultLocalization)
    {
        $this->defaultLocalization = $defaultLocalization;
    }

    /**
     * Returns the default localization for this webspace.
     *
     * @return Localization
     */
    public function getDefaultLocalization()
    {
        if (!$this->defaultLocalization) {
            return $this->localizations[0];
        }

        return $this->defaultLocalization;
    }

    /**
     * Sets the name of the webspace.
     *
     * @param string $name
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the webspace.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a portal to the webspace.
     *
     * @return void
     */
    public function addPortal(Portal $portal)
    {
        $this->portals[] = $portal;
    }

    /**
     * Sets the portals of this webspace.
     *
     * @param Portal[] $portals
     *
     * @return void
     */
    public function setPortals($portals)
    {
        $this->portals = $portals;
    }

    /**
     * Returns the portals of this webspace.
     *
     * @return Portal[]
     */
    public function getPortals()
    {
        return $this->portals;
    }

    /**
     * @return array<CustomUrl>
     */
    public function getCustomUrls(string $environment): array
    {
        return $this->getPortals()[0]->getEnvironment($environment)->getCustomUrls();
    }

    /**
     * Adds a segment to the webspace.
     *
     * @return void
     */
    public function addSegment(Segment $segment)
    {
        $this->segments[] = $segment;

        if ($segment->isDefault()) {
            $this->setDefaultSegment($segment);
        }
    }

    /**
     * Sets the segments of this webspace.
     *
     * @param Segment[] $segments
     *
     * @return void
     */
    public function setSegments($segments)
    {
        $this->segments = $segments;
    }

    /**
     * Returns the segments of this webspace.
     *
     * @return Segment[]
     */
    public function getSegments()
    {
        return $this->segments;
    }

    public function getSegment(string $segmentKey): ?Segment
    {
        foreach ($this->segments as $segment) {
            if ($segment->getKey() === $segmentKey) {
                return $segment;
            }
        }

        return null;
    }

    /**
     * Sets the default segment of this webspace.
     *
     * @param Segment $defaultSegment
     *
     * @return void
     */
    public function setDefaultSegment($defaultSegment)
    {
        $this->defaultSegment = $defaultSegment;
    }

    /**
     * Returns the default segment for this webspace.
     *
     * @return Segment
     */
    public function getDefaultSegment()
    {
        return $this->defaultSegment;
    }

    /**
     * Sets the theme for this portal.
     *
     * @param string|null $theme this parameter is options
     *
     * @return void
     */
    public function setTheme($theme = null)
    {
        $this->theme = $theme;
    }

    /**
     * Returns the theme for this portal.
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Sets the security system.
     *
     * @param Security|null $security
     *
     * @return void
     */
    public function setSecurity($security)
    {
        $this->security = $security;
    }

    /**
     * Returns the security system.
     *
     * @return Security|null
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * @return bool
     */
    public function hasWebsiteSecurity()
    {
        $security = $this->getSecurity();

        if (!$security) {
            return false;
        }

        return null !== $security->getSystem() && $security->getPermissionCheck();
    }

    /**
     * @return Navigation
     */
    public function getNavigation()
    {
        return $this->navigation;
    }

    /**
     * @param Navigation $navigation
     *
     * @return void
     */
    public function setNavigation($navigation)
    {
        $this->navigation = $navigation;
    }

    /**
     * Returns false if domain not exists in webspace.
     *
     * @param string $domain
     * @param string $environment
     * @param string $locale
     *
     * @return bool
     *
     * @throws Exception\EnvironmentNotFoundException
     */
    public function hasDomain($domain, $environment, $locale = null)
    {
        $localizationParts = \explode('_', $locale);
        $language = $localizationParts[0];
        $country = isset($localizationParts[1]) ? $localizationParts[1] : '';

        foreach ($this->getPortals() as $portal) {
            foreach ($portal->getEnvironment($environment)->getUrls() as $url) {
                $host = \parse_url('//' . $url->getUrl())['host'];
                if ((null === $locale || $url->isValidLocale($language, $country))
                    && ($host === $domain || '{host}' === $host)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add a new template for given type.
     *
     * @param string $type
     * @param string $template
     *
     * @return void
     */
    public function addTemplate($type, $template)
    {
        $this->templates[$type] = $template;
    }

    /**
     * Returns a template for the given type.
     *
     * @param string $type
     * @param string $format
     *
     * @return string|null
     */
    public function getTemplate($type, $format = 'html')
    {
        if (\array_key_exists($type, $this->templates)) {
            return $this->templates[$type] . '.' . $format . '.twig';
        }

        return null;
    }

    /**
     * Returns an array of templates.
     *
     * @return string[]
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Add a new default template for given type.
     *
     * @param string $type
     * @param string $template
     *
     * @return void
     */
    public function addDefaultTemplate($type, $template)
    {
        $this->defaultTemplates[$type] = $template;
    }

    /**
     * Returns a error template for given code.
     *
     * @param string $type
     *
     * @return string|null
     */
    public function getDefaultTemplate($type)
    {
        if (\array_key_exists($type, $this->defaultTemplates)) {
            return $this->defaultTemplates[$type];
        }

        return null;
    }

    /**
     * Returns a array of default template.
     *
     * @return string[]
     */
    public function getDefaultTemplates()
    {
        return $this->defaultTemplates;
    }

    /**
     * Add a new template for given type.
     *
     * @param string $excludedTemplate
     *
     * @return void
     */
    public function addExcludedTemplate($excludedTemplate)
    {
        $this->excludedTemplates[] = $excludedTemplate;
    }

    /**
     * @return string[]
     */
    public function getExcludedTemplates()
    {
        return $this->excludedTemplates;
    }

    /**
     * Set resource-locator strategy.
     *
     * @param string $resourceLocatorStrategy
     *
     * @return void
     */
    public function setResourceLocatorStrategy($resourceLocatorStrategy)
    {
        $this->resourceLocatorStrategy = $resourceLocatorStrategy;
    }

    /**
     * Returns resource-locator strategy.
     *
     * @return string
     */
    public function getResourceLocatorStrategy()
    {
        return $this->resourceLocatorStrategy;
    }

    /**
     * @return array
     */
    public function toArray($depth = null)
    {
        $res = [];
        $res['key'] = $this->getKey();
        $res['name'] = $this->getName();
        $res['localizations'] = [];
        $res['templates'] = $this->getTemplates();
        $res['defaultTemplates'] = $this->getDefaultTemplates();
        $res['excludedTemplates'] = $this->getExcludedTemplates();
        $res['resourceLocator']['strategy'] = $this->getResourceLocatorStrategy();

        foreach ($this->getLocalizations() as $localization) {
            $res['localizations'][] = $localization->toArray();
        }

        $thisSecurity = $this->getSecurity();
        if (null != $thisSecurity) {
            $res['security']['system'] = $thisSecurity->getSystem();
            $res['security']['permissionCheck'] = $thisSecurity->getPermissionCheck();
        }

        $res['segments'] = [];
        $segments = $this->getSegments();

        if (!empty($segments)) {
            foreach ($segments as $segment) {
                $res['segments'][] = $segment->toArray();
            }
        }

        $res['theme'] = !$this->theme ? null : $this->theme;

        $res['portals'] = [];
        foreach ($this->getPortals() as $portal) {
            $res['portals'][] = $portal->toArray();
        }

        $res['navigation'] = [];
        $res['navigation']['contexts'] = [];
        if ($navigation = $this->getNavigation()) {
            foreach ($navigation->getContexts() as $context) {
                $res['navigation']['contexts'][] = [
                    'key' => $context->getKey(),
                    'metadata' => $context->getMetadata(),
                ];
            }
        }

        return $res;
    }
}
