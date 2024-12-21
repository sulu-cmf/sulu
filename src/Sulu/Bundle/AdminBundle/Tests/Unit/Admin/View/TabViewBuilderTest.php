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
use Sulu\Bundle\AdminBundle\Admin\View\TabViewBuilder;

class TabViewBuilderTest extends TestCase
{
    public function testBuildTabViewWithClone(): void
    {
        $viewBuilder = (new TabViewBuilder('sulu_role.add_form', '/roles'));

        $this->assertNotSame($viewBuilder->getView(), $viewBuilder->getView());
    }

    public static function provideBuildTabView()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
                ['webspace'],
                null,
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
                ['sortColumn', 'sortOrder'],
                ['page', 'limit'],
            ],
            [
                'sulu_category.add_form',
                '/categories/add',
                null,
                null,
            ],
            [
                'sulu_category.add_form',
                '/categories/add',
                null,
                ['sortColumn', 'sortOrder'],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBuildTabView')]
    public function testBuildTabView(
        string $name,
        string $path,
        ?array $routerAttributesToBlacklist1,
        ?array $routerAttributesToBlacklist2
    ): void {
        $viewBuilder = new TabViewBuilder($name, $path);

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
        $this->assertSame('sulu_admin.tabs', $view->getType());
        $this->assertSame(
            $view->getOption('routerAttributesToBlacklist'),
            !empty($expectedRouterAttributesToBlacklist) ? $expectedRouterAttributesToBlacklist : null
        );
    }

    public function testBuildFormWithParent(): void
    {
        $view = (new TabViewBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setParent('sulu_admin.test')
            ->getView();

        $this->assertSame('sulu_admin.test', $view->getParent());
    }

    public function testBuildFormWithOption(): void
    {
        $view = (new TabViewBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setOption('resourceKey', 'test')
            ->getView();

        $this->assertSame('test', $view->getOption('resourceKey'));
    }
}
