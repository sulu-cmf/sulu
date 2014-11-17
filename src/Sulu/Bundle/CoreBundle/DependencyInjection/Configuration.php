<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
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
        $rootNode = $treeBuilder->root('sulu_core');

        $children = $rootNode->children();
        $this->getPhpcrConfiguration($children);
        $this->getContentConfiguration($children);
        $this->getWebspaceConfiguration($children);
        $this->getHttpCacheConfiguration($children);
        $this->getFieldsConfiguration($children);
        $this->getCoreConfiguration($children);
        $this->getCacheConfiguration($children);
        $children->end();

        return $treeBuilder;
    }

    /**
     * @param NodeBuilder $rootNode
     */
    private function getCoreConfiguration(NodeBuilder $rootNode)
    {
        $rootNode->scalarNode('cache_dir')->defaultValue('%kernel.cache_dir%/sulu')->end();
    }

    /**
     * @param NodeBuilder $rootNode
     */
    private function getWebspaceConfiguration(NodeBuilder $rootNode)
    {
        $rootNode->arrayNode('webspace')
            ->children()
                ->scalarNode('config_dir')
                    ->defaultValue('%kernel.root_dir%/Resources/webspaces')
                ->end()
                ->arrayNode('request_analyzer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('priority')
                            ->defaultValue(300)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function getHttpCacheConfiguration(NodeBuilder $rootNode)
    {
        $rootNode->arrayNode('http_cache')
            ->children()
                ->scalarNode('type')
                    ->defaultValue('SymfonyHttpCache')
                ->end()
            ->end()
        ->end();
    }

    /**
     * @param NodeBuilder $rootNode
     */
    private function getPhpcrConfiguration(NodeBuilder $rootNode)
    {
        $rootNode->arrayNode('phpcr')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('workspace')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('username')
                    ->defaultNull()
                ->end()
                ->scalarNode('password')
                    ->defaultNull()
                ->end()
                ->arrayNode('backend')
                    ->useAttributeAsKey('name')
                    ->prototype('variable')
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $rootNode
     */
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

    /**
     * @param NodeBuilder $rootNode
     */
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
                            ->defaultValue('en')
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
                        ->scalarNode('temp')
                            ->defaultValue('temp')
                        ->end()
                        ->scalarNode('snippet')
                            ->defaultValue('snippets')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('types')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('text_line')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/text_line.html.twig')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('text_area')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/text_area.html.twig')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('text_editor')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/text_editor.html.twig')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('resource_locator')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/resource_locator.html.twig')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('block')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/block.html.twig')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('structure')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('default_type')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('page')
                                    ->defaultValue('default')
                                ->end()
                                ->scalarNode('snippet')
                                    ->defaultValue('default')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('paths')
                            ->isRequired()
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('path')
                                        ->example('%kernel.root_dir%/Resources/templates')
                                    ->end()
                                    ->scalarNode('internal')
                                        ->example('false')
                                    ->end()
                                    ->scalarNode('type')
                                        ->defaultValue('page')
                                    ->end()
                                ->end()
                            ->end()
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
