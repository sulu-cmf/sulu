<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Admin;

use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactoryInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

/**
 * Admin for snippet.
 */
class SnippetAdmin extends Admin
{
    public const SECURITY_CONTEXT = 'sulu.global.snippets';

    public const LIST_VIEW = 'sulu_snippet.list';

    public const ADD_FORM_VIEW = 'sulu_snippet.add_form';

    public const EDIT_FORM_VIEW = 'sulu_snippet.edit_form';

    /**
     * Returns security context for default-snippets in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    public static function getDefaultSnippetsSecurityContext($webspaceKey)
    {
        return \sprintf('%s%s.%s', PageAdmin::SECURITY_CONTEXT_PREFIX, $webspaceKey, 'default-snippets');
    }

    /**
     * @param bool $defaultEnabled
     */
    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private SecurityCheckerInterface $securityChecker,
        private WebspaceManagerInterface $webspaceManager,
        private $defaultEnabled,
        private ActivityViewBuilderFactoryInterface $activityViewBuilderFactory,
        private ReferenceViewBuilderFactoryInterface $referenceViewBuilderFactory
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $snippet = new NavigationItem('sulu_snippet.snippets');
            $snippet->setPosition(20);
            $snippet->setIcon('su-snippet');
            $snippet->setView(static::LIST_VIEW);

            $navigationItemCollection->add($snippet);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $snippetLocales = $this->webspaceManager->getAllLocales();

        $formToolbarActionsWithType = [];
        $formToolbarActionsWithoutType = [];
        $listToolbarActions = [];
        $editDropdownToolbarActions = [];

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.add');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $formToolbarActionsWithoutType[] = new ToolbarAction('sulu_admin.save');
            $formToolbarActionsWithType[] = new ToolbarAction('sulu_admin.save');
            $formToolbarActionsWithType[] = new ToolbarAction('sulu_admin.type', ['sort_by' => 'title']);

