<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DependencyInjection;

use Sulu\Bundle\WebsiteBundle\Entity\Analytics;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepository;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_website');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->arrayNode('analytics')
                ->canBeDisabled()
            ->end()
            ->arrayNode('segments')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('switch_url')->defaultValue('/_sulu_segment_switch')->end()
                    ->scalarNode('cookie')->defaultValue('_ss')->end()
                    ->scalarNode('header')->defaultValue('X-Sulu-Segment')->end()
                ->end()
            ->end()
            ->arrayNode('twig')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('attributes')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('urls')
                                ->defaultTrue()
                                ->beforeNormalization()
                                    ->ifTrue(function($v) { return false !== $v; })
                                    ->then(function($v) {
                                        @trigger_deprecation('sulu/sulu', '2.2', 'Enabling the "urls" parameter is deprecated.');

                                        return $v;
                                    })
                                ->end()
                            ->end()
                            ->booleanNode('path')
                                ->defaultTrue()
                                ->beforeNormalization()
                                    ->ifTrue(function($v) { return false !== $v; })
                                    ->then(function($v) {
                                        @trigger_deprecation('sulu/sulu', '2.3', 'Enabling the "path" parameter is deprecated.');

                                        return $v;
                                    })
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('navigation')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('cache_lifetime')->defaultValue(1)->end()
                        ->end()
                    ->end()
                    ->arrayNode('content')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('cache_lifetime')->defaultValue(1)->end()
                        ->end()
                    ->end()
                    ->arrayNode('sitemap')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('cache_lifetime')->defaultValue(3600)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('sitemap')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('dump_dir')->defaultValue('%sulu.cache_dir%/sitemaps')->end()
                ->end()
            ->end()
            ->arrayNode('default_locale')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('provider_service_id')->defaultValue('sulu_website.default_locale.portal_provider')->end()
                ->end()
            ->end();

        $this->addObjectsSection($rootNode);

        return $treeBuilder;
    }

    private function addObjectsSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('analytics')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Analytics::class)->end()
                                ->scalarNode('repository')->defaultValue(AnalyticsRepository::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
