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

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Webspace;

class WebspaceCollectionBuilder
{
    /**
     * @param array<mixed> $configuration
     */
    public function __construct(private array $configuration = [])
    {
    }

    public function build(): WebspaceCollection
    {
        $webspaceRefs = [];
        $portalRefs = [];
        $localizationRefs = [];
        $segmentRefs = [];
        $portalInformationRefs = [];

        foreach ($this->configuration as $webspaceKey => $webspaceConfiguration) {
            $webspaceRefs[$webspaceKey] = $this->buildWebspace(
                $webspaceConfiguration,
                $portalRefs,
                $localizationRefs,
                $segmentRefs,
            );
        }

        /**

        {% for environmentKey, environment in collection.portalInformations %}
                {% for portalInformation in environment %}
                        $portalInformationRefs['{{ environmentKey }}']['{{ portalInformation.url }}'] = new PortalInformation(
                            {{ portalInformation.type }},
                            $webspaceRefs['{{ portalInformation.webspace }}'],
                            {% if portalInformation.portal %}
                                        $portalRefs['{{ portalInformation.portal }}'],
                            {% else %}
                                        null,
                            {% endif %}
                            {% if portalInformation.localization %}
                                        $localizationRefs['{{ portalInformation.webspace }}_{{ portalInformation.localization.localization }}'],
                            {% else %}
                                        null,
                            {% endif %}
                            '{{ portalInformation.url }}',
                            {% if portalInformation.segment %}
                                        $segmentRefs['{{ portalInformation.webspace }}_{{ portalInformation.segment }}'],
                            {% else %}
                                        null,
                            {% endif %}
                            portalInformation.redirect ?: null,
                            portalInformation.main,
                            portalInformation.urlExpression
                            portalInformation.priority
                        );

                {% endfor %}
        {% endfor %}
         */

        $webspaceCollection = new WebspaceCollection($webspaceRefs);
        $webspaceCollection->setPortals($portalRefs);
        $webspaceCollection->setPortalInformations($portalInformationRefs);

        return $webspaceCollection;
    }

    /**
     * @param array<string, PortalInformation> $portalRefs
     * @param array<string, Localization> $localizationRefs
     * @param array<string, Segment> $segmentRefs
     */
    protected function buildWebspace(
        array $webspaceConfiguration,
        array &$portalRefs,
        array &$localizationRefs,
        array &$segmentRefs,
    ): Webspace {
        $webspaceKey = $webspaceConfiguration['key'];

        $webspace = new Webspace();
        $webspace->setKey($webspaceKey);
        $webspace->setName($webspaceConfiguration['name']);

        $securityConfiguration = $webspaceConfiguration['security'];
        if ($securityConfiguration !== []) {
            $security = new Security();
            $security->setSystem($securityConfiguration['system']);
            $security->setPermissionCheck($securityConfiguration['permissionCheck']);

            $webspace->setSecurity($security);
        }

        foreach ($webspaceConfiguration['localizations'] as $localizationConfiguration) {
            $localization = [$this->buildLocalization($localizationConfiguration)];

            foreach ($localizationConfiguration['children'] ?? [] as $childLocalization) {
                $this->buildLocalizationTree($childLocalization, $localization, 1, 0, $webspaceKey);
            }

            $localizationRefs[$webspaceKey.'_'.$localization[0]] = $localization[0];

            $webspace->addLocalization($localization[0]);
        }

        foreach ($webspaceConfiguration['segments'] as $segmentConfiguration) {
            $segment = new Segment();
            $segment->setKey($segmentConfiguration['key']);
            $segment->setMetadata($segmentConfiguration['metadata']);
            $segment->setDefault($segmentConfiguration['default']);

            $webspace->addSegment($segment);

            $segmentRefs[$webspaceKey.'_'.$segmentConfiguration['key']] = $segment;
        }

        $this->buildTemplates($webspaceConfiguration, $webspace);

        $navigation = new Navigation();
        foreach ($webspaceConfiguration['navigation']['context'] ?? [] as $contextConfiguration) {
            $navigation->addContext(new NavigationContext($contextConfiguration['key'], $contextConfiguration['metadata']));
        }
        $webspace->setNavigation($navigation);

        $webspace->setResourceLocatorStrategy($webspaceConfiguration['resourceLocator']['strategy']);

        foreach ($webspaceConfiguration['portals'] as $portalConfiguration) {
            $portal = $this->buildPortal($portalConfiguration, $webspace);

            $portalRefs[$portalConfiguration['key']] = $portal;
            $webspace->addPortal($portal);
        }

        return $webspace;
    }

