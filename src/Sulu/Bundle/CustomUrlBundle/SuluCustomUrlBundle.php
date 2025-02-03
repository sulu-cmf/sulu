<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle;

use Sulu\Component\Route\RouteDefaultOptionsCompilerPass;
use Symfony\Cmf\Component\Routing\DependencyInjection\Compiler\RegisterRouteEnhancersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluCustomUrlBundle extends Bundle
{
    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(
            new RouteDefaultOptionsCompilerPass('sulu_custom_urls.routing.provider', 3)
        );
        $container->addCompilerPass(
            new RegisterRouteEnhancersPass('sulu_custom_urls.routing.router', 'sulu_custom_urls.route_enhancer')
        );
    }
}
