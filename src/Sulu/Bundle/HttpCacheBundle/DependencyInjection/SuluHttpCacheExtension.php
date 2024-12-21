<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Container extension for sulu-http-cache bundle.
 */
class SuluHttpCacheExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $configuration = $this->getConfiguration($configs, $container);
        $resolvingBag = $container->getParameterBag();
        $configs = $resolvingBag->resolveValue($configs);
        $config = $this->processConfiguration($configuration, $configs);

        $fosHttpCacheConfig = [
            'debug' => [
                'enabled' => $config['debug']['enabled'],
            ],
        ];

        if (!$container->hasExtension('sensio_framework_extra')
            && \version_compare(\Composer\InstalledVersions::getVersion('friendsofsymfony/http-cache-bundle') ?? '999.999.999', '3.0.0', '<')
        ) {
            $fosHttpCacheConfig['tags'] = [
                'annotations' => [
                    'enabled' => false,
                ],
            ];
        }

        if (\array_key_exists('noop', $config['proxy_client'])) {
            $fosHttpCacheConfig['proxy_client']['noop'] = $config['proxy_client']['noop'];
        }

        if ($config['proxy_client']['symfony']['enabled']) {
            $symfonyProxyClient = $config['proxy_client']['symfony'];
            $fosHttpCacheConfig['proxy_client']['symfony']['http']['servers'] =
                \count($symfonyProxyClient['servers']) ? $symfonyProxyClient['servers'] : ['127.0.0.1'];
        }

        if ($config['proxy_client']['varnish']['enabled']) {
            $varnishProxyClient = $config['proxy_client']['varnish'];

            $fosHttpCacheConfig['proxy_client']['varnish']['http']['servers'] =
                \count($varnishProxyClient['servers']) ? $varnishProxyClient['servers'] : ['127.0.0.1'];

            $fosHttpCacheConfig['proxy_client']['varnish']['tag_mode'] = $varnishProxyClient['tag_mode'];
            if (\array_key_exists('tags_header', $varnishProxyClient)) {
                $fosHttpCacheConfig['proxy_client']['varnish']['tags_header'] = $varnishProxyClient['tags_header'];
            }
        }

        if (\array_key_exists('proxy_client', $fosHttpCacheConfig)) {
            $fosHttpCacheConfig['tags']['enabled'] = $config['tags']['enabled'];
        }

        $container->prependExtensionConfig('fos_http_cache', $fosHttpCacheConfig);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('event-subscribers.xml');
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_http_cache.cache.max_age', $config['cache']['max_age']);
        $container->setParameter('sulu_http_cache.cache.shared_max_age', $config['cache']['shared_max_age']);
        $container->setParameter('sulu_http_cache.tags.enabled', $config['tags']['enabled']);

        $proxyClientAvailable = false;
        if (\array_key_exists('proxy_client', $config)) {
            foreach ($config['proxy_client'] as $proxyClient) {
                if ((\is_bool($proxyClient) && $proxyClient) || true === $proxyClient['enabled']) {
                    $proxyClientAvailable = true;
                    break;
                }
            }
        }

        $loader->load('cache-lifetime-enhancer.xml');

        if ($proxyClientAvailable) {
            $loader->load('cache-manager.xml');

            if (true === $config['tags']['enabled']) {
                $loader->load('tags.xml');
            }
        }
    }
}
