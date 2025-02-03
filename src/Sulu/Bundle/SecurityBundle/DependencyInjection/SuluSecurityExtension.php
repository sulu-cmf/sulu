<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DependencyInjection;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\SecurityBundle\Exception\RoleKeyAlreadyExistsException;
use Sulu\Bundle\SecurityBundle\Exception\RoleNameAlreadyExistsException;
use Sulu\Bundle\SecurityBundle\Security\Exception\EmailNotUniqueException;
use Sulu\Bundle\SecurityBundle\Security\Exception\UsernameNotUniqueException;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterInterface;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\RoleSettingRepositoryInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\DescendantProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * This is the class that loads and manages your bundle configuration.
 */
class SuluSecurityExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    /**
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_security.system', $config['system']);
        $container->setParameter('sulu_security.security_types.fixture', $config['security_types']['fixture']);

        $passwordPolicyEnabled = $config['password_policy']['enabled'];
        $container->setParameter('sulu_security.password_policy_pattern', $passwordPolicyEnabled ? $config['password_policy']['pattern'] : null);
        $container->setParameter('sulu_security.password_policy_info_translation_key', $passwordPolicyEnabled ? $config['password_policy']['info_translation_key'] : null);

        $container->setParameter('sulu_security.two_factor_email_template', $config['two_factor']['email']['template']);

        $twoFactorForcePattern = null;
        $twoFactorForceEnabled = $config['two_factor']['force']['enabled'];
        if ($twoFactorForceEnabled) {
            $twoFactorForcePattern = $config['two_factor']['force']['pattern'];
        }
        $container->setParameter('sulu_security.two_factor_force_pattern', $twoFactorForcePattern);

        foreach ($config['reset_password']['mail'] as $option => $value) {
            $container->setParameter('sulu_security.reset_password.mail.' . $option, $value);
        }

        $container->registerForAutoconfiguration(DescendantProviderInterface::class)
            ->addTag('sulu_security.access_control_descendant_provider');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('command.xml');

        /** @var array<string, class-string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (\in_array(SchebTwoFactorBundle::class, $bundles, true)) {
            $loader->load('2fa.xml');

            if (\interface_exists(AuthCodeMailerInterface::class)) {
                $loader->load('2fa_email.xml');
            }
        }

        if (\interface_exists(LogoutSuccessHandlerInterface::class)) {
            $loader->load('logout_success_handler.xml');
        }

        if ($config['checker']['enabled']) {
            $loader->load('checker.xml');
        }

        if ($twoFactorForcePattern) {
            $loader->load('2fa_force.xml');
        }

        $this->configurePersistence($config['objects'], $container);
        $container->addAliases(
            [
                UserRepositoryInterface::class => 'sulu.repository.user',
                RoleRepositoryInterface::class => 'sulu.repository.role',
                RoleSettingRepositoryInterface::class => 'sulu.repository.role_setting',
                AccessControlRepositoryInterface::class => 'sulu.repository.access_control',
            ],
        );

        $container->setParameter('sulu_security.has_single_sign_on_providers', false);

        if (!\array_key_exists('single_sign_on', $config) || !\array_key_exists('providers', $config['single_sign_on'])) {
            return;
        }

        if (!\interface_exists(AccessTokenExtractorInterface::class)) {
            throw new \RuntimeException('The symfony/security-http package is required to use the SuluSecurityBundle. At least symfony/security-http 6.2 is required.');
        }

        $loader->load('single_sign_on.xml');

        $container->setParameter(
            'sulu_security.has_single_sign_on_providers',
            \count($config['single_sign_on']['providers']) > 0,
        );

        foreach ($config['single_sign_on']['providers'] as $domain => $providerConfig) {
            $definition = new Definition();
            $definition->setFactory([new Reference('sulu_security.single_sign_on_adapter_factory'), 'createAdapter']);
            $definition->setClass(SingleSignOnAdapterInterface::class);
            $definition->setArguments([$providerConfig['dsn'], $providerConfig['default_role_key'] ?? null]);
            $definition->addTag('sulu_security.single_sign_on_adapter', ['domain' => $domain]);

            $container->setDefinition('sulu_security.single_sign_on_adapter_' . \str_replace('.', '_', $domain), $definition);
        }
    }

    /**
     * @return void
     */
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('scheb_two_factor') && \interface_exists(AuthCodeMailerInterface::class)) {
            $container->prependExtensionConfig(
                'scheb_two_factor',
                [
                    'email' => [
                        'enabled' => false,
                        'mailer' => 'sulu_security.two_factor_mailer',
                    ],
                ],
            );
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            RoleNameAlreadyExistsException::class => 409,
                            RoleKeyAlreadyExistsException::class => 409,
                            UsernameNotUniqueException::class => 409,
                            EmailNotUniqueException::class => 409,
                        ],
                    ],
                ],
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
                ],
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
                        'permissions' => [
                            'routes' => [
                                'detail' => 'sulu_security.get_permissions',
                            ],
                        ],
                        'roles' => [
                            'routes' => [
                                'list' => 'sulu_security.get_roles',
                                'detail' => 'sulu_security.get_role',
                            ],
                        ],
                        'users' => [
                            'routes' => [
                                'list' => 'sulu_security.get_users',
                                'detail' => 'sulu_security.get_user',
                            ],
                        ],
                        'profile' => [
                            'routes' => [
                                'detail' => 'sulu_security.get_profile',
                            ],
                        ],
                    ],
                ],
            );
        }
    }
}
