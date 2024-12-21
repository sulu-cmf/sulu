<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ListItemAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\Intl\Countries;

class ContactAdmin extends Admin
{
    public const CONTACT_SECURITY_CONTEXT = 'sulu.contact.people';

    public const ACCOUNT_SECURITY_CONTEXT = 'sulu.contact.organizations';

    public const CONTACT_LIST_VIEW = 'sulu_contact.contacts_list';

    public const CONTACT_ADD_FORM_VIEW = 'sulu_contact.contact_add_form';

    public const CONTACT_EDIT_FORM_VIEW = 'sulu_contact.contact_edit_form';

    public const ACCOUNT_LIST_VIEW = 'sulu_contact.accounts_list';

    public const ACCOUNT_ADD_FORM_VIEW = 'sulu_contact.account_add_form';

    public const ACCOUNT_EDIT_FORM_VIEW = 'sulu_contact.account_edit_form';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private SecurityCheckerInterface $securityChecker,
        private ManagerRegistry $managerRegistry
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        $contacts = new NavigationItem('sulu_contact.contacts');
        $contacts->setPosition(40);
        $contacts->setIcon('su-user');

        if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $people = new NavigationItem('sulu_contact.people');
            $people->setPosition(10);
            $people->setView(static::CONTACT_LIST_VIEW);

