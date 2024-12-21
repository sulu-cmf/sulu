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
use Sulu\Bundle\AdminBundle\Admin\View\ListViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;
use Sulu\Component\Security\Authentication\RoleInterface;

class ListViewBuilderTest extends TestCase
{
    use ReadObjectAttributeTrait;

    public function testBuildListViewWithClone(): void
    {
        $viewBuilder = (new ListViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table']);

        $this->assertNotSame($viewBuilder->getView(), $viewBuilder->getView());
    }

    public function testBuildListViewWithoutResourceKey(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/"setResourceKey"/');

        $view = (new ListViewBuilder('sulu_category.list', '/category'))
            ->getView();
    }

    public function testBuildListViewWithoutListAdapters(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/"addListAdapters"/');

        $view = (new ListViewBuilder('sulu_category.list', '/category'))
            ->setResourceKey('categories')
            ->setListKey('roles')
            ->getView();
    }

    public static function provideBuildListView()
    {
        return [
            [
                'sulu_category.list',
                '/categories',
                'categories',
                'categories',
                null,
                'Categories',
                ['table', 'column_list'],
                'sulu_category.add_form',
                'sulu_category.edit_form',
                'webspace',
            ],
            [
                'sulu_tag.list',
                '/tags',
                'tags',
                'other_tags',
                'contact_tags',
                'Tags',
                ['table'],
                'sulu_tag.add_form',
                'sulu_tag.edit_form',
                'locale',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBuildListView')]
    public function testBuildListView(
        string $name,
        string $path,
        string $resourceKey,
        string $listKey,
        ?string $userSettingsKey,
        string $title,
        array $listAdapters,
        string $addView,
        string $editView,
        string $rerenderAttribute
    ): void {
        $viewBuilder = (new ListViewBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setListKey($listKey)
            ->setTitle($title)
            ->addListAdapters($listAdapters)
            ->setAddView($addView)
            ->setEditView($editView)
            ->addRerenderAttribute($rerenderAttribute);

        if ($userSettingsKey) {
            $viewBuilder->setUserSettingsKey($userSettingsKey);
        }

        $view = $viewBuilder->getView();

        $this->assertSame($name, $view->getName());
        $this->assertSame($path, $view->getPath());
        $this->assertSame([$rerenderAttribute], $this->readObjectAttribute($view, 'rerenderAttributes'));
        $this->assertSame($resourceKey, $view->getOption('resourceKey'));
        $this->assertSame($listKey, $view->getOption('listKey'));
        $this->assertSame($userSettingsKey, $view->getOption('userSettingsKey'));
        $this->assertSame($title, $view->getOption('title'));
        $this->assertSame($listAdapters, $view->getOption('adapters'));
        $this->assertSame($addView, $view->getOption('addView'));
        $this->assertSame($editView, $view->getOption('editView'));
        $this->assertSame('sulu_admin.list', $view->getType());
    }

    public function testBuildListViewAddingAdaptersTwice(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table', 'column_list'])
            ->addListAdapters(['tree'])
            ->getView();

        $this->assertSame(['table', 'column_list', 'tree'], $view->getOption('adapters'));
    }

    public function testBuildListWithLocales(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table'])
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->setDefaultLocale('de')
            ->getView();

        $this->assertSame(['de', 'en', 'nl', 'fr'], $view->getOption('locales'));
        $this->assertSame('de', $view->getAttributeDefault('locale'));
    }

    public function testBuildListWithLocalesWithoutLocalePlaceholder(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('":locale"');

        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
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

        $view = (new ListViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table'])
            ->getView();
    }

    public function testBuildListWithoutListKey(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('"listKey"');

        $view = (new ListViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->addListAdapters(['table'])
            ->getView();
    }

    public function testBuildListViewWithSearch(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->disableSearching()
            ->enableSearching()
            ->getView();

        $this->assertTrue($view->getOption('searchable'));
    }

    public function testBuildListViewWithoutSearch(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->enableSearching()
            ->disableSearching()
            ->getView();

        $this->assertFalse($view->getOption('searchable'));
    }

    public function testBuildListViewWithSelection(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->disableSelection()
            ->enableSelection()
            ->getView();

        $this->assertTrue($view->getOption('selectable'));
    }

    public function testBuildListViewWithoutSelection(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->enableSelection()
            ->disableSelection()
            ->getView();

        $this->assertFalse($view->getOption('selectable'));
    }

    public function testBuildListWithViewrAttributesToListRequest(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToListRequest(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToListRequest(['locale'])
            ->getView();

        $this->assertSame(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $view->getOption('routerAttributesToListRequest')
        );
    }

    public function testBuildListWithRouterAttributesToListMetadata(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addRouterAttributesToListMetadata(['webspace' => 'webspaceId', 'parent' => 'parentId', 'id' => 1])
            ->addRouterAttributesToListMetadata(['locale'])
            ->getView();

        $this->assertSame(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'id' => 1, 'locale'],
            $view->getOption('routerAttributesToListMetadata')
        );
    }

    public function testBuildListWithResourceStorePropertiesToListRequest(): void
    {
        $view = (new ListViewBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addResourceStorePropertiesToListRequest(['id' => 'dimensionId', 'parent' => 'parentId'])
            ->addResourceStorePropertiesToListRequest(['locale'])
            ->getView();

        $this->assertSame(
            ['id' => 'dimensionId', 'parent' => 'parentId', 'locale'],
            $view->getOption('resourceStorePropertiesToListRequest')
        );
    }

    public function testBuildListWithResourceStorePropertiesToMetadataRequest(): void
    {
        $view = (new ListViewBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addResourceStorePropertiesToListMetadata(['id' => 'dimensionId', 'parent' => 'parentId'])
            ->addResourceStorePropertiesToListMetadata(['locale'])
            ->getView();

        $this->assertSame(
            ['id' => 'dimensionId', 'parent' => 'parentId', 'locale'],
            $view->getOption('resourceStorePropertiesToListMetadata')
        );
    }

    public function testBuildListWithRequestParameters(): void
    {
        $view = (new ListViewBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addRequestParameters(['resourceKey' => 'pages', 'flat' => 'true'])
            ->addRequestParameters(['locale' => 'de'])
            ->getView();

        $this->assertSame(
            ['resourceKey' => 'pages', 'flat' => 'true', 'locale' => 'de'],
            $view->getOption('requestParameters')
        );
    }

    public function testBuildListSetParent(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setParent('sulu_role.parent_view')
            ->getView();

        $this->assertSame('sulu_role.parent_view', $view->getParent());
    }

    public function testBuildListSetOption(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setOption('resourceKey', 'test')
            ->getView();

        $this->assertSame('test', $view->getOption('resourceKey'));
    }

    public function testBuildListSetTabTitle(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabTitle('sulu_role.title')
            ->getView();

        $this->assertSame('sulu_role.title', $view->getOption('tabTitle'));
    }

    public function testBuildListSetTabOrder(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabOrder(5)
            ->getView();

        $this->assertSame(5, $view->getOption('tabOrder'));
    }

    public function testBuildListSetTabPriority(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabPriority(5)
            ->getView();

        $this->assertSame(5, $view->getOption('tabPriority'));
    }

    public function testBuildListSetTabCondition(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setTabCondition('state == 1')
            ->getView();

        $this->assertSame('state == 1', $view->getOption('tabCondition'));
    }

    public function testBuildListSetBackView(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setBackView('sulu_category.edit_form')
            ->getView();

        $this->assertSame('sulu_category.edit_form', $view->getOption('backView'));
    }

    public function testBuildListSetItemDisabledCondition(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->setItemDisabledCondition('(_permissions && !_permissions.delete)')
            ->getView();

        $this->assertSame('(_permissions && !_permissions.delete)', $view->getOption('itemDisabledCondition'));
    }

    public function testBuildAddToolbarActions(): void
    {
        $saveToolbarAction = new ToolbarAction('sulu_admin.save');
        $typesToolbarAction = new ToolbarAction('sulu_admin.types');
        $deleteToolbarAction = new ToolbarAction('sulu_admin.delete');

        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addToolbarActions([$saveToolbarAction, $typesToolbarAction])
            ->addToolbarActions([$deleteToolbarAction])
            ->getView();

        $this->assertSame(
            [$saveToolbarAction, $typesToolbarAction, $deleteToolbarAction],
            $view->getOption('toolbarActions')
        );
    }

    public function testBuildAddItemActions(): void
    {
        $linkItemAction = new ToolbarAction('sulu_admin.link');
        $exportItemAction = new ToolbarAction('sulu_admin.export');
        $downloadItemAction = new ToolbarAction('sulu_admin.download');

        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['tree'])
            ->addItemActions([$linkItemAction, $exportItemAction])
            ->addItemActions([$downloadItemAction])
            ->getView();

        $this->assertEquals(
            [$linkItemAction, $exportItemAction, $downloadItemAction],
            $view->getOption('itemActions')
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

        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
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

    public function testBuildAddDeprecatedAdapter(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addListAdapters(['table_light', 'tree_table_slim'])
            ->getView();

        $this->assertSame(['table', 'tree_table'], $view->getOption('adapters'));
        $this->assertSame(
            [
                'table' => ['skin' => 'light'],
                'tree_table' => ['show_header' => false],
            ], $view->getOption('adapterOptions')
        );
    }

    public function testBuildAddDeprecatedAdapterWithOptions(): void
    {
        $view = (new ListViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setListKey('roles')
            ->addAdapterOptions(['table' => ['show_header' => false], 'tree_table' => ['skin' => 'flat']])
            ->addListAdapters(['table_light', 'tree_table_slim'])
            ->getView();

        $this->assertSame(['table', 'tree_table'], $view->getOption('adapters'));
        $this->assertSame(
            [
                'table' => ['show_header' => false, 'skin' => 'light'],
                'tree_table' => ['skin' => 'flat', 'show_header' => false],
            ], $view->getOption('adapterOptions')
        );
    }
}
