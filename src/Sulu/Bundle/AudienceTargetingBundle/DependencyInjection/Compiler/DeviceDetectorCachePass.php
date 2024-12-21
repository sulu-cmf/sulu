<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\DependencyInjection\Compiler;

use DeviceDetector\Cache\PSR6Bridge;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DeviceDetectorCachePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('sulu_audience_targeting.device_detector') || !$container->has('cache.system')) {
            return;
        }

        $deviceDetector = $container->findDefinition('sulu_audience_targeting.device_detector');
        $deviceDetector->addMethodCall('setCache', [new Definition(PSR6Bridge::class, [new Reference('cache.system')])]);
    }
}
