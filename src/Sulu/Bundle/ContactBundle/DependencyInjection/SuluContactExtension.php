<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\DependencyInjection;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\SecurityBundle\Security\Exception\EmailNotUniqueException;
use Sulu\Bundle\SecurityBundle\Security\Exception\UsernameNotUniqueException;
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
class SuluContactExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    /**
     * Allow an extension to prepend the extension configurations.
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_search')) {
            $container->prependExtensionConfig(
                'sulu_search',
                [
                    'indexes' => [
                        'contact' => [
                            'name' => 'sulu_contact.people',
                            'icon' => 'su-user',
                            'view' => [
                                'name' => ContactAdmin::CONTACT_EDIT_FORM_VIEW,
                                'result_to_view' => [
                                    'id' => 'id',
                                    'locale' => 'locale',
                                ],
                            ],
                            'security_context' => 'sulu.contact.people',
                        ],
                        'account' => [
                            'name' => 'sulu_contact.organizations',
                            'icon' => 'su-house',
                            'view' => [
                                'name' => ContactAdmin::ACCOUNT_EDIT_FORM_VIEW,
                                'result_to_view' => [
                                    'id' => 'id',
                                    'locale' => 'locale',
                                ],
                            ],
                            'security_context' => 'sulu.contact.organizations',
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
                        'sulu_contact' => [
                            'meta_title' => ['en' => 'Sulu contacts', 'de' => 'Sulu Kontakte'],
                            'collections' => [
                                'contact' => [
                                    'meta_title' => ['en' => 'People', 'de' => 'Personen'],
                                ],
                                'account' => [
                                    'meta_title' => ['en' => 'Organizations', 'de' => 'Organisationen'],
                                ],
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
                        'contacts' => [
                            'routes' => [
                                'list' => 'sulu_contact.get_contacts',
                                'detail' => 'sulu_contact.get_contact',
                            ],
                        ],
                        'contact_titles' => [
                            'routes' => [
                                'list' => 'sulu_contact.get_contact-titles',
                            ],
                        ],
                        'contact_positions' => [
                            'routes' => [
                                'list' => 'sulu_contact.get_contact-positions',
                            ],
                        ],
                        'contact_media' => [
                            'routes' => [
                                'list' => 'sulu_contact.cget_contact_medias',
                                'detail' => 'sulu_contact.delete_contact_medias',
                            ],
                        ],
                        'accounts' => [
                            'routes' => [
                                'list' => 'sulu_contact.get_accounts',
                                'detail' => 'sulu_contact.get_account',
                            ],
                        ],
                        'account_media' => [
                            'routes' => [
                                'list' => 'sulu_contact.cget_account_medias',
                                'detail' => 'sulu_contact.delete_account_medias',
                            ],
                        ],
                        'account_contacts' => [
                            'routes' => [
                                'list' => 'sulu_contact.get_account_contacts',
                                'detail' => 'sulu_contact.delete_account_contacts',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'single_selection' => [
                            'single_account_selection' => [
                                'default_type' => 'auto_complete',
                                'resource_key' => 'accounts',
                                'types' => [
                                    'auto_complete' => [
                                        'display_property' => 'name',
                                        'search_properties' => ['number', 'name'],
                                    ],
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'accounts',
                                        'display_properties' => ['name'],
                                        'empty_text' => 'sulu_contact.no_account_selected',
                                        'icon' => 'su-house',
                                        'overlay_title' => 'sulu_contact.single_account_selection_overlay_title',
                                    ],
                                ],
                            ],
                            'single_contact_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'contacts',
                                'view' => [
                                    'name' => 'sulu_contact.contact_edit_form',
                                    'result_to_view' => [
                                        'id' => 'id',
                                    ],
                                ],
                                'types' => [
                                    'auto_complete' => [
                                        'display_property' => 'fullName',
                                        'search_properties' => ['fullName'],
                                    ],
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'contacts',
                                        'display_properties' => ['fullName'],
                                        'empty_text' => 'sulu_contact.no_contact_selected',
                                        'icon' => 'su-user',
                                        'overlay_title' => 'sulu_contact.single_contact_selection_overlay_title',
                                    ],
                                ],
                            ],
                            'single_contact_title_selection' => [
                                'default_type' => 'single_select',
                                'resource_key' => 'contact_titles',
                                'types' => [
                                    'single_select' => [
                                        'id_property' => 'id',
                                        'display_property' => 'title',
                                        'overlay_title' => 'sulu_contact.edit_title_overlay_title',
                                    ],
                                ],
                            ],
                            'single_contact_position_selection' => [
                                'default_type' => 'single_select',
                                'resource_key' => 'contact_positions',
                                'types' => [
                                    'single_select' => [
                                        'id_property' => 'id',
                                        'display_property' => 'position',
                                        'overlay_title' => 'sulu_contact.edit_position_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                        'selection' => [
                            'contact_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'contacts',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'contacts',
                                        'display_properties' => ['firstName', 'lastName'],
                                        'icon' => 'su-user',
                                        'label' => 'sulu_contact.contact_selection_label',
                                        'overlay_title' => 'sulu_contact.contact_selection_overlay_title',
                                    ],
                                ],
                            ],
                            'account_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'accounts',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'accounts',
                                        'display_properties' => ['name'],
                                        'icon' => 'su-user',
                                        'label' => 'sulu_contact.account_selection_label',
                                        'overlay_title' => 'sulu_contact.account_selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
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
                            UsernameNotUniqueException::class => 409,
                            EmailNotUniqueException::class => 409,
                        ],
                    ],
                ]
            );
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('content.xml');
        $loader->load('command.xml');

        if (
            InstalledVersions::isInstalled('sulu/content-bundle')
            && InstalledVersions::satisfies(new VersionParser(), 'sulu/content-bundle', '^0.9')
        ) {
            $loader->load('services_content.xml');
        }

        if (\array_key_exists('SuluTrashBundle', $bundles)) {
            $loader->load('services_trash.xml');
        }

        $container->setParameter(
            'sulu_contact.defaults',
            $config['defaults']
        );

        $this->setDefaultForFormOfAddress($config);
        $container->setParameter(
            'sulu_contact.form_of_address',
            $config['form_of_address']
        );
        $container->setParameter(
            'sulu_contact.contact_form.category_root',
            $config['form']['contact']['category_root']
        );
        $container->setParameter(
            'sulu_contact.account_form.category_root',
            $config['form']['account']['category_root']
        );

        $this->configurePersistence($config['objects'], $container);
        $container->addAliases(
            [
                ContactRepositoryInterface::class => 'sulu.repository.contact',
                AccountRepositoryInterface::class => 'sulu.repository.account',
            ]
        );
    }

    /**
     * Sets default values for form of address if not defined in config.
     *
     * @param array $config
     */
    private function setDefaultForFormOfAddress($config)
    {
        if (!\array_key_exists('form_of_address', $config) || 0 == \count($config['form_of_address'])) {
            $config['form_of_address'] = [
                'male' => [
                    'id' => 0,
                    'name' => 'male',
                    'translation' => 'contact.contacts.formOfAddress.male',
                ],
                'female' => [
                    'id' => 1,
                    'name' => 'female',
                    'translation' => 'contact.contacts.formOfAddress.female',
                ],
            ];
        }
    }
}
