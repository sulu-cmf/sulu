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

use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const STORAGE_GOOGLE_CLOUD = 'google_cloud';

    public const STORAGE_S3 = 's3';

    public const STORAGE_AZURE_BLOB = 'azure_blob';

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('sulu_media');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->scalarNode('adobe_creative_key')->defaultNull()->end()
            ->scalarNode('adapter')
                ->defaultValue('auto')
            ->end()
            ->arrayNode('image_format_files')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('system_collections')
                ->useAttributeAsKey('key')
                ->prototype('array')
                    ->children()
                        ->arrayNode('meta_title')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('collections')
                            ->useAttributeAsKey('key')
                                ->prototype('array')
                                ->children()
                                    ->arrayNode('meta_title')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('search')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('default_image_format')->defaultValue('sulu-100x100')->end()
                    ->booleanNode('enabled')->info(
                        'Enable integration with SuluMediaBundle'
                    )->defaultValue(false)->end()
                ->end()
            ->end()
            ->arrayNode('ghost_script')
                ->addDefaultsIfNotSet()
                ->children()
                     ->scalarNode('path')->defaultValue('gs')->end()
                ->end()
            ->end()
            ->arrayNode('upload')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('max_filesize')
                        ->defaultValue(256)
                        ->min(0)
                    ->end()
                    ->arrayNode('blocked_file_types')
                        ->prototype('scalar')->end()
                        ->defaultValue([])
                    ->end()
                ->end()
            ->end()
            ->arrayNode('format_manager')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('response_headers')
                        ->prototype('scalar')->end()->defaultValue([
                            'Expires' => '+1 month',
                            'Pragma' => 'public',
                            'Cache-Control' => 'public, immutable, max-age=31536000',
                        ])
                    ->end()
                    ->arrayNode('default_imagine_options')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('blocked_file_types')
                        ->setDeprecated(
                            'sulu/sulu',
                            '2.1.8',
                            'The configuration "sulu_media.format_manager.blocked_file_types" is deprecated. Use "sulu_media.upload.blocked_file_types" instead.'
                        )
                        ->prototype('scalar')->end()
                        ->defaultValue([])
                    ->end()
                    ->arrayNode('mime_types')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('types')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('type')->cannotBeEmpty()->isRequired()->end()
                                ->arrayNode('mimeTypes')->requiresAtLeastOneElement()
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()->defaultValue([
                            [
                                'type' => 'document',
                                'mimeTypes' => ['*'],
                            ],
                            [
                                'type' => 'image',
                                'mimeTypes' => ['image/*'],
                            ],
                            [
                                'type' => 'video',
                                'mimeTypes' => ['video/*'],
                            ],
                            [
                                'type' => 'audio',
                                'mimeTypes' => ['audio/*'],
                            ],
                        ])
                    ->end()
                ->end()
            ->end()
            ->arrayNode('format_cache')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('path')->defaultValue('%kernel.project_dir%/public/uploads/media')->end()
                    ->booleanNode('save_image')->defaultValue(true)->end()
                    ->scalarNode('segments')->defaultValue(10)->end()
                ->end()
            ->end()
            ->arrayNode('disposition_type')
                ->addDefaultsIfNotSet()
                ->children()
                    ->enumNode('default')->values(['inline', 'attachment'])->defaultValue('attachment')->end()
                    ->arrayNode('mime_types_inline')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('mime_types_attachment')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('routing')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('media_proxy_path')->defaultValue('/uploads/media/{slug}')->end()
                    ->scalarNode('media_download_path')->defaultValue('/media/{id}/download/{slug}')->end()
                    ->scalarNode('media_download_path_admin')->defaultValue('/admin/media/{id}/download/{slug}')->end()
                ->end()
            ->end()
            ->arrayNode('ffmpeg')
                ->children()
                    ->scalarNode('ffmpeg_binary')
                        ->isRequired()
                    ->end()
                    ->scalarNode('ffprobe_binary')
                        ->isRequired()
                    ->end()
                    ->scalarNode('binary_timeout')->defaultValue(60)->end()
                    ->scalarNode('threads_count')->defaultValue(4)->end()
                ->end()
            ->end();

        $this->addObjectsSection($rootNode);
        $this->addStorageSection($rootNode);

        return $treeBuilder;
    }

    private function addStorageSection(ArrayNodeDefinition $node)
    {
        $storages = ['local'];
        $storagesNode = $node->children()
            ->arrayNode('storages')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('local')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('path')->defaultValue('%kernel.project_dir%/var/uploads/media')->end()
                            ->scalarNode('segments')->defaultValue(10)->end()
                        ->end()
                    ->end();

        if (\class_exists(GoogleStorageAdapter::class)) {
            $storages[] = self::STORAGE_GOOGLE_CLOUD;

            $storagesNode
                ->arrayNode(self::STORAGE_GOOGLE_CLOUD)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('key_file_path')->isRequired()->end()
                        ->scalarNode('bucket_name')->isRequired()->end()
                        ->scalarNode('path_prefix')->defaultNull()->end()
                        ->scalarNode('segments')->defaultValue(10)->end()
                    ->end()
                ->end();
        }

        if (\class_exists(AwsS3Adapter::class)) {
            $storages[] = self::STORAGE_S3;
            $storagesNode
                ->arrayNode(self::STORAGE_S3)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('key')->isRequired()->end()
                        ->scalarNode('secret')->isRequired()->end()
                        ->scalarNode('region')->isRequired()->end()
                        ->scalarNode('bucket_name')->isRequired()->end()
                        ->scalarNode('path_prefix')->defaultNull()->end()
                        ->scalarNode('version')->defaultValue('latest')->end()
                        ->scalarNode('endpoint')->defaultNull()->end()
                        ->scalarNode('segments')->defaultValue(10)->end()
                        ->arrayNode('arguments')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end();
        }

        if (\class_exists(AzureBlobStorageAdapter::class)) {
            $storages[] = self::STORAGE_AZURE_BLOB;

            $storagesNode
                ->arrayNode(self::STORAGE_AZURE_BLOB)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('connection_string')->isRequired()->end()
                        ->scalarNode('container_name')->isRequired()->end()
                        ->scalarNode('path_prefix')->defaultNull()->end()
                        ->scalarNode('segments')->defaultValue(10)->end()
                    ->end()
                ->end();
        }

        $node->children()
                ->scalarNode('storage')->defaultValue('local')->end()
            ->end();
    }

    /**
     * Adds `objects` section.
     */
    private function addObjectsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('media')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue(Media::class)
                                ->end()
                                ->scalarNode('repository')
                                    ->defaultValue(MediaRepository::class)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
