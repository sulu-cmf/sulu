<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\DependencyInjection;

use Composer\InstalledVersions;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages tag-bundle configuration.
 */
class SuluTagExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function prepend(ContainerBuilder $container)
    {
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
                        TagInterface::RESOURCE_KEY => [
                            'routes' => [
                                'list' => 'sulu_tag.get_tags',
                                'detail' => 'sulu_tag.get_tag',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'tag_selection' => [
                                'default_type' => 'auto_complete',
                                'resource_key' => TagInterface::RESOURCE_KEY,
                                'types' => [
                                    'auto_complete' => [
                                        'allow_add' => true,
                                        'display_property' => 'name',
                                        'id_property' => 'name',
                                        'filter_parameter' => 'names',
                                        'search_properties' => ['name'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->configurePersistence($config['objects'], $container);
        $container->addAliases(
            [
                TagRepositoryInterface::class => 'sulu.repository.tag',
            ]
        );

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        if (\array_key_exists('SuluTrashBundle', $bundles)) {
            $loader->load('services_trash.xml');
        }

        if (
            InstalledVersions::isInstalled('sulu/content-bundle')
            && \version_compare(InstalledVersions::getVersion('sulu/content-bundle') ?? '0.0.0', '0.9', '>=')
            && \version_compare(InstalledVersions::getVersion('sulu/content-bundle') ?? '0.0.0', '0.10', '<')
        ) {
            $loader->load('services_content.xml');
        }
    }
}
