<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal this is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_snippet');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('types')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('snippet')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('default_enabled')->defaultValue(true)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('twig')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('snippet')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('cache_lifetime')
                                    ->defaultValue(1)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
