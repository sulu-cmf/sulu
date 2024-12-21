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
use Sulu\Bundle\AdminBundle\Admin\View\ResourceTabViewBuilder;
use Sulu\Component\Security\Authentication\RoleInterface;

class ResourceTabViewBuilderTest extends TestCase
{
    public function testBuildResourceTabViewWithClone(): void
    {
        $viewBuilder = (new ResourceTabViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY);

        $this->assertNotSame($viewBuilder->getView(), $viewBuilder->getView());
    }

    public function testBuildResourceTabViewWithoutResourceKey(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/"setResourceKey"/');

        $view = (new ResourceTabViewBuilder('sulu_category.list', '/category'))
            ->getView();
    }

    public static function provideBuildResourceTabView()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'sulu_category.list',
                null,
                ['webspace'],
                null,
                'title',
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
                'tags',
                null,
                null,
                ['sortColumn', 'sortOrder'],
                ['page', 'limit'],
                null,
            ],
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'sulu_category.list',
                ['webspace'],
                null,
                null,
                'title',
            ],
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'sulu_category.list',
                ['webspace', 'active' => 'id'],
                null,
                ['sortColumn', 'sortOrder'],
                'title',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBuildResourceTabView')]
    public function testBuildResourceTabView(
        string $name,
        string $path,
        string $resourceKey,
        ?string $backView,
        ?array $routerAttributesToBackView,
        ?array $routerAttributesToBlacklist1,
        ?array $routerAttributesToBlacklist2,
        ?string $titleProperty
    ): void {
        $viewBuilder = (new ResourceTabViewBuilder($name, $path))
            ->setResourceKey($resourceKey);

        if ($backView) {
            $viewBuilder->setBackView($backView);
        }

        if ($routerAttributesToBackView) {
            $viewBuilder->addRouterAttributesToBackView($routerAttributesToBackView);
        }

        if ($titleProperty) {
            $viewBuilder->setTitleProperty($titleProperty);
        }

        $expectedRouterAttributesToBlacklist = [];

        if ($routerAttributesToBlacklist1) {
            $viewBuilder->addRouterAttributesToBlacklist($routerAttributesToBlacklist1);
            $expectedRouterAttributesToBlacklist = \array_merge($expectedRouterAttributesToBlacklist, $routerAttributesToBlacklist1 ?? []);
        }

        if ($routerAttributesToBlacklist2) {
            $viewBuilder->addRouterAttributesToBlacklist($routerAttributesToBlacklist2);
            $expectedRouterAttributesToBlacklist = \array_merge($expectedRouterAttributesToBlacklist, $routerAttributesToBlacklist2 ?? []);
        }

        $view = $viewBuilder->getView();

        $this->assertSame($name, $view->getName());
        $this->assertSame($path, $view->getPath());
        $this->assertSame($resourceKey, $view->getOption('resourceKey'));
        $this->assertSame($backView, $view->getOption('backView'));
        $this->assertSame($routerAttributesToBackView, $view->getOption('routerAttributesToBackView'));
        $this->assertSame($titleProperty, $view->getOption('titleProperty'));
        $this->assertSame('sulu_admin.resource_tabs', $view->getType());
        $this->assertSame(
            $view->getOption('routerAttributesToBlacklist'),
            !empty($expectedRouterAttributesToBlacklist) ? $expectedRouterAttributesToBlacklist : null
        );
    }

    public function testBuildFormWithParent(): void
    {
        $view = (new ResourceTabViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setParent('sulu_admin.test')
            ->getView();

        $this->assertSame('sulu_admin.test', $view->getParent());
    }

    public function testBuildFormWithOption(): void
    {
        $view = (new ResourceTabViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->setOption('resourceKey', 'test')
            ->getView();

        $this->assertSame('test', $view->getOption('resourceKey'));
    }

    public function testBuildResourceTabWithLocales(): void
    {
        $view = (new ResourceTabViewBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getView();

        $this->assertSame(['de', 'en', 'nl', 'fr'], $view->getOption('locales'));
    }

    public function testBuildResourceTabWithLocalesWithoutLocalePlaceholder(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('":locale"');

        $view = (new ResourceTabViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getView();
    }

    public function testBuildResourceTabWithoutLocalesWithLocalePlaceholder(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('":locale"');

        $view = (new ResourceTabViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey(RoleInterface::RESOURCE_KEY)
            ->getView();
    }
}
