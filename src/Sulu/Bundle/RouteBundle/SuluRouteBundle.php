<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle;

use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\RouteBundle\DependencyInjection\RouteGeneratorCompilerPass;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Route\RouteDefaultOptionsCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entry point of sulu-route-bundle.
 *
 * @final
 */
class SuluRouteBundle extends Bundle
{
    use PersistenceBundleTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RouteGeneratorCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1024);
        $container->addCompilerPass(
            new RouteDefaultOptionsCompilerPass('sulu_route.routing.provider', 5)
        );

        $this->buildPersistence(
            [
                RouteInterface::class => 'sulu.model.route.class',
            ],
            $container
        );
    }
}
