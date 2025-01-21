<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection;

use Gedmo\Exception;
use Oro\ORM\Query\AST\Functions\Cast;
use Oro\ORM\Query\AST\Functions\String\GroupConcat;
use Sulu\Bundle\CoreBundle\CommandOptional\SuluBuildCommand;
use Sulu\Component\Content\Types\Block\BlockVisitorInterface;
use Sulu\Component\Rest\Csv\ObjectNotSupportedException;
use Sulu\Component\Rest\Exception\InsufficientDescendantPermissionsException;
use Sulu\Component\Rest\Exception\InvalidHashException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\ReferencingResourcesFoundExceptionInterface;
use Sulu\Component\Rest\Exception\RemoveDependantResourcesFoundExceptionInterface;
use Sulu\Component\Rest\ListBuilder\Filter\InvalidFilterTypeOptionsException;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluCoreExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        // process the configuration of SuluCoreExtension
        $configs = $container->getExtensionConfig($this->getAlias());
        $parameterBag = $container->getParameterBag();
        $configs = $parameterBag->resolveValue($configs);
        $config = $this->processConfiguration(new Configuration(), $configs);

        if (isset($config['phpcr']) && $container->hasExtension('doctrine_phpcr')) {
            $phpcrConfig = $config['phpcr'];

            // TODO: Workaround for issue: https://github.com/doctrine/DoctrinePHPCRBundle/issues/178
            if (!isset($phpcrConfig['backend']['check_login_on_server'])) {
                $phpcrConfig['backend']['check_login_on_server'] = false;
            }

            $container->prependExtensionConfig(
                'doctrine_phpcr',
                [
                    'session' => $phpcrConfig,
                ]
            );
        }

        $templatesPath = __DIR__ . '/../Content/templates';
        $container->prependExtensionConfig(
            'sulu_core',
            [
                'content' => [
                    'structure' => [
                        'paths' => [
                            'sulu' => [
                                'path' => $templatesPath,
                                'type' => 'page',
                            ],
                        ],
                    ],
                ],
            ]
        );

        if ($container->hasExtension('massive_build')) {
            $container->prependExtensionConfig('massive_build', [
                'command_class' => SuluBuildCommand::class,
            ]);
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'enabled' => true,
                        'map_exception_codes' => true,
                        'codes' => [
                            MissingParameterException::class => 400,
                            InvalidHashException::class => 409,
                            ObjectNotSupportedException::class => 406,
                            InvalidFilterTypeOptionsException::class => 400,
                            RemoveDependantResourcesFoundExceptionInterface::class => 409,
                            InsufficientDescendantPermissionsException::class => 403,
                            ReferencingResourcesFoundExceptionInterface::class => 409,
                        ],
                        'exception_listener' => false,
                        'serialize_exceptions' => false,
                        'flatten_exception_format' => 'legacy',
                    ],
                    'serializer' => [
                        'serialize_null' => true,
                    ],
                    'body_listener' => [
                        'enabled' => true,
                    ],
                    'routing_loader' => false,
                    'view' => [
                        'formats' => [
                            'json' => true,
                            'csv' => true,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('twig') && \class_exists(ExceptionController::class)) {
            // Disable the deprecated exception_controller to support the newer fos_rest bundle
            $container->prependExtensionConfig('twig', [
                'exception_controller' => null,
            ]);
        }

        if ($container->hasExtension('handcraftedinthealps_rest_routing')) {
            $container->prependExtensionConfig(
                'handcraftedinthealps_rest_routing',
                [
                    'routing_loader' => [
                        'default_format' => 'json',
                        'formats' => [
                            'json' => true,
                            'csv' => true,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig(
                'jms_serializer',
                [
                    'property_naming' => [
                        'id' => 'jms_serializer.identical_property_naming_strategy',
                    ],
                ]
            );
        }

        if ($container->hasExtension('doctrine')) {
            // depending on the gedmo version it is in gedmo lib or src folder
            // we use the autoloader to detect the correct directory
            // see also: https://github.com/sulu/sulu/pull/5753
            $reflection = new \ReflectionClass(Exception::class);
            $gedmoDirectory = \dirname($reflection->getFileName());

            $container->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'gedmo_tree' => [
                                'type' => 'xml',
                                'prefix' => 'Gedmo\\Tree\\Entity',
                                'dir' => $gedmoDirectory . '/Tree/Entity',
                                'alias' => 'GedmoTree',
                                'is_bundle' => false,
                            ],
                        ],
                        'dql' => [
                            'string_functions' => [
                                'group_concat' => GroupConcat::class,
                                'CAST' => Cast::class,
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('stof_doctrine_extensions')) {
            $container->prependExtensionConfig('stof_doctrine_extensions', ['orm' => ['default' => ['tree' => true]]]);
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig(
                'jms_serializer',
                [
                    'metadata' => [
                        'debug' => '%kernel.debug%',
                    ],
                    'handlers' => [
                        'datetime' => [
                            'default_format' => 'Y-m-d\\TH:i:s',
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('massive_build')) {
            $container->prependExtensionConfig(
                'massive_build',
                [
                    'targets' => [
                        'prod' => [
                            'dependencies' => [
                                'database' => [],
                                'phpcr' => [],
                                'fixtures' => [],
                                'phpcr_migrations' => [],
                                'system_collections' => [],
                                'security' => [],
                            ],
                        ],
                        'dev' => [
                            'dependencies' => [
                                'database' => [],
                                'fixtures' => [],
                                'phpcr' => [],
                                'user' => [],
                                'phpcr_migrations' => [],
                                'system_collections' => [],
                                'security' => [],
                                'search_init' => [],
                            ],
                        ],
                        'maintain' => [
                            'dependencies' => [
                                'node_order' => [],
                                'search_index' => [],
                                'phpcr_migrations' => [],
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('framework')) {
            $container->prependExtensionConfig(
                'framework',
                [
                    'cache' => [
                        'directory' => '%sulu.common_cache_dir%/pools',
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

        foreach ($config['locales'] as $locale => $localeName) {
            if (\strtolower($locale) !== $locale) {
                throw new InvalidConfigurationException('Invalid locale in configuration: ' . $locale);
            }
        }

        foreach ($config['translations'] as $translation) {
            if (\strtolower($translation) !== $translation) {
                throw new InvalidConfigurationException('Invalid translation in configuration: ' . $translation);
            }
        }

        if (\strtolower($config['fallback_locale']) !== $config['fallback_locale']) {
            throw new InvalidConfigurationException(
                'Invalid fallback_locale in configuration: ' . $config['fallback_locale']
            );
        }

        $container->setParameter('sulu_core.locales', \array_unique(\array_keys($config['locales'])));
        $container->setParameter('sulu_core.translated_locales', $config['locales']);
        $container->setParameter('sulu_core.translations', \array_unique($config['translations']));
        $container->setParameter('sulu_core.fallback_locale', $config['fallback_locale']);

        $container->setParameter('sulu.cache_dir', $config['cache_dir']);

        $proxyCacheDirectory = $container->getParameterBag()->resolveValue(
            $container->getParameter('sulu.cache_dir') . '/proxies'
        );

        if (!\is_dir($proxyCacheDirectory)) {
            \mkdir($proxyCacheDirectory, 0777, true);
        }

        $container->setParameter('sulu_core.proxy_cache_dir', $proxyCacheDirectory);

        // Content
        if (isset($config['content'])) {
            $this->initContent($config['content'], $container, $loader);
        }

        // Webspace
        if (isset($config['webspace'])) {
            $this->initWebspace($config['webspace'], $container, $loader);
        }

        // Cache
        if (isset($config['cache'])) {
            $this->initCache($config['cache'], $container, $loader);
        }

        // Default Fields
        if (isset($config['fields_defaults'])) {
            $this->initFields($config['fields_defaults'], $container);
        }

        $this->initListBuilder($container, $loader);

        $loader->load('expression_language.xml');
        $loader->load('phpcr.xml');
        $loader->load('rest.xml');
        $loader->load('build.xml');
        $loader->load('localization.xml');
        $loader->load('serializer.xml');
        $loader->load('request_analyzer.xml');
        $loader->load('doctrine.xml');

        $container->registerForAutoconfiguration(BlockVisitorInterface::class)
            ->addTag('sulu_content.block_visitor');
    }

    /**
     * @return void
     */
    private function initWebspace(array $webspaceConfig, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $container->setParameter('sulu_core.webspace.config_dir', $webspaceConfig['config_dir']);

        $loader->load('webspace.xml');
    }

    /**
     * @return void
     */
    private function initFields(array $fieldsConfig, ContainerBuilder $container)
    {
        $container->setParameter('sulu.fields_defaults.translations', $fieldsConfig['translations']);
        $container->setParameter('sulu.fields_defaults.widths', $fieldsConfig['widths']);
    }

    /**
     * @return void
     */
    private function initContent(array $contentConfig, ContainerBuilder $container, XmlFileLoader $loader)
    {
        // Default Language
        $container->setParameter('sulu.content.language.namespace', $contentConfig['language']['namespace']);
        $container->setParameter('sulu.content.language.default', $contentConfig['language']['default']);

        // Node names
        $container->setParameter('sulu.content.node_names.base', $contentConfig['node_names']['base']);
        $container->setParameter('sulu.content.node_names.content', $contentConfig['node_names']['content']);
        $container->setParameter('sulu.content.node_names.route', $contentConfig['node_names']['route']);
        $container->setParameter('sulu.content.node_names.snippet', $contentConfig['node_names']['snippet']);

        // Default template
        $container->setParameter(
            'sulu.content.structure.default_types',
            $contentConfig['structure']['default_type']
        );

        foreach ($contentConfig['structure']['default_type'] as $type => $default) {
            $container->setParameter(
                'sulu.content.structure.default_type.' . $type,
                $default
            );
        }

        $container->setParameter(
            'sulu.content.structure.required_properties',
            $contentConfig['structure']['required_properties']
        );
        $container->setParameter(
            'sulu.content.structure.required_tags',
            $contentConfig['structure']['required_tags']
        );
        $container->setParameter(
            'sulu.content.internal_prefix',
            $contentConfig['internal_prefix']
        );
        $container->setParameter(
            'sulu.content.structure.type_map',
            $contentConfig['structure']['type_map']
        );

        // Template
        $paths = [];
        foreach ($contentConfig['structure']['paths'] as $pathConfig) {
            $pathType = $pathConfig['type'];
            if (!isset($paths[$pathType])) {
                $paths[$pathType] = [];
            }
            $paths[$pathType][] = $pathConfig;
        }

        $container->setParameter('sulu.content.structure.paths', $paths);

        $loader->load('content.xml');
    }

    /**
     * @return void
     */
    private function initCache(array $cache, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $container->setParameter('sulu_core.cache.memoize.default_lifetime', $cache['memoize']['default_lifetime']);

        $loader->load('cache.xml');
    }

    /**
     * Initializes list builder.
     *
     * @return void
     */
    private function initListBuilder(ContainerBuilder $container, XmlFileLoader $loader)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (\array_key_exists('SuluAdminBundle', $bundles)) {
            $loader->load('list_builder.xml');
        }
    }
}
