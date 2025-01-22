<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @var bool
     */
    private $debug;

    /**
     * @param bool $debug Whether to use the debug mode
     */
    public function __construct($debug)
    {
        $this->debug = $debug;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_http_cache');
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->arrayNode('tags')
                    ->canBeDisabled()
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_age')->defaultValue(240)->end()
                        ->integerNode('shared_max_age')->defaultValue(240)->end()
                    ->end()
                ->end()
                ->arrayNode('proxy_client')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('symfony')
                            ->canBeEnabled()
                            ->addDefaultsIfNotSet()
                            ->fixXmlConfig('server')
                            ->children()
                                ->arrayNode('servers')
                                    ->beforeNormalization()->ifString()->then(function($v) {
                                        return \preg_split('/\s*,\s*/', $v);
                                    })->end()
                                    ->useAttributeAsKey('name')
                                    ->prototype('scalar')->end()
                                    ->info('Addresses of the hosts Symfony is running on. May be hostname or ip, and with :port if not the default port 80.')
                                ->end()
                                ->scalarNode('base_url')
                                    ->defaultNull()
                                    ->info('Default host name and optional path for path based invalidation.')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('varnish')
                            ->canBeEnabled()
                            ->addDefaultsIfNotSet()
                            ->fixXmlConfig('server')
                            ->children()
                                ->arrayNode('servers')
                                    ->beforeNormalization()->ifString()->then(function($v) {
                                        return \preg_split('/\s*,\s*/', $v);
                                    })->end()
                                    ->useAttributeAsKey('name')
                                    ->prototype('scalar')->end()
                                    ->info('Addresses of the hosts Varnish is running on. May be hostname or ip, and with :port if not the default port 80.')
                                ->end()
                                ->scalarNode('base_url')
                                    ->defaultNull()
                                    ->info('Default host name and optional path for path based invalidation.')
                                ->end()
                                ->enumNode('tag_mode')
                                    ->info('Use the purgekeys mode for more efficient tag handling, if your Varnish server supports the xkey module')
                                    ->values(['ban', 'purgekeys'])
                                    ->defaultValue('ban')
                                ->end()
                                ->scalarNode('tags_header')
                                    ->info('HTTP header to use when sending tag invalidation requests to Varnish')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('nginx')
                            ->canBeEnabled()
                            ->addDefaultsIfNotSet()
                            ->fixXmlConfig('server')
                            ->children()
                                ->arrayNode('servers')
                                    ->beforeNormalization()->ifString()->then(function($v) {
                                        return \preg_split('/\s*,\s*/', $v);
                                    })->end()
                                    ->useAttributeAsKey('name')
                                    ->prototype('scalar')->end()
                                    ->info('Addresses of the hosts Nginx is running on. May be hostname or ip, and with :port if not the default port 80.')
                                ->end()
                                ->scalarNode('base_url')
                                    ->defaultNull()
                                    ->info('Default host name and optional path for path based invalidation.')
                                ->end()
                                ->scalarNode('purge_location')
                                    ->defaultValue(false)
                                    ->info('Path to trigger the purge on Nginx for different location purge.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('debug')
                    ->addDefaultsIfNotSet()
                    ->canBeEnabled()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultValue($this->debug)
                            ->info('Whether to send a debug header with the response to trigger a caching proxy to send debug information. If not set, defaults to kernel.debug.')
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
