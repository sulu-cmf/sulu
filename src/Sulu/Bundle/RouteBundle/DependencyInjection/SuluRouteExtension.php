<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Container extension for sulu-route-bundle.
 */
class SuluRouteExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'resources' => [
                        'routes' => [
                            'routes' => [
                                'list' => 'sulu_routes.get_routes',
                            ],
                        ],
                    ],
                ]
            );
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('sulu_route.mappings', $config['mappings']);

        $resourceKeyMappings = [];
        foreach ($config['mappings'] as $entityClass => $mapping) {
            $resourceKeyMappings[$mapping['resource_key']] = $mapping;
            $resourceKeyMappings[$mapping['resource_key']]['entityClass'] = $entityClass;
        }

        $container->setParameter(
            'sulu_route.resource_key_mappings',
            $resourceKeyMappings
        );

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('routing.xml');
        $loader->load('manager.xml');
        $loader->load('generator.xml');
        $loader->load('command.xml');
        $loader->load('page_tree_move.xml');

        $pageRouteCascade = $config['content_types']['page_tree_route']['page_route_cascade'];

        if ('off' !== $pageRouteCascade) {
            $loader->load('page_tree_update.xml');
        } else {
            $container->setAlias(
                'sulu_route.page_tree_route.updater.request',
                'sulu_route.page_tree_route.updater.off'
            );
        }

        /** @var array<string, class-string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if ('task' === $pageRouteCascade && !\array_key_exists('SuluAutomationBundle', $bundles)) {
            throw new InvalidConfigurationException(
                'You need to install the SuluAutomationBundle to use task cascading!'
            );
        }

        $container->setAlias(
            'sulu_route.page_tree_route.updater',
            'sulu_route.page_tree_route.updater.' . $pageRouteCascade
        );

        $this->configurePersistence($config['objects'], $container);
        $container->addAliases(
            [
                RouteRepositoryInterface::class => 'sulu.repository.route',
            ]
        );
    }
}
