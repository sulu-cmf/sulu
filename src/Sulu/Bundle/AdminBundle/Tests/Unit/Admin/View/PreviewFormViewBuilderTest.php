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
use Sulu\Bundle\AdminBundle\Admin\View\PreviewFormViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Component\Security\Authentication\RoleInterface;

class PreviewFormViewBuilderTest extends TestCase
{
    public function testBuildPreviewFormViewWithClone(): void
    {
        $viewBuilder = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles');

        $this->assertNotSame($viewBuilder->getView(), $viewBuilder->getView());
    }

    public function testBuildPreviewFormViewWithoutResourceKey(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/"setResourceKey"/');

        $view = (new PreviewFormViewBuilder('sulu_category.edit_form.details', '/details'))
            ->getView();
    }

    public static function provideBuildPreviewFormView()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                null,
                'categories',
                'Details',
                'name == "Test"',
                100,
                512,
                'sulu_category.edit_form',
                'sulu_category.list',
                false,
                ['test1' => 'value1'],
                true,
                null,
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
                'tags',
                'tag_contents',
                'tags',
                null,
                null,
                null,
                null,
                null,
                null,
                true,
                null,
                false,
                ['webspace'],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBuildPreviewFormView')]
    public function testBuildPreviewFormView(
        string $name,
        string $path,
        string $resourceKey,
        ?string $previewResourceKey,
        string $formKey,
        ?string $tabTitle,
        ?string $tabCondition,
        ?int $tabOrder,
        ?int $tabPriority,
        ?string $editView,
        ?string $backView,
        ?bool $titleVisible,
        ?array $requestParameters,
        ?bool $disablePreviewWebspaceChooser,
        ?array $routerAttributesToFormMetadata
    ): void {
        $viewBuilder = (new PreviewFormViewBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setFormKey($formKey);

        if ($previewResourceKey) {
            $viewBuilder->setPreviewResourceKey($previewResourceKey);
        }

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

        if (null !== $titleVisible) {
            $viewBuilder->setTitleVisible($titleVisible);
        }

        if ($requestParameters) {
            $viewBuilder->addRequestParameters($requestParameters);
        }

        if ($disablePreviewWebspaceChooser) {
            $viewBuilder->disablePreviewWebspaceChooser();
        }

        if ($routerAttributesToFormMetadata) {
            $viewBuilder->addRouterAttributesToFormMetadata($routerAttributesToFormMetadata);
        }

        $view = $viewBuilder->getView();

        $this->assertSame($name, $view->getName());
        $this->assertSame($path, $view->getPath());
        $this->assertSame($resourceKey, $view->getOption('resourceKey'));
        $this->assertSame($previewResourceKey, $view->getOption('previewResourceKey'));
        $this->assertSame($formKey, $view->getOption('formKey'));
        $this->assertSame($tabTitle, $view->getOption('tabTitle'));
        $this->assertSame($tabCondition, $view->getOption('tabCondition'));
        $this->assertSame($tabOrder, $view->getOption('tabOrder'));
        $this->assertSame($tabPriority, $view->getOption('tabPriority'));
        $this->assertSame($editView, $view->getOption('editView'));
        $this->assertSame($backView, $view->getOption('backView'));
        $this->assertSame($titleVisible, $view->getOption('titleVisible'));
        $this->assertSame($requestParameters, $view->getOption('requestParameters'));
        $this->assertNull($view->getParent());
        $this->assertSame('sulu_admin.preview_form', $view->getType());
        $this->assertSame($routerAttributesToFormMetadata, $view->getOption('routerAttributesToFormMetadata'));

        if ($disablePreviewWebspaceChooser) {
            $this->assertFalse($view->getOption('previewWebspaceChooser'));
        } else {
            $this->assertNull($view->getOption('previewWebspaceChooser'));
        }
    }

    public function testBuildFormWithToolbarActions(): void
    {
        $saveToolbarAction = new ToolbarAction('sulu_admin.save');
        $typesToolbarAction = new ToolbarAction('sulu_admin.types');
        $deleteToolbarAction = new ToolbarAction('sulu_admin.delete');

        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
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
        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
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
        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
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
        $view = (new PreviewFormViewBuilder('sulu_security.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->setIdQueryParameter('contactId')
            ->getView();

        $this->assertSame(
            'contactId',
            $view->getOption('idQueryParameter')
        );
    }

    public function testBuildFormWithPreviewCondition(): void
    {
        $view = (new PreviewFormViewBuilder('sulu_page.page_edit_form.details', '/pages/:id/details'))
            ->setResourceKey('pages')
            ->setFormKey('pages')
            ->setPreviewCondition('nodeType == 1')
            ->getView();

        $this->assertSame('nodeType == 1', $view->getOption('previewCondition'));
    }

    public function testBuildFormWithParent(): void
    {
        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->setParent('sulu_admin.test')
            ->getView();

        $this->assertSame('sulu_admin.test', $view->getParent());
    }

    public function testBuildFormWithOption(): void
    {
        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->setOption('resourceKey', 'test')
            ->getView();

        $this->assertSame('test', $view->getOption('resourceKey'));
    }

    public function testBuildFormWithLocales(): void
    {
        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles/:locale'))
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

        $view = (new PreviewFormViewBuilder('sulu_role.list', '/roles'))
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

        $view = (new PreviewFormViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setFormKey('roles')
            ->getView();
    }

    public function testBuildFormWithRedirectToItself(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('"editView"');

        $view = (new PreviewFormViewBuilder('sulu_role.list', '/roles'))
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

        $view = (new PreviewFormViewBuilder('sulu_role.list', '/roles'))
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
