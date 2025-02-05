<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_core');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $children = $rootNode->children();
        $this->getContentConfiguration($children);
        $this->getWebspaceConfiguration($children);
        $this->getFieldsConfiguration($children);
        $this->getCoreConfiguration($children);
        $this->getCacheConfiguration($children);
        $this->getLocaleConfiguration($children);
        $children->end();

        return $treeBuilder;
    }

    private function getCoreConfiguration(NodeBuilder $rootNode)
    {
        $rootNode->scalarNode('cache_dir')->defaultValue('%kernel.cache_dir%/sulu')->end();
    }

    private function getLocaleConfiguration(NodeBuilder $rootNode)
    {
        $rootNode
            ->arrayNode('locales')
                ->useAttributeAsKey('locale')
                ->prototype('scalar')->end()
                ->defaultValue(['de' => 'Deutsch', 'en' => 'English'])
            ->end()
            ->arrayNode('translations')
                ->prototype('scalar')->end()
                ->defaultValue(['de', 'en'])
            ->end()
            ->scalarNode('fallback_locale')->defaultValue('en')->end();
    }

    private function getWebspaceConfiguration(NodeBuilder $rootNode)
    {
        $rootNode->arrayNode('webspace')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('config_dir')
                    ->defaultValue('%kernel.project_dir%/config/webspaces')
                ->end()
            ->end()
        ->end();
    }

    private function getFieldsConfiguration(NodeBuilder $rootNode)
    {
        $rootNode->arrayNode('fields_defaults')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('translations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('id')->defaultValue('public.id')->end()
                        ->scalarNode('title')->defaultValue('public.title')->end()
                        ->scalarNode('name')->defaultValue('public.name')->end()
                        ->scalarNode('created')->defaultValue('public.created')->end()
                        ->scalarNode('changed')->defaultValue('public.changed')->end()
                    ->end()
                ->end()
                ->arrayNode('widths')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('id')->defaultValue('50px')->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function getContentConfiguration(NodeBuilder $rootNode)
    {
        $rootNode->arrayNode('content')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('internal_prefix')
                    ->defaultValue('')
                ->end()
                ->arrayNode('language')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('namespace')
                            ->defaultValue('i18n')
                        ->end()
                        ->scalarNode('default')
                            ->defaultValue('%kernel.default_locale%')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('node_names')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base')
                            ->defaultValue('cmf')
                        ->end()
                        ->scalarNode('content')
                            ->defaultValue('contents')
                        ->end()
                        ->scalarNode('route')
                            ->defaultValue('routes')
                        ->end()
                        ->scalarNode('snippet')
                            ->defaultValue('snippets')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('structure')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('default_type')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')
                            ->end()
                        ->end()
                        ->arrayNode('required_properties')
                            ->useAttributeAsKey('type')
                            ->arrayPrototype()
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                        ->arrayNode('required_tags')
                            ->useAttributeAsKey('type')
                            ->arrayPrototype()
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                        ->arrayNode('paths')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('path')
                                        ->example('%kernel.project_dir%/config/templates')
                                    ->end()
                                    ->scalarNode('type')
                                        ->defaultValue('page')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('type_map')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function getCacheConfiguration(NodeBuilder $rootNode)
    {
        $rootNode->arrayNode('cache')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('memoize')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_lifetime')->defaultValue(1)->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
