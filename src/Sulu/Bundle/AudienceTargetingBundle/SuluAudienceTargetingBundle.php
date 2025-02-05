<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle;

use Sulu\Bundle\AudienceTargetingBundle\DependencyInjection\Compiler\AddRulesPass;
use Sulu\Bundle\AudienceTargetingBundle\DependencyInjection\Compiler\DeviceDetectorCachePass;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupConditionInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceInterface;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Sulu Audience Targeting Bundle is for managing target groups, their rules and conditions
 * and applying them to certain contents to delivery user specific content.
 */
final class SuluAudienceTargetingBundle extends Bundle
{
    use PersistenceBundleTrait;

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        $this->buildPersistence(
            [
                TargetGroupInterface::class => 'sulu.model.target_group.class',
                TargetGroupConditionInterface::class => 'sulu.model.target_group_condition.class',
                TargetGroupRuleInterface::class => 'sulu.model.target_group_rule.class',
                TargetGroupWebspaceInterface::class => 'sulu.model.target_group_webspace.class',
            ],
            $container
        );

        $container->addCompilerPass(new AddRulesPass());
        $container->addCompilerPass(new DeviceDetectorCachePass());
    }
}
