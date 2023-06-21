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

use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ListItemAction;
use Sulu\Bundle\AdminBundle\Admin\View\SaveWithFormDialogToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
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
    public const SECURITY_CONTEXT_PREFIX = 'sulu.webspaces.';

    public const SECURITY_CONTEXT_GROUP = 'Webspaces';

    public const WEBSPACE_TABS_VIEW = 'sulu_page.webspaces';

    public const PAGES_VIEW = 'sulu_page.pages_list';

    public const ADD_FORM_VIEW = 'sulu_page.page_add_form';

    public const EDIT_FORM_VIEW = 'sulu_page.page_edit_form';

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

    /**
     * @var ActivityViewBuilderFactoryInterface
     */
    private $activityViewBuilderFactory;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        WebspaceManagerInterface $webspaceManager,
        SecurityCheckerInterface $securityChecker,
        SessionManagerInterface $sessionManager,
        TeaserProviderPoolInterface $teaserProviderPool,
        bool $versioningEnabled,
        ActivityViewBuilderFactoryInterface $activityViewBuilderFactory
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->webspaceManager = $webspaceManager;
        $this->securityChecker = $securityChecker;
        $this->sessionManager = $sessionManager;
        $this->teaserProviderPool = $teaserProviderPool;
        $this->versioningEnabled = $versioningEnabled;
        $this->activityViewBuilderFactory = $activityViewBuilderFactory;
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

        $createPageSaveVisibleCondition = '!_permissions && (!__webspace || __webspace._permissions.edit)';
        $editPageSaveVisibleCondition = '_permissions && _permissions.edit';
        $saveVisibleCondition = '(' . $createPageSaveVisibleCondition . ') || (' . $editPageSaveVisibleCondition . ')';

        $createPagePublishVisibleCondition = '!_permissions  && (!__webspace || __webspace._permissions.live)';
        $editPagePublishVisibleCondition = '(!_permissions || _permissions.live)';
        $publishVisibleCondition = '(' . $createPagePublishVisibleCondition . ') || (' . $editPagePublishVisibleCondition . ')';

        $saveWithPublishingDropdown = new DropdownToolbarAction(
            'sulu_admin.save',
            'su-save',
            [
                new ToolbarAction(
                    'sulu_admin.save',
                    [
                        'label' => 'sulu_admin.save_draft',
                        'options' => ['action' => 'draft'],
                        'visible_condition' => $saveVisibleCondition,
                    ]
                ),
                new ToolbarAction(
                    'sulu_admin.save',
                    [
                        'label' => 'sulu_admin.save_publish',
                        'options' => ['action' => 'publish'],
                        'visible_condition' => '(' . $saveVisibleCondition . ') && (' . $publishVisibleCondition . ')',
                    ]
                ),
                new ToolbarAction(
                    'sulu_admin.publish',
                    [
                        'visible_condition' => $publishVisibleCondition,
                    ]
                ),
            ]
        );

        $formToolbarActionsWithType = [
            $saveWithPublishingDropdown,
            new ToolbarAction(
                'sulu_admin.type',
                [
                    'sort_by' => 'title',
                    'disabled_condition' => '(_permissions && !_permissions.edit)',
                ]
            ),
            new DropdownToolbarAction(
                'sulu_admin.delete',
                'su-trash-alt',
                [
                    new ToolbarAction(
                        'sulu_admin.delete',
                        [
                            'visible_condition' => '(!_permissions || _permissions.delete) && url != "/"',
                            'router_attributes_to_back_view' => ['webspace'],
                        ]
                    ),
                    new ToolbarAction(
                        'sulu_admin.delete',
                        [
                            'visible_condition' => '(!_permissions || _permissions.delete) && url != "/"',
                            'router_attributes_to_back_view' => ['webspace'],
                            'delete_locale' => true,
                        ]
                    ),
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
                            'visible_condition' => $publishVisibleCondition,
                        ]
                    ),
                    new ToolbarAction(
                        'sulu_admin.set_unpublished',
                        [
                            'visible_condition' => $publishVisibleCondition,
                        ]
                    ),
                ]
            ),
        ];

        $formToolbarActionsWithoutType = [
            $saveWithPublishingDropdown,
        ];

        $routerAttributesToFormRequest = ['parentId', 'webspace'];
        $routerAttributesToFormMetadata = ['webspace'];

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
                    ->setOption('resourceKey', BasePageDocument::RESOURCE_KEY)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_page.page_add_form.details', '/details')
                    ->setResourceKey(BasePageDocument::RESOURCE_KEY)
                    ->setFormKey('page')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->addRouterAttributesToEditView(['webspace'])
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                    ->addRouterAttributesToFormMetadata($routerAttributesToFormMetadata)
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
                    ->setOption('resourceKey', BasePageDocument::RESOURCE_KEY)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createPreviewFormViewBuilder('sulu_page.page_edit_form.details', '/details')
                    ->disablePreviewWebspaceChooser()
                    ->setResourceKey(BasePageDocument::RESOURCE_KEY)
                    ->setFormKey('page')
                    ->setTabTitle('sulu_admin.details')
                    ->setTabPriority(1024)
                    ->setTabCondition('nodeType == 1 && shadowOn == false')
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                    ->addRouterAttributesToFormMetadata($routerAttributesToFormMetadata)
                    ->setPreviewCondition($previewCondition)
                    ->setTabOrder(1024)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createPreviewFormViewBuilder('sulu_page.page_edit_form.seo', '/seo')
                    ->disablePreviewWebspaceChooser()
                    ->setResourceKey(BasePageDocument::RESOURCE_KEY)
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
                    ->setResourceKey(BasePageDocument::RESOURCE_KEY)
                    ->setFormKey('page_excerpt')
                    ->setTabTitle('sulu_page.excerpt')
                    ->setTabCondition('(nodeType == 1 || nodeType == 4) && shadowOn == false')
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->addRouterAttributesToFormRequest($routerAttributesToFormRequest)
                    ->addRouterAttributesToFormMetadata($routerAttributesToFormMetadata)
                    ->setPreviewCondition($previewCondition)
                    ->setTitleVisible(true)
                    ->setTabOrder(3072)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createPreviewFormViewBuilder('sulu_page.page_edit_form.settings', '/settings')
                    ->disablePreviewWebspaceChooser()
                    ->setResourceKey(BasePageDocument::RESOURCE_KEY)
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
                    ->createPreviewFormViewBuilder('sulu_page.page_edit_form.permissions', '/permissions')
                    ->setResourceKey('permissions')
                    ->setPreviewResourceKey(BasePageDocument::RESOURCE_KEY)
                    ->setFormKey('permission_details')
                    ->addRequestParameters(['resourceKey' => BasePageDocument::RESOURCE_KEY])
                    ->setTabCondition('_permissions.security')
                    ->setTabTitle('sulu_security.permissions')
                    ->addToolbarActions([
                        new SaveWithFormDialogToolbarAction(
                            'sulu_security.inherit_permissions_title',
                            'permission_inheritance',
                            '__parent.hasSub'
                        ),
                    ])
                    ->addRouterAttributesToFormRequest(['webspace'])
                    ->setTitleVisible(true)
                    ->setTabOrder(5120)
                    ->setParent(static::EDIT_FORM_VIEW)
            );

            if ($this->activityViewBuilderFactory->hasActivityListPermission()) {
                $activityResourceTabViewName = PageAdmin::EDIT_FORM_VIEW . '.activity';

                $viewCollection->add(
                    $this->viewBuilderFactory
                        ->createResourceTabViewBuilder($activityResourceTabViewName, '/activity')
                        ->setResourceKey(BasePageDocument::RESOURCE_KEY)
                        ->setTabOrder(6144)
                        ->setTabTitle($this->versioningEnabled ? 'sulu_admin.activity_versions' : 'sulu_admin.activity')
                        ->setParent(PageAdmin::EDIT_FORM_VIEW)
                );

                $viewCollection->add(
                    $this->activityViewBuilderFactory
                        ->createActivityListViewBuilder(
                            $activityResourceTabViewName . '.activity',
                            '/activities',
                            BasePageDocument::RESOURCE_KEY
                        )
                        ->setParent($activityResourceTabViewName)
                );

                if ($this->versioningEnabled) {
                    $viewCollection->add(
                        $this->viewBuilderFactory
                            ->createListViewBuilder($activityResourceTabViewName . '.versions', '/versions')
                            ->setTabTitle('sulu_admin.versions')
                            ->setResourceKey('page_versions')
                            ->setListKey('page_versions')
                            ->addListAdapters(['table'])
                            ->addAdapterOptions([
                                'table' => [
                                    'skin' => 'flat',
                                ],
                            ])
                            ->disableTabGap()
                            ->disableSearching()
                            ->disableSelection()
                            ->disableColumnOptions()
                            ->disableFiltering()
                            ->addRouterAttributesToListRequest(['id', 'webspace'])
                            ->addItemActions([
                                new ListItemAction('restore_version', ['success_view' => PageAdmin::EDIT_FORM_VIEW]),
                            ])
                            ->setParent($activityResourceTabViewName)
                    );
                }
            }
        }
    }

    public function getSecurityContexts()
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

            $webspaceSecuritySystemContexts[$system] = [
                static::SECURITY_CONTEXT_GROUP => [
                    static::SECURITY_CONTEXT_PREFIX . $webspace->getKey() => [
                        PermissionTypes::VIEW,
                    ],
                ],
            ];
        }

        $webspaceContexts = [];
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            /* @var Webspace $webspace */
            $webspaceContexts[self::getPageSecurityContext($webspace->getKey())] = [
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
            $webspaceSecuritySystemContexts
        );
    }

    public function getSecurityContextsWithPlaceholder()
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

            $webspaceSecuritySystemContexts[$system] = [
                static::SECURITY_CONTEXT_GROUP => [
                    static::SECURITY_CONTEXT_PREFIX . '#webspace#' => [
                        PermissionTypes::VIEW,
                    ],
                ],
            ];
        }

        return \array_merge(
            [
                static::SULU_ADMIN_SECURITY_SYSTEM => [
                    static::SECURITY_CONTEXT_GROUP => [
                        static::SECURITY_CONTEXT_PREFIX . '#webspace#' => [
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
            $webspaceSecuritySystemContexts
        );
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_page';
    }

    public function getConfig(): ?array
    {
        $webspaces = $this->webspaceManager->getWebspaceCollection()->getWebspaces();
        \uasort($webspaces, function($w1, $w2) {
            return \strcmp($w1->getName(), $w2->getName());
        });

        return [
            'teaser' => $this->teaserProviderPool->getConfiguration(),
            'versioning' => $this->versioningEnabled,
            'webspaces' => $webspaces,
        ];
    }

    private function hasSomeWebspacePermission(): bool
    {
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $hasWebspacePermission = $this->securityChecker->hasPermission(
                self::getPageSecurityContext($webspace->getKey()),
                PermissionTypes::EDIT
            );

            if ($hasWebspacePermission) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns security context for pages in given webspace.
     *
     * @final
     */
    public static function getPageSecurityContext(string $webspaceKey): string
    {
        return \sprintf('%s%s', self::SECURITY_CONTEXT_PREFIX, $webspaceKey);
    }
}
