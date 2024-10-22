<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\DependencyInjection;

use Composer\InstalledVersions;
use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotUniqueException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryNameMissingException;
use Sulu\Bundle\CategoryBundle\Exception\KeywordIsMultipleReferencedException;
use Sulu\Bundle\CategoryBundle\Exception\KeywordNotUniqueException;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluCategoryExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('command.xml');

        $bundles = $container->getParameter('kernel.bundles');
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

        $this->configurePersistence($config['objects'], $container);
        $container->addAliases(
            [
                CategoryRepositoryInterface::class => 'sulu.repository.category',
                CategoryMetaRepositoryInterface::class => 'sulu.repository.category_meta',
                CategoryTranslationRepositoryInterface::class => 'sulu.repository.category_translation',
                KeywordRepositoryInterface::class => 'sulu.repository.keyword',
            ]
        );
    }

    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            CategoryIdNotFoundException::class => 404,
                            CategoryKeyNotFoundException::class => 404,
                            CategoryKeyNotUniqueException::class => 409,
                            CategoryNameMissingException::class => 400,
                            KeywordIsMultipleReferencedException::class => 409,
                            KeywordNotUniqueException::class => 409,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig(
                'jms_serializer',
                [
                    'metadata' => [
                        'directories' => [
                            [
                                'name' => 'sulu_category',
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Bundle\CategoryBundle\Entity',
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
                        CategoryInterface::RESOURCE_KEY => [
                            'routes' => [
                                'list' => 'sulu_category.get_categories',
                                'detail' => 'sulu_category.get_category',
                            ],
                        ],
                        'category_keywords' => [
                            'routes' => [
                                'list' => 'sulu_category.get_category_keywords',
                                'detail' => 'sulu_category.get_category_keyword',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'category_selection' => [
                                'default_type' => 'list',
                                'resource_key' => CategoryInterface::RESOURCE_KEY,
                                'types' => [
                                    'list' => [
                                        'adapter' => 'tree_table_slim',
                                        'list_key' => 'categories',
                                    ],
                                ],
                            ],
                        ],
                        'single_selection' => [
                            'single_category_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => CategoryInterface::RESOURCE_KEY,
                                'view' => [
                                    'name' => 'sulu_category.edit_form',
                                    'result_to_view' => [
                                        'id' => 'id',
                                    ],
                                ],
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'tree_table_slim',
                                        'list_key' => 'categories',
                                        'display_properties' => ['name'],
                                        'empty_text' => 'sulu_category.no_category_selected',
                                        'icon' => 'su-tag',
                                        'overlay_title' => 'sulu_category.single_category_selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_search')) {
            $container->prependExtensionConfig(
                'sulu_search',
                [
                    'indexes' => [
                        'category' => [
                            'name' => 'sulu_category.categories',
                            'icon' => 'su-tag',
                            'view' => [
                                'name' => CategoryAdmin::EDIT_FORM_VIEW,
                                'result_to_view' => [
                                    'id' => 'id',
                                    'locale' => 'locale',
                                ],
                            ],
                            'security_context' => CategoryAdmin::SECURITY_CONTEXT,
                        ],
                    ],
                ]
            );
        }
    }
}
