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

use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\AccessControlRepository;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\RoleRepository;
use Sulu\Bundle\SecurityBundle\Entity\RoleSetting;
use Sulu\Bundle\SecurityBundle\Entity\RoleSettingRepository;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_security');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('system')
                    ->defaultValue('Sulu')
                    ->setDeprecated(
                        'sulu/sulu',
                        '2.1.0',
                        'The %node% option is deprecated and will be removed. Setting this option in the admin context will break the permissions registered by the bundles.'
                    )
                ->end()
                ->arrayNode('checker')
                    ->canBeEnabled()
                ->end()
                ->arrayNode('security_types')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('fixture')
                            ->defaultValue(__DIR__ . '/../DataFixtures/security-types.xml')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('password_policy')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('pattern')
                            ->cannotBeEmpty()
                            ->defaultValue('.{8,}')
                        ->end()
                        ->scalarNode('info_translation_key')
                            ->cannotBeEmpty()
                            ->defaultValue('sulu_security.password_policy_information')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('single_sign_on')
                    ->children()
                        ->arrayNode('providers')
                            ->useAttributeAsKey('domain')
                            ->normalizeKeys(false)
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('dsn')
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('default_role_key')
                                        ->cannotBeEmpty()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('two_factor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('email')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->cannotBeEmpty()
                                    ->defaultValue('@SuluSecurity/mail_templates/two_factor')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('force')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('pattern')
                                    ->cannotBeEmpty()
                                    ->defaultValue('(.+)')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('reset_password')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('mail')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('token_send_limit')
                                    ->min(1)
                                    ->defaultValue(3)
                                ->end()
                                ->scalarNode('sender')
                                    ->defaultValue('')
                                ->end()
                                ->scalarNode('subject')
                                    ->cannotBeEmpty()
                                    ->defaultValue('sulu_security.reset_mail_subject')
                                ->end()
                                ->scalarNode('template')
                                    ->cannotBeEmpty()
                                    ->defaultValue('@SuluSecurity/mail_templates/reset_password.html.twig')
                                ->end()
                                ->scalarNode('translation_domain')
                                    ->cannotBeEmpty()
                                    ->defaultValue('admin')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->addObjectsSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds `objects` section.
     *
     * @return void
     */
    private function addObjectsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('user')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(User::class)->end()
                                ->scalarNode('repository')->defaultValue(UserRepository::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('role')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Role::class)->end()
                                ->scalarNode('repository')->defaultValue(RoleRepository::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('role_setting')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(RoleSetting::class)->end()
                                ->scalarNode('repository')->defaultValue(RoleSettingRepository::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('access_control')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(AccessControl::class)->end()
                                ->scalarNode('repository')->defaultValue(AccessControlRepository::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