    protected function buildPortal(array $portalConfiguration, Webspace $webspace): Portal
    {
        $portal = new Portal();
        $portal->setName($portalConfiguration['name']);
        $portal->setKey($portalConfiguration['key']);
        $portal->setWebspace($webspace);

        foreach ($portalConfiguration['localizations'] ?? [] as $localizationConfiguration) {
            $localization = new Localization($localizationConfiguration['language']);
            $localization->setCountry($localizationConfiguration['country']);
            $localization->setDefault($localizationConfiguration['default']);

            $portal->addLocalization($localization);
        }

        foreach ($portalConfiguration['environments'] as $environmentConfiguration) {
            $portal->addEnvironment($this->buildEnvironment($environmentConfiguration));
        }

        return $portal;
    }

    protected function buildTemplates( array $webspaceConfiguration, Webspace $webspace): void
    {
        $webspace->setTheme($webspaceConfiguration['theme']);

        foreach ($webspaceConfiguration['templates'] as $type => $template) {
            $webspace->addTemplate($type, $template);
        }
        foreach ($webspaceConfiguration['default_templates'] as $type => $defaultTemplate) {
            $webspace->addDefaultTemplate($type, $defaultTemplate);
        }
        foreach ($webspaceConfiguration['excluded_templates'] as $excludedTemplate) {
            $webspace->addExcludedTemplate($excludedTemplate);
        }
    }

    protected function buildEnvironment(array $environmentConfiguration): Environment
    {
        $environment = new Environment();
        $environment->setType($environmentConfiguration['type']);

        foreach ($environmentConfiguration['urls'] as $urlConfiguration) {
            dump($urlConfiguration);
            $url = new Url();
            $url->setUrl($urlConfiguration['value']);
            $url->setLanguage($urlConfiguration['language']);
            $url->setCountry($urlConfiguration['country'] ?? null);
            $url->setSegment($urlConfiguration['segment'] ?? null);
            $url->setRedirect($urlConfiguration['redirect'] ?? null);
            $url->setMain($urlConfiguration['main'] ?? null);

            $environment->addUrl($url);
        }

        foreach ($environmentConfiguration['customUrls'] as $customUrl) {
            $environment->addCustomUrl(new CustomUrl($customUrl['url']));
        }

        return $environment;
    }

    protected function buildLocalization(array $localizationConfiguration): Localization
    {
        $localization = new Localization($localizationConfiguration['language']);
        $localization->setCountry($localizationConfiguration['country'] ?? null);
        $localization->setShadow($localizationConfiguration['shadow'] ?? null);
        $localization->setDefault($localizationConfiguration['default']);

        return $localization;
    }

    /**
     * @param array<mixed> $localizationConfiguration
     * @param array<Localization> $localization
     */
    private function buildLocalizationTree(
        array $localizationConfiguration,
        array &$localization,
        int $currentIndex,
        int $parentIndex,
        string $webspaceKey
    ): void {
        $localization[$currentIndex] = $this->buildLocalization($localizationConfiguration);
        $localization[$currentIndex]->setParent($localization[$parentIndex]);

        //$localizationRefs[$webspaceKey.'_'.$localization[$currentIndex]] = $localization[$currentIndex];

        foreach ($localizationConfiguration['children'] as $childLocalization) {
            $this->buildLocalizationTree($childLocalization, $localization, $currentIndex + 1, $currentIndex);
        }

        $localization[$currentIndex - 1]->addChild($localization[$currentIndex]);
    }
}
