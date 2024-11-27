<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use Contao\ImagineSvg\Imagine as SvgImagine;
use FFMpeg\FFMpeg;
use Imagine\Vips\Imagine as VipsImagine;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatOptionsMissingParameterException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\MediaPropertiesProviderInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemStorage;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\SearchBundle\SuluSearchBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Process\ExecutableFinder;

class SuluMediaExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    /**
     * @var ExecutableFinder
     */
    private $executableFinder;

    public function __construct(?ExecutableFinder $executableFinder = null)
    {
        $this->executableFinder = $executableFinder ?: new ExecutableFinder();
    }

    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_search')) {
            $container->prependExtensionConfig(
                'sulu_search',
                [
                    'indexes' => [
                        MediaInterface::RESOURCE_KEY => [
                            'name' => 'sulu_media.media',
                            'icon' => 'su-image',
                            'view' => [
                                'name' => MediaAdmin::EDIT_FORM_VIEW,
                                'result_to_view' => [
                                    'id' => 'id',
                                    'locale' => 'locale',
                                ],
                            ],
                            'security_context' => 'sulu.media.collections',
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_media')) {
            $container->prependExtensionConfig(
                'sulu_media',
                [
                    'system_collections' => [
                        'sulu_media' => [
                            'meta_title' => ['en' => 'Sulu media', 'de' => 'Sulu Medien'],
                            'collections' => [
                                'preview_image' => [
                                    'meta_title' => ['en' => 'Preview images', 'de' => 'Vorschaubilder'],
                                ],
                            ],
                        ],
                    ],
                    'image_format_files' => [
                        __DIR__ . '/../Resources/config/image-formats.xml',
                        '%kernel.project_dir%/config/image-formats.xml',
                    ],
                    'search' => ['enabled' => $container->hasExtension('massive_search')],
                ]
            );
        }

        if ($container->hasExtension('fos_js_routing')) {
            $container->prependExtensionConfig(
                'fos_js_routing',
                [
                    'routes_to_expose' => [
                        'sulu_media.redirect',
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
                            MediaNotFoundException::class => 404,
                            FileVersionNotFoundException::class => 404,
                            FormatNotFoundException::class => 404,
                            FormatOptionsMissingParameterException::class => 400,
                            MediaException::class => 400,
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
                        MediaInterface::RESOURCE_KEY => [
                            'routes' => [
                                'list' => 'sulu_media.cget_media',
                                'detail' => 'sulu_media.get_media',
                            ],
                            'security_context' => 'sulu.media.collections',
                            'security_class' => Collection::class,
                        ],
                        'media_preview' => [
                            'routes' => [
                                'detail' => 'sulu_media.post_media_preview',
                            ],
                        ],
                        'media_formats' => [
                            'routes' => [
                                'list' => 'sulu_media.get_media_formats',
                                'detail' => 'sulu_media.put_media_format',
                            ],
                        ],
                        'media_versions' => [
                            'routes' => [
                                'detail' => 'sulu_media.delete_media_version',
                            ],
                        ],
                        CollectionInterface::RESOURCE_KEY => [
                            'routes' => [
                                'list' => 'sulu_media.get_collections',
                                'detail' => 'sulu_media.get_collection',
                            ],
                        ],
                        'formats' => [
                            'routes' => [
                                'list' => 'sulu_media.get_formats',
                                'detail' => 'sulu_media.get_format',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'single_selection' => [
                            'single_collection_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'collections',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'column_list',
                                        'list_key' => 'collections',
                                        'display_properties' => ['title'],
                                        'icon' => 'su-folder',
                                        'empty_text' => 'sulu_media.no_collection_selected',
                                        'overlay_title' => 'sulu_media.single_collection_selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                        'selection' => [
                            'collection_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'collections',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'column_list',
                                        'list_key' => 'collections',
                                        'display_properties' => ['title'],
                                        'icon' => 'su-folder',
                                        'label' => 'sulu_media.collection_selection_label',
                                        'overlay_title' => 'sulu_media.collection_selection_overlay_title',
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
        /** @var array<string, class-string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // collection-class
        $container->setParameter('sulu.model.collection.class', Collection::class);

        // image-formats
        $container->setParameter('sulu_media.image_format_files', $config['image_format_files']);

        // system collections
        $container->setParameter('sulu_media.system_collections', $config['system_collections']);

        // routing paths
        $container->setParameter('sulu_media.format_cache.media_proxy_path', $config['routing']['media_proxy_path']);
        $container->setParameter(
            'sulu_media.media_manager.media_download_path',
            $config['routing']['media_download_path']
        );
        $container->setParameter(
            'sulu_media.media_manager.media_download_path_admin',
            $config['routing']['media_download_path_admin']
        );

        // format manager
        $container->setParameter(
            'sulu_media.format_manager.response_headers',
            $config['format_manager']['response_headers']
        );
        $container->setParameter(
            'sulu_media.format_manager.default_imagine_options',
            \array_merge([
                'jpeg_quality' => 100,
                'webp_quality' => 100,
                'avif_quality' => 100,
            ], $config['format_manager']['default_imagine_options'])
        );

        // format cache
        $container->setParameter('sulu_media.format_cache.path', $config['format_cache']['path']);
        $container->setParameter('sulu_media.format_cache.save_image', $config['format_cache']['save_image']);
        $container->setParameter('sulu_media.format_cache.segments', $config['format_cache']['segments']);

        // converter
        $ghostScriptPath = $container->resolveEnvPlaceholders($config['ghost_script']['path'], true);

        $container->setParameter('sulu_media.ghost_script.path', $ghostScriptPath);

        // storage
        $container->setParameter(
            'sulu_media.media.blocked_file_types',
            $this->getBlockedFileTypes($config)
        );
        $this->configureStorage($config, $container);

        // collections
        $container->setParameter('sulu_media.collection.type.default', ['id' => 1]);

        $container->setParameter('sulu_media.collection.previews.format', 'sulu-50x50');

        // media
        $container->setParameter('sulu_media.media.types', $config['format_manager']['types']);

        // search
        $container->setParameter('sulu_media.search.default_image_format', $config['search']['default_image_format']);

        // disposition type
        $container->setParameter('sulu_media.disposition_type.default', $config['disposition_type']['default']);
        $container->setParameter(
            'sulu_media.disposition_type.mime_types_inline',
            $config['disposition_type']['mime_types_inline']
        );
        $container->setParameter(
            'sulu_media.disposition_type.mime_types_attachment',
            $config['disposition_type']['mime_types_attachment']
        );

        // dropzone
        $container->setParameter(
            'sulu_media.upload.max_filesize',
            $config['upload']['max_filesize']
        );

        /* @deprecated This parameter is duplicated and should be removed use sulu_media.upload.max_filesize instead. */
        $container->setParameter(
            'sulu_media.media.max_file_size',
            $config['upload']['max_filesize'] . 'MB'
        );

        // Adobe creative sdk
        $container->setParameter(
            'sulu_media.adobe_creative_key',
            $config['adobe_creative_key']
        );

        // load services
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('command.xml');

        if (\class_exists(SvgImagine::class)) {
            $loader->load('services_imagine_svg.xml');
        }

        $hasVipsAdapter = false;
        if (\class_exists(VipsImagine::class)
            && (
                \extension_loaded('vips') // deprecate vips 1.0 way use ffi instead
                || (
                    \extension_loaded('ffi')
                    && '1' === \ini_get('ffi.enable') // preload is not yet supported by vips see https://github.com/libvips/php-vips
                )
            )
        ) {
            $loader->load('services_imagine_vips.xml');
            $hasVipsAdapter = true;
        }

        $adapter = $container->resolveEnvPlaceholders($config['adapter'], true);
        if ('auto' === $adapter) {
            $adapter = 'gd';
            if ($hasVipsAdapter) {
                $adapter = 'vips';
            } elseif (\class_exists(\Imagick::class)) {
                $adapter = 'imagick';
            }

            $container->setAlias('sulu_media.adapter', 'sulu_media.adapter.' . $adapter);
        } else {
            // set used adapter for imagine
            $container->setAlias('sulu_media.adapter', 'sulu_media.adapter.' . $adapter);
        }

        // enable search
        if (true === $config['search']['enabled']) {
            if (!\class_exists(SuluSearchBundle::class)) {
                throw new \InvalidArgumentException(
                    'You have enabled sulu search integration for the SuluMediaBundle, but the SuluSearchBundle must be installed'
                );
            }

            $loader->load('search.xml');
        }

        if (\array_key_exists('SuluAudienceTargetingBundle', $bundles)) {
            $loader->load('audience_targeting.xml');
        }

        if (\array_key_exists('SuluTrashBundle', $bundles)) {
            $loader->load('services_trash.xml');
        }

        if (\array_key_exists('SuluContentBundle', $bundles)) {
            $loader->load('services_content.xml');
        }

        $ffmpegBinary = $container->resolveEnvPlaceholders($config['ffmpeg']['ffmpeg_binary'] ?? null, true);
        $ffprobeBinary = $container->resolveEnvPlaceholders($config['ffmpeg']['ffprobe_binary'] ?? null, true);

        if ($ffmpegBinary || $ffprobeBinary) {
            if (!\class_exists(FFMpeg::class)) {
                throw new InvalidConfigurationException(
                    'The "php-ffmpeg/php-ffmpeg" need to be installed to use ffmpeg.'
                );
            }

            $container->setParameter('sulu_media.ffmpeg.binary', $config['ffmpeg']['ffmpeg_binary']);
            $container->setParameter('sulu_media.ffprobe.binary', $config['ffmpeg']['ffprobe_binary']);
            $container->setParameter('sulu_media.ffmpeg.binary_timeout', $config['ffmpeg']['binary_timeout']);
            $container->setParameter('sulu_media.ffmpeg.threads_count', $config['ffmpeg']['threads_count']);

            $loader->load('ffmpeg.xml');
        }

        $mimeTypes = $config['format_manager']['mime_types'];
        if (0 === \count($mimeTypes)) {
            $mimeTypes = $this->getSupportedMimeTypes($ghostScriptPath, $ffmpegBinary, $ffprobeBinary);
        }
        $container->setParameter('sulu_media.format_manager.mime_types', $mimeTypes);

        $this->configurePersistence($config['objects'], $container);
        $container->addAliases(
            [
                MediaRepositoryInterface::class => 'sulu.repository.media',
            ]
        );

        $this->configureFileValidator($config, $container);

        $container->registerForAutoconfiguration(MediaPropertiesProviderInterface::class)
            ->addTag('sulu_media.media_properties_provider');
    }

    private function configureStorage(array $config, ContainerBuilder $container): void
    {
        $flySystemService = $container->get($config['storage']);
        $flySystemAdapter = $container->get('flysystem.adapter.'.$config['storage']);

        $container->set('sulu_media.storage', new Definition(
            FlysystemStorage::class,
            [
                $flySystemService,
                $flySystemAdapter,
                $config['storage']['segments'],
            ]
            ));


        $storage = $container->resolveEnvPlaceholders($config['storage'], true);
        $container->setParameter('sulu_media.media.storage', $storage);

        foreach ($config['storages'] as $storageKey => $storageConfig) {
            foreach ($storageConfig as $key => $value) {
                if ($storageKey === $storage) {
                    $container->setParameter('sulu_media.media.storage.' . $storageKey . '.' . $key, $value);
                } else {
                    // Resolve unused ENV Variables of other Adapter
                    $container->resolveEnvPlaceholders($value, true);
                }
            }
        }

        $container->setAlias('sulu_media.storage', 'sulu_media.storage.' . $storage)->setPublic(true);
        $container->setAlias(StorageInterface::class, 'sulu_media.storage')->setPublic(true);
    }

    private function getSupportedMimeTypes($ghostScriptPath, $ffmpegBinary, $ffprobeBinary)
    {
        $mimeTypes = ['image/*'];

        if ($ffmpegBinary
            && $ffprobeBinary
            && $this->checkCommandAvailability($ffmpegBinary)
            && $this->checkCommandAvailability($ffprobeBinary)
        ) {
            $mimeTypes[] = 'video/*';
        }

        if ($ghostScriptPath && $this->checkCommandAvailability($ghostScriptPath)) {
            $mimeTypes[] = 'application/pdf';
        }

        return $mimeTypes;
    }

    private function checkCommandAvailability($command)
    {
        return null !== $this->executableFinder->find($command) || @\is_executable($command);
    }

    private function configureFileValidator(array $config, ContainerBuilder $container)
    {
        $definition = $container->getDefinition('sulu_media.file_validator');
        $definition->addMethodCall('setMaxFileSize', [$config['upload']['max_filesize'] . 'MB']);
        $definition->addMethodCall('setBlockedMimeTypes', [$this->getBlockedFileTypes($config)]);
    }

    private function getBlockedFileTypes(array $config): array
    {
        $blockedFileTypes = $config['upload']['blocked_file_types'];
        if (0 === \count($blockedFileTypes)) {
            return $config['format_manager']['blocked_file_types'];
        }

        return $blockedFileTypes;
    }
}
