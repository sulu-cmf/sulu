<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Infrastructure\Symfony\HttpKernel;

use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Infrastructure\Doctrine\EventListener\RouteChangedUpdater;
use Sulu\Route\Infrastructure\Doctrine\Repository\RouteRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @experimental
 *
 * @codeCoverageIgnore
 */
final class SuluRouteBundle extends AbstractBundle
{
    /**
     * @param array<string, mixed> $config
     *
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        // Doctrine Route Updater Listener
        $services->set('sulu_route.doctrine_route_changed_updater')
            ->class(RouteChangedUpdater::class)
            ->tag('doctrine.event_listener', ['event' => 'preUpdate', 'entity' => Route::class, 'method' => 'preUpdate'])
            ->tag('doctrine.event_listener', ['event' => 'postFlush', 'method' => 'postFlush'])
            ->tag('doctrine.event_listener', ['event' => 'onClear', 'method' => 'onClear']);

        // Repositories services
        $services->set('sulu_route.route_repository')
            ->class(RouteRepository::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
            ]);

        $services->alias(RouteRepositoryInterface::class, 'sulu_route.route_repository')
            ->public();
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('doctrine')) {
            $builder->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'SuluRoute' => [
                                'type' => 'xml',
                                'prefix' => 'Sulu\Route\Domain\Model',
                                'dir' => \dirname(__DIR__, 4) . '/config/doctrine/Route',
                                'alias' => 'SuluRoute',
                                'is_bundle' => false,
                                'mapping' => true,
                            ],
                        ],
                    ],
                ],
            );
        }
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function getPath(): string
    {
        return \dirname(__DIR__, 4); // target the root of the library where config, src, ... is located
    }
}
