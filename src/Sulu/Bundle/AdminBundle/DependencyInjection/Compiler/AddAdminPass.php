<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add all admin-services with the tag "sulu.admin" to the AdminPool-Service.
 */
class AddAdminPass implements CompilerPassInterface
{
    const ADMIN_POOL_DEFINITION_ID = 'sulu_admin.admin_pool';
    const ADMIN_TAG = 'sulu.admin';

    public function process(ContainerBuilder $container)
    {
        $pool = $container->getDefinition(self::ADMIN_POOL_DEFINITION_ID);

        $adminServiceDefinitions = [];

        foreach ($container->findTaggedServiceIds(self::ADMIN_TAG) as $id => $tags) {
            $serviceDefinition = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($serviceDefinition->getClass());

            /** @var callable $callable */
            $callable = [$class, 'getPriority'];
            $priority = \call_user_func($callable);

            $adminServiceDefinitions[$priority][] = $serviceDefinition;
        }

        \krsort($adminServiceDefinitions);

        foreach ($adminServiceDefinitions as $priority => $serviceDefinitions) {
            foreach ($serviceDefinitions as $serviceDefinition) {
                $pool->addMethodCall('addAdmin', [$serviceDefinition]);
            }
        }
    }
}
