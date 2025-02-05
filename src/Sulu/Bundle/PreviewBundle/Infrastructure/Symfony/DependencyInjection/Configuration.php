<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\DependencyInjection;

use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLink;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal this is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_preview');
        $rootNode = $treeBuilder->getRootNode();

        // add config preview interval
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->children()->end()
                ->end()
                ->scalarNode('mode')
                    ->defaultValue('auto')
                    ->validate()
                        ->ifNotInArray(['auto', 'on_request', 'off'])
                        ->thenInvalid('Invalid preview mode "%s" use one of [auto, on_request, off]')
                    ->end()
                ->end()
                ->scalarNode('delay')
                    ->defaultValue(500)
                    ->info('Used for the delayed send of changes')
                ->end()
                ->scalarNode('cache_adapter')
                    ->defaultValue('cache.app')
                    ->info('Define the symfony framework cache adapter for preview')
                ->end()
                ->arrayNode('cache')
                    ->setDeprecated(
                        'sulu/sulu',
                        '2.1.0',
                        'The "%node%" option is deprecated. Use "cache_adapter" instead.'
                    )
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('type')->defaultValue(null)->end()
                        ->scalarNode('namespace')->defaultNull()->end()
                        ->append($this->addBasicProviderNode('apc'))
                        ->append($this->addBasicProviderNode('apcu'))
                        ->append($this->addBasicProviderNode('array'))
                        ->append($this->addFileSystemNode())
                        ->append($this->addRedisNode())
                    ->end()
                ->end()
            ->end()
        ->end();

        $this->addObjectsSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param string $name
     *
     * @return NodeDefinition
     */
    private function addBasicProviderNode($name)
    {
        $builder = new TreeBuilder($name);
        $node = $builder->getRootNode();

        return $node;
    }

    /**
     * Build file_system node configuration definition.
     *
     * @return NodeDefinition
     */
    private function addFileSystemNode()
    {
        $builder = new TreeBuilder('file_system');
        $node = $builder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('directory')->defaultValue('%sulu.cache_dir%/preview')->end()
                ->scalarNode('extension')->defaultNull()->end()
                ->integerNode('umask')->defaultValue(0002)->end()
            ->end();

        return $node;
    }

    /**
     * Build redis node configuration definition.
     *
     * @return NodeDefinition
     */
    private function addRedisNode()
    {
        $builder = new TreeBuilder('redis');
        $node = $builder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('connection_id')->defaultNull()->end()
                ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                ->scalarNode('port')->defaultValue('6379')->end()
                ->scalarNode('password')->defaultNull()->end()
                ->scalarNode('timeout')->defaultNull()->end()
                ->scalarNode('database')->defaultNull()->end()
            ->end();

        return $node;
    }

    /**
     * Adds `objects` section.
     */
    private function addObjectsSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('preview_link')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue(PreviewLink::class)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
