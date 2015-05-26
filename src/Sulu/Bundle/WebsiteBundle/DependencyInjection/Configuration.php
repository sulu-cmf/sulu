<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_website');

        $rootNode->children()
            ->arrayNode('preview_defaults')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('analytics_key')->defaultValue('UA-SULU-PREVIEW-KEY')->end()
                ->end()
            ->end()
            ->arrayNode('sitemap')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('multi_webspace')->defaultValue(true)->end()
                ->end()
            ->end()
            ->arrayNode('twig')
                ->addDefaultsIfNotSet()
                ->children()
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
                            ->scalarNode('cache_lifetime')->defaultValue(43200)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
