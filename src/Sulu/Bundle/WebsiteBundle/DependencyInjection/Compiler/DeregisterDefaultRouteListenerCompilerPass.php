<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes the `kernel.event_subscriber` tag from the default route listener. This is necessary because it is decorated
 * by the `sulu_website.router_listener` service, which adds the analyzing of the request done by Sulu to this listener.
 */
class DeregisterDefaultRouteListenerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('router_listener')->clearTag('kernel.event_subscriber');
    }
}
