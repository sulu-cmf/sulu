<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_page');
        $rootNode = $treeBuilder->getRootNode();

        // add config preview interval
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('default_author')->defaultTrue()->info('Set default author if none isset')->end()
                ->arrayNode('seo')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('max_title_length')->defaultValue(70)->end()
                        ->scalarNode('max_description_length')->defaultValue(160)->end()
                        ->scalarNode('max_keywords')->defaultValue(5)->end()
                        ->scalarNode('keywords_separator')->defaultValue(',')->end()
                        ->scalarNode('url_prefix')->defaultValue('www.yoursite.com/{locale}')->end()
                    ->end()
                ->end()
                ->arrayNode('search')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('mapping')
                            ->useAttributeAsKey('structure_type')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('index')->info('Name of index to use')->isRequired()->end()
                                    ->booleanNode('decorate_index')->info('Decorate Index name')->defaultFalse()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
