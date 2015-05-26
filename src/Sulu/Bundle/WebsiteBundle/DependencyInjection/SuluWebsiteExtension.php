<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DependencyInjection;

use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluWebsiteExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(
            'sulu_website.preview_defaults.analytics_key',
            $config['preview_defaults']['analytics_key']
        );
        $container->setParameter(
            'sulu_website.sitemap.multi_webspace',
            $config['sitemap']['multi_webspace']
        );

        $container->setParameter(
            'sulu_website.navigation.cache.lifetime',
            $config['twig']['navigation']['cache_lifetime']
        );
        $container->setParameter(
            'sulu_website.content.cache.lifetime',
            $config['twig']['content']['cache_lifetime']
        );
        $container->setParameter(
            'sulu_website.sitemap.cache.lifetime',
            $config['twig']['content']['cache_lifetime']
        );

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        if (SuluKernel::CONTEXT_WEBSITE == $container->getParameter('sulu.context')) {
            $loader->load('website.xml');
        }
    }
}
