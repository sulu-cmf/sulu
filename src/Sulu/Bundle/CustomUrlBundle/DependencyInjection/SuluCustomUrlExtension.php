<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\DependencyInjection;

use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Component\CustomUrl\Generator\MissingDomainPartException;
use Sulu\Component\CustomUrl\Manager\RouteNotRemovableException;
use Sulu\Component\CustomUrl\Manager\TitleAlreadyExistsException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Loads configuration and services for custom-urls.
 */
class SuluCustomUrlExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    /**
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('admin.xml');
        $loader->load('document.xml');
        $loader->load('routing.xml');

        if ($container->hasExtension('sulu_trash')) {
            $loader->load('services_trash.xml');
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->configurePersistence($config['objects'], $container);
    }

    /**
     * @return void
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('doctrine')) {
            $container->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'SuluCustomUrlBundle' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../Resources/config/doctrine',
                                'prefix' => 'Sulu\Bundle\CustomUrlBundle\Entity',
                                'alias' => 'SuluCustomUrlBundle',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/lists',
                        ],
                    ],
                    'forms' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/forms',
                        ],
                    ],
                    'resources' => [
                        CustomUrl::RESOURCE_KEY => [
                            'routes' => [
                                'list' => 'sulu_custom_url.cget_webspace_custom-urls',
                                'detail' => 'sulu_custom_url.get_webspace_custom-urls',
                            ],
                        ],
                        'custom_url_routes' => [
                            'routes' => [
                                'list' => 'sulu_custom_url.get_webspace_custom-urls_routes',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            TitleAlreadyExistsException::class => 400,
                            MissingDomainPartException::class => 400,
                            RouteNotRemovableException::class => 420, // Policy Not Fulfilled
                        ],
                    ],
                ]
            );
        }
    }
}
