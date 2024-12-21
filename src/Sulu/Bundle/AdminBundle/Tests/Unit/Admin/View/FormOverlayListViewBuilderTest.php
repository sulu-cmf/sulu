<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\View;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\Badge;
use Sulu\Bundle\AdminBundle\Admin\View\FormOverlayListViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Component\Security\Authentication\RoleInterface;

class FormOverlayListViewBuilderTest extends TestCase
{
    public function testBuildFormOverlayListViewWithClone(): void
    {
        $routeBuilder = (new FormOverlayListViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['table']);

        $this->assertNotSame($routeBuilder->getView(), $routeBuilder->getView());
    }

    public function testBuildFormOverlayListViewWithoutResourceKey(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/"setResourceKey"/');

        $route = (new FormOverlayListViewBuilder('sulu_category.list', '/category'))
            ->getView();
    }

    public function testBuildFormOverlayListViewWithoutListAdapters(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/"addListAdapters"/');

        $route = (new FormOverlayListViewBuilder('sulu_category.list', '/category'))
            ->setResourceKey('categories')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->getView();
    }

    public static function provideBuildFormOverlayListView()
    {
        return [
            [
                'sulu_category.list',
                '/categories',
                'categories',
                'categories',
                'category_details',
                'Categories',
                ['table', 'column_list'],
                'sulu_category.add_category',
                'sulu_category.edit_category',
                'small',
                ['test1' => 'value1'],
            ],
            [
                'sulu_tag.list',
                '/tags',
                'tags',
                'other_tags',
                'tag_details',
                'Tags',
                ['table'],
                'sulu_tag.add_tag',
                'sulu_tag.edit_tag',
                'large',
                ['test2' => 'value2'],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBuildFormOverlayListView')]
    public function testBuildFormOverlayListView(
        string $name,
        string $path,
        string $resourceKey,
        string $listKey,
        string $formKey,
        string $title,
        array $listAdapters,
        string $addOverlayTitle,
        string $editOverlayTitle,
        string $overlaySize,
        array $requestParameters
    ): void {
        $route = (new FormOverlayListViewBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setListKey($listKey)
            ->setFormKey($formKey)
            ->setTitle($title)
            ->addListAdapters($listAdapters)
            ->setAddOverlayTitle($addOverlayTitle)
            ->setEditOverlayTitle($editOverlayTitle)
            ->setOverlaySize($overlaySize)
            ->addRequestParameters($requestParameters)
            ->getView();

        $this->assertEquals($name, $route->getName());
        $this->assertEquals($path, $route->getPath());
        $this->assertEquals($resourceKey, $route->getOption('resourceKey'));
        $this->assertEquals($listKey, $route->getOption('listKey'));
        $this->assertEquals($formKey, $route->getOption('formKey'));
        $this->assertEquals($title, $route->getOption('title'));
        $this->assertEquals($listAdapters, $route->getOption('adapters'));
        $this->assertEquals($addOverlayTitle, $route->getOption('addOverlayTitle'));
        $this->assertEquals($editOverlayTitle, $route->getOption('editOverlayTitle'));
        $this->assertEquals($overlaySize, $route->getOption('overlaySize'));
        $this->assertEquals($requestParameters, $route->getOption('requestParameters'));
        $this->assertEquals('sulu_admin.form_overlay_list', $route->getType());
    }

    public function testBuildFormOverlayListViewAddingAdaptersTwice(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['table', 'column_list'])
            ->addListAdapters(['tree'])
            ->getView();

        $this->assertEquals(['table', 'column_list', 'tree'], $route->getOption('adapters'));
    }

    public function testBuildListWithLocales(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getView();

        $this->assertEquals(['de', 'en', 'nl', 'fr'], $route->getOption('locales'));
        $this->assertEquals('de', $route->getAttributeDefault('locale'));
    }

    public function testBuildListWithLocalesWithoutLocalePlaceholder(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('":locale"');

        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getView();
    }

    public function testBuildListWithoutLocalesWithLocalePlaceholder(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('":locale"');

        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['table'])
            ->getView();
    }

    public function testBuildListWithoutListKey(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('"listKey"');

        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->addListAdapters(['table'])
            ->getView();
    }

    public function testBuildListWithoutFormKey(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('"formKey"');

        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table'])
            ->getView();
    }

    public function testBuildFormOverlayListViewWithSearch(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->disableSearching()
            ->enableSearching()
            ->getView();

        $this->assertTrue($route->getOption('searchable'));
    }

    public function testBuildFormOverlayListViewWithoutSearch(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->enableSearching()
            ->disableSearching()
            ->getView();

        $this->assertFalse($route->getOption('searchable'));
    }

    public function testBuildFormOverlayListViewWithSelection(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->disableSelection()
            ->enableSelection()
            ->getView();

        $this->assertTrue($route->getOption('selectable'));
    }

    public function testBuildFormOverlayListViewWithoutSelection(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->enableSelection()
            ->disableSelection()
            ->getView();

        $this->assertFalse($route->getOption('selectable'));
    }

    public function testBuildListWithRouterAttributesToListRequest(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToListRequest(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToListRequest(['locale'])
            ->getView();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToListRequest')
        );
    }

    public function testBuildFormWithRouterAttributesToFormRequest(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToFormRequest(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToFormRequest(['locale'])
            ->getView();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToFormRequest')
        );
    }

    public function testBuildFormWithRouterAttributesToFormMetadata(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToFormMetadata(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToFormMetadata(['locale'])
            ->getView();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('routerAttributesToFormMetadata')
        );
    }

    public function testBuildWithResourceStorePropertiesToListRequest(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addResourceStorePropertiesToListRequest(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addResourceStorePropertiesToListRequest(['locale'])
            ->getView();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('resourceStorePropertiesToListRequest')
        );
    }

    public function testBuildWithResourceStorePropertiesToFormRequest(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addResourceStorePropertiesToFormRequest(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addResourceStorePropertiesToFormRequest(['locale'])
            ->getView();

        $this->assertEquals(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $route->getOption('resourceStorePropertiesToFormRequest')
        );
    }

    public function testBuildListSetParent(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setParent('sulu_role.parent_view')
            ->getView();

        $this->assertEquals('sulu_role.parent_view', $route->getParent());
    }

    public function testBuildListSetOption(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setParent('sulu_role.parent_view')
            ->setOption('listKey', 'overridden_roles')
            ->getView();

        $this->assertEquals('overridden_roles', $route->getOption('listKey'));
    }

    public function testBuildListSetTabTitle(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setTabTitle('sulu_role.title')
            ->getView();

        $this->assertEquals('sulu_role.title', $route->getOption('tabTitle'));
    }

    public function testBuildListSetTabOrder(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setTabOrder(5)
            ->getView();

        $this->assertEquals(5, $route->getOption('tabOrder'));
    }

    public function testBuildListSetTabPriority(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setTabPriority(5)
            ->getView();

        $this->assertEquals(5, $route->getOption('tabPriority'));
    }

    public function testBuildListSetTabCondition(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setTabCondition('state == 1')
            ->getView();

        $this->assertEquals('state == 1', $route->getOption('tabCondition'));
    }

    public function testBuildListSetBackView(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setBackView('sulu_category.edit_form')
            ->getView();

        $this->assertEquals('sulu_category.edit_form', $route->getOption('backView'));
    }

    public function testBuildListSetItemDisabledCondition(): void
    {
        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->setItemDisabledCondition('(_permissions && !_permissions.delete)')
            ->getView();

        $this->assertSame('(_permissions && !_permissions.delete)', $route->getOption('itemDisabledCondition'));
    }

    public function testBuildAddToolbarActions(): void
    {
        $saveToolbarAction = new ToolbarAction('sulu_admin.save');
        $typesToolbarAction = new ToolbarAction('sulu_admin.types');
        $deleteToolbarAction = new ToolbarAction('sulu_admin.delete');

        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addToolbarActions([$saveToolbarAction, $typesToolbarAction])
            ->addToolbarActions([$deleteToolbarAction])
            ->getView();

        $this->assertEquals(
            [$saveToolbarAction, $typesToolbarAction, $deleteToolbarAction],
            $route->getOption('toolbarActions')
        );
    }

    public function testBuildAddItemActions(): void
    {
        $linkItemAction = new ToolbarAction('sulu_admin.link');
        $exportItemAction = new ToolbarAction('sulu_admin.export');
        $downloadItemAction = new ToolbarAction('sulu_admin.download');

        $route = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('role_details')
            ->addListAdapters(['tree'])
            ->addItemActions([$linkItemAction, $exportItemAction])
            ->addItemActions([$downloadItemAction])
            ->getView();

        $this->assertEquals(
            [$linkItemAction, $exportItemAction, $downloadItemAction],
            $route->getOption('itemActions')
        );
    }

    public function testBuildAddTabBadge(): void
    {
        $fooBadge = new Badge('sulu_foo.get_foo_badge');
        $barBadge = new Badge('sulu_bar.get_bar_badge');
        $bazBadge = (new Badge('sulu_baz.get_baz_badge', '/total', 'value != 0'))
            ->addRequestParameters([
                'limit' => 0,
                'entityClass' => 'Sulu\Bundle\BazBundle\Entity\Baz',
            ])
            ->addRouterAttributesToRequest([
                'locale',
                'id' => 'entityId',
            ]);

        $view = (new FormOverlayListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->setFormKey('roles')
            ->addListAdapters(['tree'])
            ->addTabBadges([$fooBadge, 'abc' => $barBadge])
            ->addTabBadges(['abc' => $bazBadge])
            ->getView();

        $this->assertEquals(
            [
                $fooBadge,
                'abc' => $bazBadge,
            ],
            $view->getOption('tabBadges')
        );
    }
}
