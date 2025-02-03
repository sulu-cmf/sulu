<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;

class SuluSearchExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'resources' => [
                        'search' => [
                            'routes' => [
                                'list' => 'sulu_search_search',
                            ],
                        ],
                        'search_indexes' => [
                            'routes' => [
                                'list' => 'sulu_search_indexes',
                            ],
                        ],
                    ],
                ]
            );
        }

        $container->prependExtensionConfig('jms_serializer', [
            'metadata' => [
                'directories' => [
                    [
                        'name' => 'massive_search',
                        'path' => \realpath(__DIR__ . '/..') . '/Resources/config/serializer/massive',
                        'namespace_prefix' => 'Massive\Bundle\SearchBundle\Search',
                    ],
                    [
                        'name' => 'sulu_search',
                        'path' => \realpath(__DIR__ . '/..') . '/Resources/config/serializer/sulu',
                        'namespace_prefix' => 'Sulu\Bundle\SearchBundle\Search',
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('massive_search', [
            'services' => [
                'factory' => 'sulu_search.search.factory',
            ],
            'persistence' => [
                'doctrine_orm' => [
                    'enabled' => true,
                ],
            ],
            'adapters' => [
                'zend_lucene' => [
                    'basepath' => '%kernel.project_dir%/var/indexes',
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_search.indexes', $config['indexes']);
        $container->setParameter(
            'sulu_search.website.indexes',
            \array_values(
                \array_filter($config['website']['indexes'])
            )
        );

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('search.xml');
        $loader->load('build.xml');
    }
}
