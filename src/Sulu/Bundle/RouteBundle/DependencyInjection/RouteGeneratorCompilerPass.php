<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects route-generator by configured mappings.
 */
class RouteGeneratorCompilerPass implements CompilerPassInterface
{
    const TAG_NAME = 'sulu.route_generator';
    const SERVICE_ID = 'sulu_route.manager.route_manager';
    const PARAMETER_NAME = 'sulu_route.mappings';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_ID) || !$container->hasParameter(self::PARAMETER_NAME)) {
            return;
        }

        $config = $container->getParameter(self::PARAMETER_NAME);

        $generators = [];
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $tags) {
            foreach ($tags as $attributes) {
                $generators[$attributes['alias']] = $id;
            }
        }

        $services = [];
        foreach ($config as $item) {
            $serviceId = $generators[$item['generator']];
            $services[$item['generator']] = new Reference($serviceId);

            // validate options
            $generator = $container->get($serviceId);
            $optionsResolver = $generator->getOptionsResolver($item['options']);
            $optionsResolver->resolve($item['options']);
        }

        $definition = $container->getDefinition(self::SERVICE_ID);
        $definition->replaceArgument(0, $services);
    }
}
