<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Route;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal this is an internal class which should not be used by a project
 */
class RouteDefaultOptionsCompilerPass implements CompilerPassInterface
{
    /**
     * @param string $targetService
     * @param int $targetDefaultOptionsArgument
     */
    public function __construct(private $targetService, private $targetDefaultOptionsArgument)
    {
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('routing.loader')) {
            return;
        }

        if (!$container->hasDefinition('sulu_custom_urls.routing.provider')) {
            return;
        }

        // copy default route options which are set by the symfony FrameworkExtension based on the config:
        // https://github.com/symfony/symfony/pull/31900
        $routeDefaultOptions = $container->getDefinition('routing.loader')->getArgument(1);

        $container->getDefinition($this->targetService)->replaceArgument(
            $this->targetDefaultOptionsArgument,
            $routeDefaultOptions
        );
    }
}
