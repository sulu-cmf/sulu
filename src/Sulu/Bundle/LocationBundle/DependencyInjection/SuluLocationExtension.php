<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\DependencyInjection;

use GuzzleHttp\Client;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluLocationExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @return void
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'resources' => [
                        'geolocator_locations' => [
                            'routes' => [
                                'list' => 'sulu_location.geolocator_query',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('fos_js_routing')) {
            $container->prependExtensionConfig(
                'fos_js_routing',
                [
                    'routes_to_expose' => [
                        'sulu_location.geolocator_query',
                    ],
                ]
            );
        }
    }

    /**
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->configureGeolocators($config, $container, $loader);
    }

    /**
     * @return void
     */
    private function configureGeolocators(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $geolocatorName = $config['geolocator'] ?? null;
        $geolocators = $config['geolocators'] ?? null;

        $loader->load('geolocator.xml');

        if (\class_exists(Client::class)) {
            $loader->load('guzzle.xml');
        }

        $container->setParameter('sulu_location.geolocator.name', $geolocatorName);
        $container->setAlias('sulu_location.geolocator', 'sulu_location.geolocator.service.' . $geolocatorName);

        $nominatim = function(array $geolocators, ContainerBuilder $container) {
            $apiKey = $geolocators['nominatim']['api_key'];
            $container->setParameter('sulu_location.geolocator.service.nominatim.api_key', $apiKey);

            $endpoint = $geolocators['nominatim']['endpoint'];
            $container->setParameter('sulu_location.geolocator.service.nominatim.endpoint', $endpoint);
        };

        $google = function(array $geolocators, ContainerBuilder $container) {
            $apiKey = $geolocators['google']['api_key'];
            $container->setParameter('sulu_location.geolocator.service.google.api_key', $apiKey);
        };

        $nominatim($geolocators, $container);
        $google($geolocators, $container);
    }
}
