<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\DependencyInjection;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupCondition;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupConditionRepository;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepository;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRule;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleRepository;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspace;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceRepository;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration definition of sulu_audience_targeting.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_audience_targeting');

        $rootNode
            ->children()
                ->scalarNode('number_of_priorities')
                    ->defaultValue(5)
                ->end()
            ->end();

        $this->addObjectsSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds `objects` section.
     *
     * @param ArrayNodeDefinition $node
     */
    private function addObjectsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('target_group')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue(TargetGroup::class)
                                ->end()
                                ->scalarNode('repository')
                                    ->defaultValue(TargetGroupRepository::class)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('target_group_condition')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue(TargetGroupCondition::class)
                                ->end()
                                ->scalarNode('repository')
                                    ->defaultValue(TargetGroupConditionRepository::class)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('target_group_rule')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue(TargetGroupRule::class)
                                ->end()
                                ->scalarNode('repository')
                                    ->defaultValue(TargetGroupRuleRepository::class)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('target_group_webspace')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue(TargetGroupWebspace::class)
                                ->end()
                                ->scalarNode('repository')
                                    ->defaultValue(TargetGroupWebspaceRepository::class)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
