<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\DependencyInjection;

use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Entity\RouteRepository;
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
        $treeBuilder = new TreeBuilder('sulu_route');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('mappings')
                    ->useAttributeAsKey('className')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('generator')->defaultNull()->end()
                            ->arrayNode('options')
                                ->defaultValue([])
                                ->normalizeKeys(false)
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('resource_key')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('content_types')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('page_tree_route')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('page_route_cascade')
                                    ->values(['request', 'task', 'off'])
                                    ->defaultValue('request')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->addObjectsSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds `objects` section.
     */
    private function addObjectsSection(ArrayNodeDefinition $node)
    {
        $node->children()
            ->arrayNode('objects')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('route')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('model')->defaultValue(Route::class)->end()
                            ->scalarNode('repository')->defaultValue(RouteRepository::class)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
