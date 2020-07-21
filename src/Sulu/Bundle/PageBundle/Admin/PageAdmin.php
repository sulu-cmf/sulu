<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\View;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class PageAdmin extends Admin
{
    /**
     * The prefix for the security context, the key of the webspace has to be appended.
     *
     * @var string
     */
    const SECURITY_CONTEXT_PREFIX = 'sulu.webspaces.';

    const WEBSPACE_TABS_VIEW = 'sulu_page.webspaces';

    const PAGES_VIEW = 'sulu_page.pages_list';

    const ADD_FORM_VIEW = 'sulu_page.page_add_form';

    const EDIT_FORM_VIEW = 'sulu_page.page_edit_form';

    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    /**
     * @var bool
     */
    private $versioningEnabled;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        WebspaceManagerInterface $webspaceManager,
        SecurityCheckerInterface $securityChecker,
        SessionManagerInterface $sessionManager,
        TeaserProviderPoolInterface $teaserProviderPool,
        bool $versioningEnabled
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->webspaceManager = $webspaceManager;
        $this->securityChecker = $securityChecker;
        $this->sessionManager = $sessionManager;
        $this->teaserProviderPool = $teaserProviderPool;
        $this->versioningEnabled = $versioningEnabled;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->hasSomeWebspacePermission()) {
            $webspaceItem = new NavigationItem('sulu_page.webspaces');
            $webspaceItem->setPosition(10);
            $webspaceItem->setIcon('su-webspace');
            $webspaceItem->setView(static::WEBSPACE_TABS_VIEW);

            $navigationItemCollection->add($webspaceItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        /** @var Webspace $firstWebspace */
        $firstWebspace = \current($this->webspaceManager->getWebspaceCollection()->getWebspaces());
        $publishDisplayCondition = '(!_permissions || _permissions.live)';

        $formToolbarActionsWithType = [
            new ToolbarAction(
                'sulu_admin.save_with_publishing',
                [
                    'publish_visible_condition' => '(!_permissions || _permissions.live)',
                    'save_visible_condition' => '(!_permissions || _permissions.edit)',
                ]
            ),
            new ToolbarAction(
                'sulu_admin.type',
                [
                    'disabled_condition' => '(_permissions && !_permissions.edit)',
                ]
            ),
            new ToolbarAction(
                'sulu_admin.delete',
                [
                    'visible_condition' => '(!_permissions || _permissions.delete) && url != "/"',
                    'router_attributes_to_back_view' => ['webspace'],
                ]
            ),
            new DropdownToolbarAction(
                'sulu_admin.edit',
                'su-pen',
                [
                    new ToolbarAction(
                        'sulu_admin.copy_locale',
                        [
                            'visible_condition' => '(!_permissions || _permissions.edit)',
                        ]
                    ),
                    new ToolbarAction(
                        'sulu_admin.delete_draft',
                        [
                            'visible_condition' => $publishDisplayCondition,
                        ]
                    ),
                    new ToolbarAction(
                        'sulu_admin.set_unpublished',
                        [
                            'visible_condition' => $publishDisplayCondition,
                        ]
                    ),
                ]
            ),
        ];

        $formToolbarActionsWithoutType = [
            new ToolbarAction('sulu_admin.save_with_publishing'),
        ];

        $routerAttributesToFormRequest = ['parentId', 'webspace'];
        $routerAttributesToFormMetdata = ['webspace', 'defaultTemplate'];

        $previewCondition = 'nodeType == 1';

        // This view has to be registered even if permissions for pages are missing
        // Otherwise the application breaks when other bundles try to add child views to this one
        $viewCollection->add(
            $this->viewBuilderFactory
                ->createViewBuilder(static::WEBSPACE_TABS_VIEW, '/webspaces/:webspace', 'sulu_page.webspace_tabs')
                ->setAttributeDefault('webspace', $firstWebspace->getKey())
        );

        if ($this->hasSomeWebspacePermission()) {
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createViewBuilder(static::PAGES_VIEW, '/pages/:locale', 'sulu_page.page_list')
                    ->setAttributeDefault('locale', $firstWebspace->getDefaultLocalization()->getLocale())
                    ->setOption('tabTitle', 'sulu_page.pages')
                    ->setOption('tabOrder', 0)
                    ->setOption('tabPriority', 1024)
                    ->addRerenderAttribute('webspace')
                    ->setParent(static::WEBSPACE_TABS_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createViewBuilder(
                    static::ADD_FORM_VIEW,
                    '/webspaces/:webspace/pages/:locale/add/:parentId',
                    'sulu_page.page_tabs'
                )
                    ->setOption('backView', static::PAGES_VIEW)
                    ->setOption('routerAttributesToBackView', ['webspace'])
                    ->setOption('resourceKey', 'pages')
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_page.page_add_form.details', '/details')
                    ->setResourceKey('pages')
                    ->setFormKey('page')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->addRouterAttributesToEditView(['webspace'])
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                    ->addRouterAttributesToFormMetadata($routerAttributesToFormMetdata)
                    ->setParent(static::ADD_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createViewBuilder(
                    static::EDIT_FORM_VIEW,
                    '/webspaces/:webspace/pages/:locale/:id',
                    'sulu_page.page_tabs'
                )
                    ->setOption('backView', static::PAGES_VIEW)
                    ->setOption('routerAttributesToBackView', ['id' => 'active', 'webspace'])
                    ->setOption('resourceKey', 'pages')
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createPreviewFormViewBuilder('sulu_page.page_edit_form.details', '/details')
                    ->disablePreviewWebspaceChooser()
                    ->setResourceKey('pages')
                    ->setFormKey('page')
                    ->setTabTitle('sulu_admin.details')
                    ->setTabPriority(1024)
                    ->setTabCondition('nodeType == 1 && shadowOn == false')
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                    ->addRouterAttributesToFormMetadata($routerAttributesToFormMetdata)
                    ->setPreviewCondition($previewCondition)
                    ->setTabOrder(1024)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createPreviewFormViewBuilder('sulu_page.page_edit_form.seo', '/seo')
                    ->disablePreviewWebspaceChooser()
                    ->setResourceKey('pages')
                    ->setFormKey('page_seo')
                    ->setTabTitle('sulu_page.seo')
                    ->setTabCondition('nodeType == 1 && shadowOn == false')
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                    ->setPreviewCondition($previewCondition)
                    ->setTitleVisible(true)
                    ->setTabOrder(2048)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createPreviewFormViewBuilder('sulu_page.page_edit_form.excerpt', '/excerpt')
                    ->disablePreviewWebspaceChooser()
                    ->setResourceKey('pages')
                    ->setFormKey('page_excerpt')
                    ->setTabTitle('sulu_page.excerpt')
                    ->setTabCondition('(nodeType == 1 || nodeType == 4) && shadowOn == false')
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                    ->setPreviewCondition($previewCondition)
                    ->setTitleVisible(true)
                    ->setTabOrder(3072)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createPreviewFormViewBuilder('sulu_page.page_edit_form.settings', '/settings')
                    ->disablePreviewWebspaceChooser()
                    ->setResourceKey('pages')
                    ->setFormKey('page_settings')
                    ->setTabTitle('sulu_page.settings')
                    ->setTabPriority(512)
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                    ->setPreviewCondition($previewCondition)
                    ->setTitleVisible(true)
                    ->setTabOrder(4096)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_page.page_edit_form.permissions', '/permissions')
                    ->setResourceKey('permissions')
                    ->setFormKey('permission_details')
                    ->addRequestParameters(['resourceKey' => 'pages'])
                    ->setTabCondition('_permissions.security')
                    ->setTabTitle('sulu_security.permissions')
                    ->addToolbarActions([new ToolbarAction('sulu_admin.save')])
                    ->addRouterAttributesToFormRequest(['webspace'])
                    ->setTitleVisible(true)
                    ->setTabOrder(5120)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
        }
    }

    public function getSecurityContexts()
    {
        $webspaceContexts = [];
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            /* @var Webspace $webspace */
            $webspaceContexts[self::SECURITY_CONTEXT_PREFIX . $webspace->getKey()] = [
                PermissionTypes::VIEW,
                PermissionTypes::ADD,
                PermissionTypes::EDIT,
                PermissionTypes::DELETE,
                PermissionTypes::LIVE,
                PermissionTypes::SECURITY,
            ];
        }

        return \array_merge(
            [
                self::SULU_ADMIN_SECURITY_SYSTEM => [
                    'Webspaces' => $webspaceContexts,
                ],
            ],
            $this->getWebspaceSecuritySystemContexts()
        );
    }

    public function getSecurityContextsWithPlaceholder()
    {
        return \array_merge(
            [
                self::SULU_ADMIN_SECURITY_SYSTEM => [
                    'Webspaces' => [
                        self::SECURITY_CONTEXT_PREFIX . '#webspace#' => [
                            PermissionTypes::VIEW,
                            PermissionTypes::ADD,
                            PermissionTypes::EDIT,
                            PermissionTypes::DELETE,
                            PermissionTypes::LIVE,
                            PermissionTypes::SECURITY,
                        ],
                    ],
                ],
            ],
            $this->getWebspaceSecuritySystemContexts()
        );
    }

    private function getWebspaceSecuritySystemContexts(): array
    {
        $webspaceSecuritySystemContexts = [];

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $security = $webspace->getSecurity();
            if (!$security) {
                continue;
            }

            $system = $security->getSystem();
            if (!$system) {
                continue;
            }

            $webspaceSecuritySystemContexts[$system] = [];
        }

        return $webspaceSecuritySystemContexts;
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_page';
    }

    public function getConfig(): ?array
    {
        return [
            'teaser' => $this->teaserProviderPool->getConfiguration(),
            'versioning' => $this->versioningEnabled,
            'webspaces' => $this->webspaceManager->getWebspaceCollection()->getWebspaces(),
        ];
    }

    private function hasSomeWebspacePermission(): bool
    {
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $hasWebspacePermission = $this->securityChecker->hasPermission(
                self::SECURITY_CONTEXT_PREFIX . $webspace->getKey(),
                PermissionTypes::EDIT
            );

            if ($hasWebspacePermission) {
                return true;
            }
        }

        return false;
    }
}