            $editDropdownToolbarActions[] = new ToolbarAction('sulu_admin.copy', [
                'visible_condition' => '!!id',
            ]);
            if (1 < \count($snippetLocales)) {
                $editDropdownToolbarActions[] = new ToolbarAction('sulu_admin.copy_locale', [
                    'visible_condition' => '!!id',
                ]);
            }
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $formToolbarActionsWithType[] = new ToolbarAction('sulu_admin.delete');
            $listToolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listToolbarActions[] = new ToolbarAction('sulu_admin.export');
        }

        if (0 !== \count($editDropdownToolbarActions)) {
            $formToolbarActionsWithType[] = new DropdownToolbarAction(
                'sulu_admin.edit',
                'su-pen',
                $editDropdownToolbarActions,
            );
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->viewBuilderFactory->createListViewBuilder(static::LIST_VIEW, '/snippets/:locale')
                    ->setResourceKey(SnippetDocument::RESOURCE_KEY)
                    ->setListKey(SnippetDocument::LIST_KEY)
                    ->setTitle('sulu_snippet.snippets')
                    ->addListAdapters(['table'])
                    ->addLocales($snippetLocales)
                    ->setAddView(static::ADD_FORM_VIEW)
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->addToolbarActions($listToolbarActions)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::ADD_FORM_VIEW, '/snippets/:locale/add')
                    ->setResourceKey(SnippetDocument::RESOURCE_KEY)
                    ->addLocales($snippetLocales)
                    ->setBackView(static::LIST_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder('sulu_snippet.add_form.details', '/details')
                    ->setResourceKey(SnippetDocument::RESOURCE_KEY)
                    ->setFormKey('snippet')
                    ->setTabTitle('sulu_admin.details')
                    ->setEditView(static::EDIT_FORM_VIEW)
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->setParent(static::ADD_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::EDIT_FORM_VIEW, '/snippets/:locale/:id')
                    ->setResourceKey(SnippetDocument::RESOURCE_KEY)
                    ->addLocales($snippetLocales)
                    ->setBackView(static::LIST_VIEW)
                    ->setTitleProperty('title')
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder('sulu_snippet.edit_form.details', '/details')
                    ->setResourceKey(SnippetDocument::RESOURCE_KEY)
                    ->setFormKey('snippet')
                    ->setTabTitle('sulu_admin.details')
                    ->addToolbarActions($formToolbarActionsWithType)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createFormViewBuilder('sulu_snippet.edit_form.taxonomies', '/taxonomies')
                    ->setResourceKey(SnippetDocument::RESOURCE_KEY)
                    ->setFormKey('snippet_taxonomies')
                    ->setTabTitle('sulu_snippet.taxonomies')
                    ->addToolbarActions($formToolbarActionsWithoutType)
                    ->setTitleVisible(true)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
        }

        if ($this->hasSomeDefaultSnippetPermission()) {
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createViewBuilder('sulu_snippet.snippet_areas', '/snippet-areas', 'sulu_snippet.snippet_areas')
                    ->setOption('snippetEditView', static::EDIT_FORM_VIEW)
                    ->setOption('tabTitle', 'sulu_snippet.default_snippets')
                    ->setOption('tabOrder', 3072)
                    ->setParent(PageAdmin::WEBSPACE_TABS_VIEW)
                    ->addRerenderAttribute('webspace')
            );
        }

        if (($this->activityViewBuilderFactory->hasActivityListPermission() || $this->referenceViewBuilderFactory->hasReferenceListPermission()) && $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $insightsResourceTabViewName = SnippetAdmin::EDIT_FORM_VIEW . '.insights';

            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder($insightsResourceTabViewName, '/insights')
                    ->setResourceKey(SnippetDocument::RESOURCE_KEY)
                    ->setTabOrder(6144)
                    ->setTabTitle('sulu_admin.insights')
                    ->setTitleProperty('')
                    ->setParent(SnippetAdmin::EDIT_FORM_VIEW)
            );

            if ($this->activityViewBuilderFactory->hasActivityListPermission()) {
                $viewCollection->add(
                    $this->activityViewBuilderFactory
                        ->createActivityListViewBuilder(
                            $insightsResourceTabViewName . '.activity',
                            '/activities',
                            SnippetDocument::RESOURCE_KEY
                        )
                        ->setParent($insightsResourceTabViewName)
                );
            }

            if ($this->referenceViewBuilderFactory->hasReferenceListPermission()) {
                $viewCollection->add(
                    $this->referenceViewBuilderFactory
                        ->createReferenceListViewBuilder(
                            $insightsResourceTabViewName . '.reference',
                            '/references',
                            SnippetDocument::RESOURCE_KEY
                        )
                        ->setParent($insightsResourceTabViewName)
                );
            }
        }
    }

    public function getSecurityContexts()
    {
        $contexts = $this->getGlobalSnippetsSecurityContext();

        if ($this->defaultEnabled) {
            $webspaceContexts = [];
            /* @var Webspace $webspace */
            foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
                $webspaceContexts[self::getDefaultSnippetsSecurityContext($webspace->getKey())] = [
                    PermissionTypes::VIEW,
                    PermissionTypes::EDIT,
                ];
            }

            $contexts[self::SULU_ADMIN_SECURITY_SYSTEM]['Webspaces'] = $webspaceContexts;
        }

        return $contexts;
    }

    private function hasSomeDefaultSnippetPermission(): bool
    {
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            if ($this->securityChecker->hasPermission(
                self::getDefaultSnippetsSecurityContext($webspace->getKey()),
                PermissionTypes::EDIT
            )) {
                return true;
            }
        }

        return false;
    }

    public function getSecurityContextsWithPlaceholder()
    {
        $contexts = $this->getGlobalSnippetsSecurityContext();

        if ($this->defaultEnabled) {
            $webspaceContexts[self::getDefaultSnippetsSecurityContext('#webspace#')] = [
                PermissionTypes::VIEW,
                PermissionTypes::EDIT,
            ];

            $contexts[self::SULU_ADMIN_SECURITY_SYSTEM]['Webspaces'] = $webspaceContexts;
        }

        return $contexts;
    }

    private function getGlobalSnippetsSecurityContext()
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Global' => [
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
