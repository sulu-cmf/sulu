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

use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Generator\MissingDomainPartException;
use Sulu\Component\CustomUrl\Manager\RouteNotRemovableException;
use Sulu\Component\CustomUrl\Manager\TitleAlreadyExistsException;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
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
    /**
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('admin.xml');
        $loader->load('document.xml');
        $loader->load('routing.xml');
        $loader->load('event_listener.xml');

        if (\array_key_exists('SuluTrashBundle', $bundles)) {
            $loader->load('services_trash.xml');
        }
    }

    /**
     * @return void
     */
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
                        CustomUrlDocument::RESOURCE_KEY => [
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

        if ($container->hasExtension('sulu_document_manager')) {
            $container->prependExtensionConfig(
                'sulu_document_manager',
                [
                    'mapping' => [
                        'custom_url' => [
                            'class' => CustomUrlDocument::class,
                            'phpcr_type' => 'sulu:custom_url',
                            'mapping' => [
                                'published' => ['property' => 'published'],
                                'baseDomain' => ['property' => 'baseDomain'],
                                'domainParts' => ['property' => 'domainParts', 'type' => 'json_array'],
                                'canonical' => ['property' => 'canonical'],
                                'redirect' => ['property' => 'redirect'],
                                'targetLocale' => ['property' => 'targetLocale'],
                            ],
                        ],
                        'custom_url_route' => [
                            'class' => RouteDocument::class,
                            'phpcr_type' => 'sulu:custom_url_route',
                            'mapping' => [
                                'locale' => ['property' => 'locale'],
                            ],
                        ],
                    ],
                    'path_segments' => [
                        'custom_urls' => 'custom-urls',
                        'custom_urls_items' => 'items',
                        'custom_urls_routes' => 'routes',
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
                                'name' => 'sulu_custom_url',
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Component\CustomUrl',
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
                            DocumentNotFoundException::class => 404,
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
