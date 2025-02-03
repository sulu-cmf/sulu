<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\DependencyInjection;

use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Form\SnippetType;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SuluSnippetExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
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
                        'snippets' => [
                            'routes' => [
                                'list' => 'sulu_snippet.get_snippets',
                                'detail' => 'sulu_snippet.get_snippet',
                            ],
                            'views' => [
                                'list' => SnippetAdmin::LIST_VIEW,
                                'detail' => SnippetAdmin::EDIT_FORM_VIEW,
                            ],
                        ],
                        'snippet_areas' => [
                            'routes' => [
                                'list' => 'sulu_snippet.get_snippet-areas',
                                'detail' => 'sulu_snippet.put_snippet-area',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'single_selection' => [
                            'single_snippet_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'snippets',
                                'view' => [
                                    'name' => 'sulu_snippet.edit_form',
                                    'result_to_view' => [
                                        'id' => 'id',
                                    ],
                                ],
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'snippets',
                                        'display_properties' => ['title'],
                                        'icon' => 'su-snippet',
                                        'empty_text' => 'sulu_snippet.no_snippet_selected',
                                        'overlay_title' => 'sulu_snippet.single_snippet_selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                        'selection' => [
                            'snippet_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'snippets',
                                'view' => [
                                    'name' => 'sulu_snippet.edit_form',
                                    'result_to_view' => [
                                        'id' => 'id',
                                    ],
                                ],
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'snippets',
                                        'display_properties' => ['title'],
                                        'icon' => 'su-snippet',
                                        'label' => 'sulu_snippet.selection_label',
                                        'overlay_title' => 'sulu_snippet.selection_overlay_title',
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
                        'snippet' => [
                            'name' => 'sulu_snippet.snippets',
                            'icon' => 'su-snippet',
                            'view' => [
                                'name' => SnippetAdmin::EDIT_FORM_VIEW,
                                'result_to_view' => ['id' => 'id', 'locale' => 'locale'],
                            ],
                            'security_context' => 'sulu.global.snippets',
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_core')) {
            $container->prependExtensionConfig(
                'sulu_core',
                [
                    'content' => [
                        'structure' => [
                            'required_properties' => [
                                'snippet' => ['title'],
                            ],
                            'paths' => [
                                'snippet' => [
                                    'path' => '%kernel.project_dir%/config/templates/snippets',
                                    'type' => 'snippet',
                                ],
                            ],
                            'default_type' => [
                                'snippet' => 'default',
                            ],
                            'type_map' => ['snippet' => SnippetBridge::class],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_page')) {
            $container->prependExtensionConfig(
                'sulu_page',
                [
                    'search' => [
                        'mapping' => [
                            SnippetDocument::class => ['index' => 'snippet'],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_document_manager')) {
            $container->prependExtensionConfig(
                'sulu_document_manager',
                [
                    'mapping' => [
                        'snippet' => ['class' => SnippetDocument::class, 'phpcr_type' => 'sulu:snippet', 'form_type' => SnippetType::class],
                    ],
                ]
            );
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array<string, class-string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter(
            'sulu_snippet.content-type.default_enabled',
            $config['types']['snippet']['default_enabled']
        );
        $container->setParameter(
            'sulu_snippet.twig.snippet.cache_lifetime',
            $config['twig']['snippet']['cache_lifetime']
        );

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('content.xml');
        $loader->load('snippet.xml');
        $loader->load('export.xml');
        $loader->load('import.xml');
        $loader->load('admin.xml');
        $loader->load('command.xml');

        if (\array_key_exists('SuluReferenceBundle', $bundles)) {
            $loader->load('services_reference.xml');
        }

        if (\array_key_exists('SuluTrashBundle', $bundles)) {
            $loader->load('services_trash.xml');
        }
    }
}
