<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal this is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_location');
        $treeBuilder->getRootNode()
            ->children()
                ->enumNode('geolocator')
                    ->values(['nominatim', 'google', 'mapquest'])
                    ->defaultValue('nominatim')
                ->end()
                ->arrayNode('geolocators')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('nominatim')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('api_key')->defaultValue('')->end()
                                ->scalarNode('endpoint')->defaultValue('https://nominatim.openstreetmap.org/search')->end()
                            ->end()
                        ->end()
                        ->arrayNode('google')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('api_key')->defaultValue('')->end()
                            ->end()
                        ->end()
                        ->arrayNode('mapquest')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('api_key')->defaultValue('')->end()
                                ->scalarNode('endpoint')->defaultValue('https://www.mapquestapi.com/geocoding/v1/address')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
