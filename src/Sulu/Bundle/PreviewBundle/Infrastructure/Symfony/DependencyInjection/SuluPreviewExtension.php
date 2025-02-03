<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Extends the container and initializes the preview budle.
 */
class SuluPreviewExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_preview.mode', $config['mode']);
        $container->setParameter('sulu_preview.delay', $config['delay']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config'));
        $loader->load('services.xml');

        $this->configurePersistence($config['objects'], $container);
        $container->addAliases(
            [
                PreviewLinkRepositoryInterface::class => 'sulu_preview.preview_link_repository',
            ]
        );
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->prependExtensionConfig('framework',
            [
                'cache' => [
                    'pools' => [
                        'sulu_preview.preview.cache' => [
                            'adapter' => $config['cache_adapter'],
                        ],
                    ],
                ],
            ]
        );

        if ($container->hasExtension('doctrine')) {
            $container->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'SuluPreviewBundle' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../../../Resources/config/doctrine',
                                'prefix' => 'Sulu\Bundle\PreviewBundle\Domain\Model',
                                'alias' => 'SuluPreviewBundle',
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
                    'resources' => [
                        PreviewLinkInterface::RESOURCE_KEY => [
                            'routes' => [
                                'detail' => 'sulu_preview.get_preview-link',
                            ],
                        ],
                    ],
                ]
            );
        }
    }
}
