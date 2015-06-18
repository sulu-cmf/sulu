<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluContentExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $extensions = $container->getExtensions();

        if (isset($extensions['sulu_core'])) {
            $prepend = array(
                'content' => array(
                    'structure' => array(
                        'paths' => array(
                            array(
                                'path' => __DIR__ . '/../Content/templates',
                                'type' => 'page',
                            ),
                        ),
                    ),
                ),
            );

            $container->prependExtensionConfig('sulu_core', $prepend);
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig('jms_serializer', array(
                'metadata' => array(
                    'directories' => array(
                        array(
                            'path' => __DIR__ . '/../Resources/config/serializer',
                            'namespace_prefix' => 'Sulu\Bundle\ContentBundle',
                        ),
                        array(
                            'path' => __DIR__ . '/../Resources/config/serializer',
                            'namespace_prefix' => 'Sulu\Component\Content',
                        ),
                    ),
                ),
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $this->processTemplates($container, $config);
        $this->processPreview($container, $config);

        if (isset($bundles['SuluSearchBundle'])) {
            $this->processSearch($config, $loader, $container);
        }

        $loader->load('services.xml');
        $loader->load('content_types.xml');
        $loader->load('preview.xml');
        $loader->load('structure.xml');
        $loader->load('extension.xml');
        $loader->load('form.xml');
        $loader->load('compat.xml');
        $loader->load('document.xml');
        $loader->load('serializer.xml');
    }

    private function processPreview(ContainerBuilder $container, $config)
    {
        $container->setParameter('sulu.content.preview.mode', $config['preview']['mode']);
        $container->setParameter('sulu.content.preview.websocket', $config['preview']['websocket']);
        $container->setParameter('sulu.content.preview.delay', $config['preview']['delay']);
        $errorTemplate = null;
        if (isset($config['preview']['error_template'])) {
            $errorTemplate = $config['preview']['error_template'];
        }
        $container->setParameter(
            'sulu.content.preview.error_template',
            $errorTemplate
        );
    }

    private function processTemplates(ContainerBuilder $container, $config)
    {
        $container->setParameter(
            'sulu.content.type.smart_content.template',
            $config['types']['smart_content']['template']
        );
        $container->setParameter(
            'sulu.content.type.internal_links.template',
            $config['types']['internal_links']['template']
        );
        $container->setParameter(
            'sulu.content.type.single_internal_link.template',
            $config['types']['single_internal_link']['template']
        );
        $container->setParameter(
            'sulu.content.type.phone.template',
            $config['types']['phone']['template']
        );
        $container->setParameter(
            'sulu.content.type.password.template',
            $config['types']['password']['template']
        );
        $container->setParameter(
            'sulu.content.type.url.template',
            $config['types']['url']['template']
        );
        $container->setParameter(
            'sulu.content.type.email.template',
            $config['types']['email']['template']
        );
        $container->setParameter(
            'sulu.content.type.date.template',
            $config['types']['date']['template']
        );
        $container->setParameter(
            'sulu.content.type.time.template',
            $config['types']['time']['template']
        );
        $container->setParameter(
            'sulu.content.type.color.template',
            $config['types']['color']['template']
        );
        $container->setParameter(
            'sulu.content.type.checkbox.template',
            $config['types']['checkbox']['template']
        );
    }

    private function processSearch($config, LoaderInterface $loader, ContainerBuilder $container)
    {
        $container->setParameter('sulu_content.search.mapping', $config['search']['mapping']);
        $loader->load('search.xml');
    }
}
