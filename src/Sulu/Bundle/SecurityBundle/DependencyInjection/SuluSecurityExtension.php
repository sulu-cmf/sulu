<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\SecurityBundle\Exception\RoleNameAlreadyExistsException;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 */
class SuluSecurityExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_security.system', $config['system']);
        $container->setParameter('sulu_security.security_types.fixture', $config['security_types']['fixture']);

        foreach ($config['reset_password']['mail'] as $option => $value) {
            $container->setParameter('sulu_security.reset_password.mail.' . $option, $value);
        }

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('command.xml');

        if ($config['checker']['enabled']) {
            $loader->load('checker.xml');
        }

        $this->configurePersistence($config['objects'], $container);
    }

    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            RoleNameAlreadyExistsException::class => 409,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('framework')
            && SuluKernel::CONTEXT_ADMIN === $container->getParameter('sulu.context')
        ) {
            $container->prependExtensionConfig(
                'framework',
                [
                    'csrf_protection' => true,
                    'session' => [
                        'cookie_path' => '/admin',
                    ],
                    'fragments' => [
                        'path' => '/admin/_fragments',
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'forms' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/forms',
                        ],
                    ],
                    'resources' => [
                        'roles' => [
                            'datagrid' => '%sulu.model.role.class%',
                            'endpoint' => 'get_roles',
                        ],
                        'users' => [
                            'endpoint' => 'get_users',
                        ],
                    ],
                ]
            );
        }
    }
}
