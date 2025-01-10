<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddAdminPass;
use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\MetadataProviderNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataVisitorInterface;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RemoveForeignContextServicesPass;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluAdminExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('framework')) {
            $publicDir = 'public';

            $composerFile = $container->getParameter('kernel.project_dir') . '/composer.json';
            if (\file_exists($composerFile)) {
                $composerConfig = \json_decode(\file_get_contents($composerFile), true);
                $publicDir = $composerConfig['extra']['public-dir'] ?? $publicDir;
            }

            $container->prependExtensionConfig(
                'framework',
                [
                    'assets' => [
                        'packages' => [
                            'sulu_admin' => [
                                'json_manifest_path' => '%kernel.project_dir%/' . $publicDir . '/build/admin/manifest.json',
                            ],
                        ],
                    ],
                    'cache' => [
                        'pools' => [
                            'sulu_admin.collaboration_cache' => [
                                'adapter' => 'cache.app',
                            ],
                        ],
                    ],
                    'translator' => [
                        'enabled' => true,
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
                                'name' => 'sulu_admin',
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Component\SmartContent\Configuration',
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
                            MetadataNotFoundException::class => 404,
                            MetadataProviderNotFoundException::class => 404,
                            MissingArgumentException::class => 400,
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
                        '(.+\.)?c?get_.*',
                        'sulu_admin.metadata',
                    ],
                ]
            );
        }

        $container->prependExtensionConfig(
            'sulu_admin',
            [
                'forms' => [
                    'directories' => [
                        __DIR__ . '/../Resources/config/forms',
                    ],
                ],
                'resources' => [
                    'collaborations' => [
                        'routes' => [
                            'detail' => 'sulu_admin.put_collaborations',
                        ],
                    ],
                    'localizations' => [
                        'routes' => [
                            'list' => 'sulu_core.get_localizations',
                        ],
                    ],
                    'teasers' => [
                        'routes' => [
                            'list' => 'sulu_page.get_teasers',
                        ],
                    ],
                ],
            ]
        );
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter($this->getAlias() . '.name', $config['name']);
        $container->setParameter($this->getAlias() . '.email', $config['email']);
        $container->setParameter($this->getAlias() . '.user_data_service', $config['user_data_service']);
        $container->setParameter($this->getAlias() . '.resources', $config['resources']);
        $container->setParameter($this->getAlias() . '.collaboration_enabled', $config['collaboration']['enabled']);
        $container->setParameter($this->getAlias() . '.collaboration_interval', $config['collaboration']['interval']);
        $container->setParameter($this->getAlias() . '.collaboration_threshold', $config['collaboration']['threshold']);

        $container->setParameter($this->getAlias() . '.forms.directories', $config['forms']['directories'] ?? []);
        $container->setParameter($this->getAlias() . '.lists.directories', $config['lists']['directories'] ?? []);
        $container->setParameter($this->getAlias() . '.icon_sets', $config['icon_sets'] ?? []);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->registerForAutoconfiguration(Admin::class)
            ->addTag(AddAdminPass::ADMIN_TAG)
            ->addTag(RemoveForeignContextServicesPass::SULU_CONTEXT_TAG, ['context' => SuluKernel::CONTEXT_ADMIN]);

        $container->registerForAutoconfiguration(ListMetadataLoaderInterface::class)
            ->addTag('sulu_admin.list_metadata_loader');

        $container->registerForAutoconfiguration(ListMetadataVisitorInterface::class)
            ->addTag('sulu_admin.list_metadata_visitor');

        $container->registerForAutoconfiguration(FormMetadataVisitorInterface::class)
            ->addTag('sulu_admin.form_metadata_visitor');

        $container->registerForAutoconfiguration(TypedFormMetadataVisitorInterface::class)
            ->addTag('sulu_admin.typed_form_metadata_visitor');

        $this->loadFieldTypeOptions(
            $config['field_type_options'],
            $container->getDefinition('sulu_admin.field_type_option_registry')
        );

        $this->registerPropertyMetadataMappers(
            $config['field_type_options'],
            $container
        );
    }

    public function loadFieldTypeOptions(
        array $fieldTypeOptionsConfig,
        Definition $fieldTypeOptionRegistry
    ) {
        foreach ($fieldTypeOptionsConfig as $baseFieldType => $baseFieldTypeConfig) {
            foreach ($baseFieldTypeConfig as $fieldTypeName => $fieldTypeConfig) {
                $fieldTypeOptionRegistry->addMethodCall('add', [$fieldTypeName, $baseFieldType, $fieldTypeConfig]);
            }
        }
    }

    private function registerPropertyMetadataMappers(array $fieldTypeOptionsConfig, ContainerBuilder $container)
    {
        foreach ($fieldTypeOptionsConfig as $baseFieldType => $baseFieldTypeConfig) {
            if (!\in_array($baseFieldType, ['selection', 'single_selection'], true)) {
                continue;
            }

            $definition = $container->getDefinition('sulu_admin.property_metadata_mapper.' . $baseFieldType);

            foreach ($baseFieldTypeConfig as $fieldTypeName => $fieldTypeConfig) {
                $definition->addTag('sulu_admin.property_metadata_mapper', [
                    'type' => $fieldTypeName,
                ]);
            }
        }
    }

    /**
     * @param mixed[] $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }
}
