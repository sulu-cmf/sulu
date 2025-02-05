<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
use Sulu\Bundle\AudienceTargetingBundle\EventListener\AudienceTargetingCacheListener;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal this is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_audience_targeting');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('headers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('target_group')->defaultValue(AudienceTargetingCacheListener::TARGET_GROUP_HEADER)->end()
                        ->scalarNode('url')->defaultValue(AudienceTargetingCacheListener::USER_CONTEXT_URL_HEADER)->end()
                    ->end()
                ->end()
                ->scalarNode('url')->defaultValue(AudienceTargetingCacheListener::TARGET_GROUP_URL)->end()
                ->arrayNode('cookies')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('target_group')->defaultValue(AudienceTargetingCacheListener::TARGET_GROUP_COOKIE)->end()
                        ->scalarNode('session')->defaultValue(AudienceTargetingCacheListener::VISITOR_SESSION_COOKIE)->end()
                    ->end()
                ->end()
                ->arrayNode('hit')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('url')->defaultValue('/_sulu_target_group_hit')->end()
                        ->arrayNode('headers')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('referrer')->defaultValue('X-Forwarded-Referrer')->end()
                                ->scalarNode('uuid')->defaultValue('X-Forwarded-UUID')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('number_of_priorities')
                    ->defaultValue(5)
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
