<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ExampleAdmin extends Admin
{
    public const SECURITY_CONTEXT = 'sulu.example.examples';

    public const LIST_VIEW = 'example_test.example.list';

    public const ADD_TABS_VIEW = 'example_test.example.add_tabs';

    public const EDIT_TABS_VIEW = 'example_test.example.edit_tabs';

    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var ContentViewBuilderFactoryInterface
     */
    private $contentViewBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        ContentViewBuilderFactoryInterface $contentViewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManagerInterface $localizationManager
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->contentViewBuilderFactory = $contentViewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $navigationItem = new NavigationItem('example_test.examples');
            $navigationItem->setPosition(30);
            $navigationItem->setIcon('su-pen');
            $navigationItem->setView(static::LIST_VIEW);

            $navigationItemCollection->add($navigationItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $bundlePrefix = 'example_test.';
        $locales = $this->localizationManager->getLocales();
        $resourceKey = Example::RESOURCE_KEY;

        $formToolbarActions = [];
        $listToolbarActions = [];

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.save');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.delete');
            $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.export');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->viewBuilderFactory->createListViewBuilder(static::LIST_VIEW, '/' . $resourceKey . '/:locale')
                    ->setResourceKey($resourceKey)
                    ->setListKey($resourceKey)
                    ->setTitle($bundlePrefix . $resourceKey)
                    ->addListAdapters(['table'])
                    ->addLocales($locales)
                    ->setDefaultLocale($locales[0])
                    ->setAddView(static::ADD_TABS_VIEW)
                    ->setEditView(static::EDIT_TABS_VIEW)
                    ->addToolbarActions($listToolbarActions)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::ADD_TABS_VIEW, '/' . $resourceKey . '/:locale/add')
                    ->setResourceKey($resourceKey)
                    ->addLocales($locales)
                    ->setBackView(static::LIST_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createResourceTabViewBuilder(static::EDIT_TABS_VIEW, '/' . $resourceKey . '/:locale/:id')
                    ->setResourceKey($resourceKey)
                    ->addLocales($locales)
                    ->setBackView(static::LIST_VIEW)
                    ->setTitleProperty('name')
            );

            $viewBuilders = $this->contentViewBuilderFactory->createViews(
                Example::class,
                static::EDIT_TABS_VIEW,
                static::ADD_TABS_VIEW,
                static::SECURITY_CONTEXT
            );

            foreach ($viewBuilders as $viewBuilder) {
                $viewCollection->add($viewBuilder);
            }
        }
    }

    /**
     * @return mixed[]
     */
    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Settings' => [
                    static::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                        PermissionTypes::LIVE,
                    ],
                ],
            ],
        ];
    }
}
