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
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entry point of sulu-route-bundle.
 */
final class SuluRouteBundle extends Bundle
{
    use PersistenceBundleTrait;

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RouteGeneratorCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1024);
        $container->addCompilerPass(
            new RouteDefaultOptionsCompilerPass('sulu_route.routing.provider', 5)
        );
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass('sulu_route.routing.defaults_provider', 'sulu_route.defaults_provider')
        );

        $this->buildPersistence(
            [
                RouteInterface::class => 'sulu.model.route.class',
            ],
            $container
        );
    }
}
