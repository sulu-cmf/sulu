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

class RouteDefaultOptionsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('routing.loader')) {
            return;
        }

        if (!$container->hasDefinition('sulu_website.provider.content')) {
            return;
        }

        // copy default route options which are set by the symfony FrameworkExtension based on the config:
        // https://github.com/symfony/symfony/pull/31900
        $container->getDefinition('sulu_website.provider.content')->replaceArgument(
            7,
            $container->getDefinition('routing.loader')->getArgument(1)
        );
    }
}
