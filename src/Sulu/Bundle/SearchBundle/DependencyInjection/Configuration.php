<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_search');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('indexes')
                    ->useAttributeAsKey('index_name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('icon')->isRequired()->end()
                            ->arrayNode('view')
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->arrayNode('result_to_view')
                                        ->isRequired()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('security_context')->end()
                            ->arrayNode('contexts')
                                ->prototype('scalar')
                                    ->defaultValue([])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('website')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('indexes')
                            ->useAttributeAsKey('key')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
