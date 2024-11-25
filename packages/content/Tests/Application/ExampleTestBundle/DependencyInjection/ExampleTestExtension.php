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

namespace Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\DependencyInjection;

use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ExampleTestExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('sulu_core')) {
            $container->prependExtensionConfig(
                'sulu_core',
                [
                    'content' => [
                        'structure' => [
                            'paths' => [
                                Example::TEMPLATE_TYPE => [
                                    'path' => '%kernel.project_dir%/config/templates/examples',
                                    'type' => 'example',
                                ],
                            ],
                            'default_type' => [
                                Example::TEMPLATE_TYPE => 'default', // TODO should not be hardcoded
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
                    'resources' => [
                        Example::RESOURCE_KEY => [
                            'routes' => [
                                'list' => 'example_test.get_examples',
                                'detail' => 'example_test.get_example',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_route')) {
            $container->prependExtensionConfig(
                'sulu_route',
                [
                    'mappings' => [
                        Example::class => [
                            'generator' => 'schema',
                            'options' => [
                                'route_schema' => '/{object["title"]}',
                            ],
                            'resource_key' => Example::RESOURCE_KEY,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_search')) {
            $suluSearchConfigs = $container->getExtensionConfig('sulu_search');

            /** @var array{website?: array{ indexes?: array<string, string> }} $suluSearchConfig */
            foreach ($suluSearchConfigs as $suluSearchConfig) {
                if (isset($suluSearchConfig['website']['indexes'])) {
                    $container->prependExtensionConfig(
                        'sulu_search',
                        [
                            'website' => [
                                'indexes' => [
                                    Example::RESOURCE_KEY => Example::RESOURCE_KEY . '_published',
                                ],
                            ],
                        ]
                    );

                    break;
                }
            }
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}
