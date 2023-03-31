<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class CategoryAdmin extends Admin
{
    public const SECURITY_CONTEXT = 'sulu.settings.categories';

    public const LIST_VIEW = 'sulu_category.list';

    public const ADD_FORM_VIEW = 'sulu_category.add_form';

    public const EDIT_FORM_VIEW = 'sulu_category.edit_form';

    private \Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface $viewBuilderFactory;

    private \Sulu\Component\Security\Authorization\SecurityCheckerInterface $securityChecker;

    private \Sulu\Component\Localization\Manager\LocalizationManagerInterface $localizationManager;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManagerInterface $localizationManager
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $categoryItem = new NavigationItem('sulu_category.categories');
            $categoryItem->setPosition(20);
            $categoryItem->setView(static::LIST_VIEW);

            $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($categoryItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $locales = $this->localizationManager->getLocales();

        $formToolbarActions = [];
        $listToolbarActions = [];

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::ADD)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.save');
            $listToolbarActions[] = new ToolbarAction('sulu_admin.move');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.delete');
            $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.export');
        }

        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $listViewBuilder = $this->viewBuilderFactory
                ->createListViewBuilder(static::LIST_VIEW, '/categories/:locale')
                ->setResourceKey(CategoryInterface::RESOURCE_KEY)
                ->setListKey('categories')
                ->setTitle('sulu_category.categories')
                ->addListAdapters(['tree_table'])
                ->addLocales($locales)
                ->setEditView(static::EDIT_FORM_VIEW)
                ->enableSearching()
                ->addToolbarActions($listToolbarActions);

            // hide add button of the tree_table adapter by not setting an add view if the user has no add permission
            if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::ADD)) {
                $listViewBuilder->setAddView(static::ADD_FORM_VIEW);
            }
            $viewCollection->add($listViewBuilder);

            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::ADD_FORM_VIEW, '/categories/:locale/add')
                    ->setResourceKey(CategoryInterface::RESOURCE_KEY)
                    ->addLocales($locales)
                    ->setBackView(static::LIST_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_category.add_form.details', '/details')
                    ->setResourceKey(CategoryInterface::RESOURCE_KEY)
                    ->setFormKey('category_details')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($formToolbarActions)
                    ->addRouterAttributesToFormRequest(['parentId'])
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->setParent(static::ADD_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::EDIT_FORM_VIEW, '/categories/:locale/:id')
                    ->setResourceKey(CategoryInterface::RESOURCE_KEY)
                    ->addLocales($locales)
                    ->setBackView(static::LIST_VIEW)
                    ->addRouterAttributesToBackView(['id' => 'active'])
                    ->setTitleProperty('name')
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_category.edit_form.details', '/details')
                    ->setResourceKey(CategoryInterface::RESOURCE_KEY)
                    ->setFormKey('category_details')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($formToolbarActions)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormOverlayListViewBuilder('sulu_category.edit_form.keywords', '/keywords')
                    ->setResourceKey('category_keywords')
                    ->setListKey('category_keywords')
                    ->addListAdapters(['table'])
                    ->addRouterAttributesToListRequest(['id' => 'categoryId'])
                    ->setFormKey('category_keywords')
                    ->addRouterAttributesToFormRequest(['id' => 'categoryId'])
                    ->setTabTitle('sulu_category.keywords')
                    ->addToolbarActions([new ToolbarAction('sulu_admin.add'), new ToolbarAction('sulu_admin.delete')])
                    ->setParent(static::EDIT_FORM_VIEW)
            );
        }
    }

    public function getSecurityContexts()
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Settings' => [
                    static::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }
}
