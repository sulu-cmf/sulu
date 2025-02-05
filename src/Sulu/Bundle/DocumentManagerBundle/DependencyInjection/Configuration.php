<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal this is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_document_manager');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('default_session')->end()
                ->scalarNode('live_session')->end()
                ->arrayNode('sessions')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('backend')
                                ->useAttributeAsKey('name')
                                ->prototype('variable')->end()
                            ->end()
                            ->scalarNode('workspace')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('username')
                                ->defaultValue('admin')
                            ->end()
                            ->scalarNode('password')
                                ->defaultValue('admin')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('namespace')
                    ->useAttributeAsKey('role')
                    ->defaultValue([
                        'extension_localized' => 'i18n',
                        'system' => 'sulu',
                        'system_localized' => 'i18n',
                        'content' => null,
                        'content_localized' => 'i18n',
                    ])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('versioning')
                    ->canBeEnabled()
                ->end()
                ->scalarNode('debug')
                    ->info('Enable the debug event dispatcher. Logs all document manager events. Very slow.')
                    ->defaultValue(false)
                ->end()
                ->arrayNode('path_segments')
                    ->useAttributeAsKey('key')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('mapping')
                    ->useAttributeAsKey('alias')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')
                                ->info('Fully qualified class name for mapped object')
                                ->isRequired()
                            ->end()
                            ->scalarNode('phpcr_type')
                                ->info('PHPCR type to map to')
                                ->isRequired()
                            ->end()
                            ->scalarNode('form_type')
                                ->info('Form type to map to')
                            ->end()
                            ->scalarNode('sync_remove_live')
                                ->info('Should sync live workspace if node was removed')
                            ->end()
                            ->scalarNode('set_default_author')
                                ->info('Set default author if none set')
                            ->end()
                            ->arrayNode('mapping')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('encoding')->defaultValue('content')->end()
                                        ->scalarNode('property')->end()
                                        ->scalarNode('type')->end()
                                        ->booleanNode('mapped')->defaultTrue()->end()
                                        ->booleanNode('multiple')->defaultFalse()->end()
                                        ->scalarNode('default')->defaultValue(null)->end()
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
