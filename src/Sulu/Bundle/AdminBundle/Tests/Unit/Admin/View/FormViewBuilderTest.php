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
use Sulu\Bundle\AdminBundle\Admin\View\FormViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Component\Security\Authentication\RoleInterface;

class FormViewBuilderTest extends TestCase
{
    public function testBuildFormViewWithClone(): void
    {
        $viewBuilder = (new FormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles');

        $this->assertNotSame($viewBuilder->getView(), $viewBuilder->getView());
    }

    public function testBuildFormViewWithoutResourceKey(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/"setResourceKey"/');

        $view = (new FormViewBuilder('sulu_category.edit_form.details', '/details'))
            ->getView();
    }

    public static function provideBuildFormView()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'categories',
                'Details',
                'name == "Test"',
                100,
                512,
                'sulu_category.edit_form',
                'sulu_category.list',
                null,
                null,
                null,
                ['test1' => 'value1'],
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
                'tags',
                'tags',
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'categories',
                'Details',
                'name == "Test"',
                100,
                512,
                'sulu_category.edit_form',
                'sulu_category.list',
                ['webspace'],
                ['webspaceKey' => 'webspace'],
                true,
                null,
            ],
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'categories',
                'Details',
                'name == "Test"',
                100,
                512,
                'sulu_category.edit_form',
                'sulu_category.list',
                ['webspace', 'id' => 'active'],
                ['webspace'],
                false,
                null,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBuildFormView')]
    public function testBuildFormView(
        string $name,
        string $path,
        string $resourceKey,
        string $formKey,
        ?string $tabTitle,
        ?string $tabCondition,
        ?int $tabOrder,
        ?int $tabPriority,
        ?string $editView,
        ?string $backView,
        ?array $routerAttributesToBackView,
        ?array $routerAttributesToFormMetadata,
        ?bool $titleVisible,
        ?array $requestParameters
    ): void {
        $viewBuilder = (new FormViewBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setFormKey($formKey);

        if ($tabTitle) {
            $viewBuilder->setTabTitle($tabTitle);
        }

        if ($tabCondition) {
            $viewBuilder->setTabCondition($tabCondition);
        }

        if ($tabOrder) {
            $viewBuilder->setTabOrder($tabOrder);
        }

        if ($tabPriority) {
            $viewBuilder->setTabPriority($tabPriority);
        }

        if ($editView) {
            $viewBuilder->setEditView($editView);
        }

        if ($backView) {
            $viewBuilder->setBackView($backView);
        }

        if ($routerAttributesToBackView) {
            $viewBuilder->addRouterAttributesToBackView($routerAttributesToBackView);
        }

        if ($routerAttributesToFormMetadata) {
            $viewBuilder->addRouterAttributesToFormMetadata($routerAttributesToFormMetadata);
        }

        if (null !== $titleVisible) {
            $viewBuilder->setTitleVisible($titleVisible);
        }

        if ($requestParameters) {
            $viewBuilder->addRequestParameters($requestParameters);
        }

        $view = $viewBuilder->getView();

        $this->assertSame($name, $view->getName());
        $this->assertSame($path, $view->getPath());
        $this->assertSame($resourceKey, $view->getOption('resourceKey'));
        $this->assertSame($formKey, $view->getOption('formKey'));
        $this->assertSame($tabTitle, $view->getOption('tabTitle'));
        $this->assertSame($tabCondition, $view->getOption('tabCondition'));
        $this->assertSame($tabOrder, $view->getOption('tabOrder'));
        $this->assertSame($tabPriority, $view->getOption('tabPriority'));
        $this->assertSame($editView, $view->getOption('editView'));
        $this->assertSame($backView, $view->getOption('backView'));
        $this->assertSame($routerAttributesToBackView, $view->getOption('routerAttributesToBackView'));
        $this->assertSame($routerAttributesToFormMetadata, $view->getOption('routerAttributesToFormMetadata'));
        $this->assertSame($titleVisible, $view->getOption('titleVisible'));
        $this->assertSame($requestParameters, $view->getOption('requestParameters'));
        $this->assertNull($view->getParent());
        $this->assertSame('sulu_admin.form', $view->getType());
    }

    public function testBuildFormWithToolbarActions(): void
    {
        $saveToolbarAction = new ToolbarAction('sulu_admin.save');
        $typesToolbarAction = new ToolbarAction('sulu_admin.types');
        $deleteToolbarAction = new ToolbarAction('sulu_admin.delete');

        $view = (new FormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->addToolbarActions([$saveToolbarAction, $typesToolbarAction])
            ->addToolbarActions([$deleteToolbarAction])
            ->getView();

        $this->assertSame(
            [$saveToolbarAction, $typesToolbarAction, $deleteToolbarAction],
            $view->getOption('toolbarActions')
        );
    }

    public function testBuildFormWithRouterAttributesToFormRequest(): void
    {
        $view = (new FormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->addRouterAttributesToFormRequest(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToFormRequest(['locale'])
            ->getView();

        $this->assertSame(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $view->getOption('routerAttributesToFormRequest')
        );
    }

    public function testBuildFormWithRouterAttributesToEditView(): void
    {
        $view = (new FormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->addRouterAttributesToEditView(['webspace', 'parent'])
            ->addRouterAttributesToEditView(['locale'])
            ->getView();

        $this->assertSame(
            ['webspace', 'parent', 'locale'],
            $view->getOption('routerAttributesToEditView')
        );
    }

    public function testBuildFormWithIdQueryParameter(): void
    {
        $view = (new FormViewBuilder('sulu_security.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->setIdQueryParameter('contactId')
            ->getView();

        $this->assertSame(
            'contactId',
            $view->getOption('idQueryParameter')
        );
    }

    public function testBuildFormWithParent(): void
    {
        $view = (new FormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->setParent('sulu_admin.test')
            ->getView();

        $this->assertSame('sulu_admin.test', $view->getParent());
    }

    public function testBuildFormWithOption(): void
    {
        $view = (new FormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->setOption('resourceKey', 'test')
            ->getView();

        $this->assertSame('test', $view->getOption('resourceKey'));
    }

    public function testBuildFormWithLocales(): void
    {
        $view = (new FormViewBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getView();

        $this->assertSame(['de', 'en', 'nl', 'fr'], $view->getOption('locales'));
    }

    public function testBuildFormWithLocalesWithoutLocalePlaceholder(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('":locale"');

        $view = (new FormViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getView();
    }

    public function testBuildFormWithoutLocalesWithLocalePlaceholder(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('":locale"');

        $view = (new FormViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->getView();
    }

    public function testBuildFormWithRedirectToItself(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('"editView"');

        $view = (new FormViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->setEditView('sulu_role.list')
            ->getView();
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

        $view = (new FormViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
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
