<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle;

use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\WebsiteBundle\DependencyInjection\Compiler\DeregisterDefaultRouteListenerCompilerPass;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Sulu\Component\Route\RouteDefaultOptionsCompilerPass;
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Sulu\Component\Util\SuluVersionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluWebsiteBundle extends Bundle
{
    use PersistenceBundleTrait;

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SuluVersionPass());
        $container->addCompilerPass(new DeregisterDefaultRouteListenerCompilerPass());
        $container->addCompilerPass(
            new RouteDefaultOptionsCompilerPass('sulu_website.provider.content', 7)
        );

        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu_website.reference_store_pool',
                'sulu_website.reference_store',
                0,
                'alias'
            )
        );

        $this->buildPersistence(
            [
                AnalyticsInterface::class => 'sulu.model.analytics.class',
            ],
            $container
        );
    }
}
