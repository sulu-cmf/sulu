<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder->root('sulu_admin')
            ->children()
                ->scalarNode('name')->defaultValue('Sulu Admin')->end()
                ->scalarNode('email')->isRequired()->end()
                ->scalarNode('user_data_service')->defaultValue('sulu_security.user_manager')->end()
                ->arrayNode('resources')
                    ->useAttributeAsKey('resourceKey')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('datagrid')->end()
                            ->scalarNode('endpoint')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('forms')
                    ->children()
                        ->arrayNode('directories')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('field_type_options')
                    ->children()
                        ->arrayNode('selection')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('default_type')
                                        ->isRequired()
                                        ->validate()
                                            ->ifNotInArray(['auto_complete', 'datagrid', 'datagrid_overlay'])
                                            ->thenInvalid('Invalid selection type "%s"')
                                        ->end()
                                    ->end()
                                    ->scalarNode('resource_key')->isRequired()->end()
                                    ->arrayNode('types')
                                        ->isRequired()
                                        ->children()
                                            ->arrayNode('auto_complete')
                                                ->children()
                                                    ->booleanNode('allow_add')->defaultFalse()->end()
                                                    ->scalarNode('id_property')->defaultValue('id')->end()
                                                    ->scalarNode('display_property')->isRequired()->end()
                                                    ->scalarNode('filter_parameter')->end()
                                                    ->arrayNode('search_properties')
                                                        ->isRequired()
                                                        ->requiresAtLeastOneElement()
                                                        ->prototype('scalar')
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                            ->arrayNode('datagrid')
                                                ->children()
                                                    ->scalarNode('adapter')->isRequired()->end()
                                                ->end()
                                            ->end()
                                            ->arrayNode('datagrid_overlay')
                                                ->children()
                                                    ->scalarNode('adapter')->isRequired()->end()
                                                    ->arrayNode('display_properties')
                                                        ->isRequired()
                                                        ->requiresAtLeastOneElement()
                                                        ->prototype('scalar')
                                                        ->end()
                                                    ->end()
                                                    ->scalarNode('icon')->isRequired()->end()
                                                    ->scalarNode('label')->isRequired()->end()
                                                    ->scalarNode('overlay_title')->isRequired()->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('single_selection')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('default_type')
                                        ->isRequired()
                                        ->validate()
                                            ->ifNotInArray(['auto_complete', 'datagrid_overlay'])
                                            ->thenInvalid('Invalid selection type "%s"')
                                        ->end()
                                    ->end()
                                    ->scalarNode('resource_key')->isRequired()->end()
                                    ->arrayNode('types')
                                        ->isRequired()
                                        ->children()
                                            ->arrayNode('auto_complete')
                                                ->children()
                                                    ->scalarNode('display_property')->isRequired()->end()
                                                    ->arrayNode('search_properties')
                                                        ->isRequired()
                                                        ->requiresAtLeastOneElement()
                                                        ->prototype('scalar')
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                            ->arrayNode('datagrid_overlay')
                                                ->children()
                                                    ->scalarNode('adapter')->isRequired()->end()
                                                    ->arrayNode('display_properties')
                                                        ->isRequired()
                                                        ->requiresAtLeastOneElement()
                                                        ->prototype('scalar')
                                                        ->end()
                                                    ->end()
                                                    ->scalarNode('icon')->isRequired()->end()
                                                    ->scalarNode('empty_text')->isRequired()->end()
                                                    ->scalarNode('overlay_title')->isRequired()->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
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
