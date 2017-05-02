<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddRulesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $ruleCollection = $container->getDefinition('sulu_audience_targeting.rules_collection');
        $taggedServices = $container->findTaggedServiceIds('sulu.audience_target_rule');

        $ruleReferences = [];
        foreach ($taggedServices as $id => $attributes) {
            if (!isset($attributes[0]['alias'])) {
                throw new \InvalidArgumentException(
                    sprintf('No "alias" specified for audience targeting rule with service ID: "%s"', $id)
                );
            }

            $ruleReferences[$attributes[0]['alias']] = new Reference($id);
        }

        $ruleCollection->replaceArgument(0, $ruleReferences);
    }
}