            $contacts->addChild($people);
        }

        if ($this->securityChecker->hasPermission(static::ACCOUNT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $companies = new NavigationItem('sulu_contact.organizations');
            $companies->setPosition(20);
            $companies->setView(static::ACCOUNT_LIST_VIEW);

            $contacts->addChild($companies);
        }

        $navigationItemCollection->add($contacts);
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $contactFormToolbarActions = [];
            $contactListToolbarActions = [];
            $contactDocumentsToolbarActions = [];
            $contactDocumentsItemActions = [];

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::ADD)) {
                $contactListToolbarActions[] = new ToolbarAction('sulu_admin.add');
            }

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
                $contactFormToolbarActions[] = new ToolbarAction('sulu_admin.save');
                $contactDocumentsToolbarActions[] = new ToolbarAction('sulu_contact.add_media');
                $contactDocumentsToolbarActions[] = new ToolbarAction('sulu_contact.delete_media');
            }

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::DELETE)) {
                $contactFormToolbarActions[] = new ToolbarAction('sulu_admin.delete');
                $contactListToolbarActions[] = new ToolbarAction('sulu_admin.delete');
            }

            if ($this->securityChecker->hasPermission(static::CONTACT_SECURITY_CONTEXT, PermissionTypes::VIEW)) {
                $contactListToolbarActions[] = new ToolbarAction('sulu_admin.export');
                $contactDocumentsItemActions[] = new ListItemAction(
                    'link',
                    ['icon' => 'su-download', 'link_property' => 'url']
                );
            }

            $viewCollection->add(
                $this->viewBuilderFactory->createListViewBuilder(static::CONTACT_LIST_VIEW, '/contacts')
                    ->setResourceKey(ContactInterface::RESOURCE_KEY)
                    ->setListKey('contacts')
                    ->setTitle('sulu_contact.people')
                    ->addListAdapters(['table'])
                    ->setAddView(static::CONTACT_ADD_FORM_VIEW)
                    ->setEditView(static::CONTACT_EDIT_FORM_VIEW)
                    ->addToolbarActions($contactListToolbarActions)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::CONTACT_ADD_FORM_VIEW, '/contacts/add')
                    ->setResourceKey(ContactInterface::RESOURCE_KEY)
                    ->setBackView(static::CONTACT_LIST_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::CONTACT_EDIT_FORM_VIEW, '/contacts/:id')
                    ->setResourceKey(ContactInterface::RESOURCE_KEY)
                    ->setBackView(static::CONTACT_LIST_VIEW)
                    ->setTitleProperty('fullName')
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_contact.contact_add_form.details', '/details')
                    ->setResourceKey(ContactInterface::RESOURCE_KEY)
                    ->setFormKey('contact_details')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::CONTACT_EDIT_FORM_VIEW)
                    ->addToolbarActions($contactFormToolbarActions)
                    ->setParent(static::CONTACT_ADD_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_contact.contact_edit_form.details', '/details')
                    ->setResourceKey(ContactInterface::RESOURCE_KEY)
                    ->setFormKey('contact_details')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($contactFormToolbarActions)
                    ->setTabOrder(1024)
                    ->setParent(static::CONTACT_EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createListViewBuilder('sulu_contact.contact_documents_list', '/documents')
                    ->setResourceKey('contact_media')
                    ->setListKey('media')
                    ->setUserSettingsKey('contact_media')
                    ->setTabTitle('sulu_contact.documents')
                    ->addListAdapters(['table'])
                    ->addToolbarActions($contactDocumentsToolbarActions)
                    ->addItemActions($contactDocumentsItemActions)
                    ->addRouterAttributesToListRequest(['id' => 'contactId'])
                    ->setTabOrder(2048)
                    ->setParent(static::CONTACT_EDIT_FORM_VIEW)
            );
        }

        if ($this->securityChecker->hasPermission(static::ACCOUNT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $accountFormToolbarActions = [];
            $accountListToolbarActions = [];
            $accountDocumentsToolbarActions = [];
            $accountDocumentsItemActions = [];

            if ($this->securityChecker->hasPermission(static::ACCOUNT_SECURITY_CONTEXT, PermissionTypes::ADD)) {
                $accountListToolbarActions[] = new ToolbarAction('sulu_admin.add');
            }

            if ($this->securityChecker->hasPermission(static::ACCOUNT_SECURITY_CONTEXT, PermissionTypes::EDIT)) {
                $accountFormToolbarActions[] = new ToolbarAction('sulu_admin.save');
                $accountDocumentsToolbarActions[] = new ToolbarAction('sulu_contact.add_media');
                $accountDocumentsToolbarActions[] = new ToolbarAction('sulu_contact.delete_media');
            }

            if ($this->securityChecker->hasPermission(static::ACCOUNT_SECURITY_CONTEXT, PermissionTypes::DELETE)) {
                $accountFormToolbarActions[] = new ToolbarAction(
                    'sulu_admin.delete',
                    ['allow_conflict_deletion' => false]
                );
                $accountListToolbarActions[] = new ToolbarAction(
                    'sulu_admin.delete',
                    ['allow_conflict_deletion' => false]
                );
            }

            if ($this->securityChecker->hasPermission(static::ACCOUNT_SECURITY_CONTEXT, PermissionTypes::VIEW)) {
                $accountListToolbarActions[] = new ToolbarAction('sulu_admin.export');
                $accountDocumentsItemActions[] = new ListItemAction(
                    'link',
                    ['icon' => 'su-download', 'link_property' => 'url']
                );
            }

            $viewCollection->add(
                $this->viewBuilderFactory->createListViewBuilder(static::ACCOUNT_LIST_VIEW, '/accounts')
                    ->setResourceKey(AccountInterface::RESOURCE_KEY)
                    ->setListKey('accounts')
                    ->setTitle('sulu_contact.organizations')
                    ->addListAdapters(['table'])
                    ->setAddView(static::ACCOUNT_ADD_FORM_VIEW)
                    ->setEditView(static::ACCOUNT_EDIT_FORM_VIEW)
                    ->addToolbarActions($accountListToolbarActions)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::ACCOUNT_ADD_FORM_VIEW, '/accounts/add')
                    ->setResourceKey(AccountInterface::RESOURCE_KEY)
                    ->setBackView(static::ACCOUNT_LIST_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_contact.account_add_form.details', '/details')
                    ->setResourceKey(AccountInterface::RESOURCE_KEY)
                    ->setFormKey('account_details')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::ACCOUNT_EDIT_FORM_VIEW)
                    ->addToolbarActions($accountFormToolbarActions)
                    ->setParent(static::ACCOUNT_ADD_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::ACCOUNT_EDIT_FORM_VIEW, '/accounts/:id')
                    ->setResourceKey(AccountInterface::RESOURCE_KEY)
                    ->setBackView(static::ACCOUNT_LIST_VIEW)
                    ->setTitleProperty('name')
                    ->addRouterAttributesToBlacklist(['active', 'filter', 'limit', 'page', 'search', 'sortColumn', 'sortOrder'])
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_contact.account_edit_form.details', '/details')
                    ->setResourceKey(AccountInterface::RESOURCE_KEY)
                    ->setFormKey('account_details')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($accountFormToolbarActions)
                    ->setParent(static::ACCOUNT_EDIT_FORM_VIEW)
                    ->setTabOrder(1024)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createListViewBuilder('sulu_contact.account_contacts_list', '/contacts')
                    ->setResourceKey(AccountContact::RESOURCE_KEY)
                    ->setListKey('account_contacts')
                    ->setTabTitle('sulu_contact.people')
                    ->addListAdapters(['table'])
                    ->setEditView(static::CONTACT_EDIT_FORM_VIEW)
                    ->addRouterAttributesToListRequest(['id'])
                    ->addToolbarActions([
                        new ToolbarAction('sulu_contact.add_contact'),
                        new ToolbarAction('sulu_admin.delete'),
                    ])
                    ->addRouterAttributesToListRequest(['id' => 'accountId'])
                    ->setTabOrder(2048)
                    ->setParent(static::ACCOUNT_EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createListViewBuilder('sulu_contact.account_documents_list', '/documents')
                    ->setResourceKey('account_media')
                    ->setListKey('media')
                    ->setUserSettingsKey('contact_media')
                    ->setTabTitle('sulu_contact.documents')
                    ->addListAdapters(['table'])
                    ->addRouterAttributesToListRequest(['id' => 'contactId'])
                    ->addToolbarActions($accountDocumentsToolbarActions)
                    ->addItemActions($accountDocumentsItemActions)
                    ->setTabOrder(3072)
                    ->setParent(static::ACCOUNT_EDIT_FORM_VIEW)
            );
        }
    }

    public function getSecurityContexts()
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Contacts' => [
                    self::CONTACT_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    self::ACCOUNT_SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_contact';
    }

    public function getConfig(): ?array
    {
        return [
            'addressTypes' => $this->managerRegistry->getRepository(\Sulu\Bundle\ContactBundle\Entity\AddressType::class)->findAll(),
            'countries' => Countries::getNames(),
            'emailTypes' => $this->managerRegistry->getRepository(\Sulu\Bundle\ContactBundle\Entity\EmailType::class)->findAll(),
            'faxTypes' => $this->managerRegistry->getRepository(\Sulu\Bundle\ContactBundle\Entity\FaxType::class)->findAll(),
            'phoneTypes' => $this->managerRegistry->getRepository(\Sulu\Bundle\ContactBundle\Entity\PhoneType::class)->findAll(),
            'socialMediaTypes' => $this->managerRegistry
                ->getRepository(\Sulu\Bundle\ContactBundle\Entity\SocialMediaProfileType::class)->findAll(),
            'websiteTypes' => $this->managerRegistry->getRepository(\Sulu\Bundle\ContactBundle\Entity\UrlType::class)->findAll(),
        ];
    }
}
