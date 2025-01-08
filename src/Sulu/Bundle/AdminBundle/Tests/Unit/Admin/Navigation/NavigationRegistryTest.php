<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\Navigation;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\View\View;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class NavigationRegistryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var NavigationRegistry
     */
    protected $navigationRegistry;

    /**
     * @var ObjectProphecy<ViewRegistry>
     */
    protected $viewRegistry;

    /**
     * @var ObjectProphecy<AdminPool>
     */
    protected $adminPool;

    /**
     * @var ObjectProphecy<Admin>
     */
    protected $admin1;

    /**
     * @var ObjectProphecy<Admin>
     */
    protected $admin2;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    protected $translator;

    public function setUp(): void
    {
        $this->admin1 = $this->prophesize(Admin::class);
        $this->admin2 = $this->prophesize(Admin::class);

        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->adminPool->getAdmins()->willReturn([$this->admin1, $this->admin2]);

        $this->translator = $this->prophesize(TranslatorInterface::class);

        $this->viewRegistry = $this->prophesize(ViewRegistry::class);

        $this->navigationRegistry = new NavigationRegistry(
            $this->translator->reveal(),
            $this->adminPool->reveal(),
            $this->viewRegistry->reveal()
        );
    }

    public function testGetNavigation(): void
    {
        $navigationItem1 = new NavigationItem('sulu_snippet.snippets');
        $navigationItem1->setView('view1');
        $navigationItem1->setPosition(10);

        $this->admin1->configureNavigationItems(Argument::any())->will(function($arguments) use ($navigationItem1): void {
            $arguments[0]->add($navigationItem1);
        });

        $navigationItem2 = new NavigationItem('sulu_admin.settings');
        $navigationItem2->setPosition(20);
        $navigationChildItem1 = new NavigationItem('sulu_category.categories');
        $navigationChildItem1->setView('view2_child1');
        $navigationChildItem2 = new NavigationItem('sulu_tag.tags');
        $navigationChildItem2->setView('view2_child2');
        $navigationItem2->addChild($navigationChildItem1);
        $navigationItem2->addChild($navigationChildItem2);

        $this->admin2->configureNavigationItems(Argument::any())->will(function($arguments) use ($navigationItem2): void {
            $arguments[0]->add($navigationItem2);
        });

        $view1 = $this->prophesize(View::class);
        $view1->getPath()->willReturn('/view1');
        $view1->getName()->willReturn('view1');

        $view2Child1 = $this->prophesize(View::class);
        $view2Child1->getPath()->willReturn('/view2-child1');
        $view2Child1->getName()->willReturn('view2_child1');

        $view2Child2 = $this->prophesize(View::class);
        $view2Child2->getPath()->willReturn('/view2-child2');
        $view2Child2->getName()->willReturn('view2_child2');

        $view2Child2Details = $this->prophesize(View::class);
        $view2Child2Details->getPath()->willReturn('/view2-child2/details');
        $view2Child2Details->getName()->willReturn('view2_child2_details');

        $this->viewRegistry->getViews()->willReturn(
            [
                $view1->reveal(),
                $view2Child1->reveal(),
                $view2Child2->reveal(),
                $view2Child2->reveal(),
            ]
        );

        $this->viewRegistry->findViewByName('view1')->shouldBeCalled()->willReturn($view1->reveal());
        $this->viewRegistry->findViewByName('view2_child1')->shouldBeCalled()
            ->willReturn($view2Child1->reveal());
        $this->viewRegistry->findViewByName('view2_child2')->shouldBeCalled()
            ->willReturn($view2Child2->reveal());

        $this->translator->trans('sulu_snippet.snippets', [], 'admin')->willReturn('Snippets');
        $this->translator->trans('sulu_admin.settings', [], 'admin')->willReturn('Settings');
        $this->translator->trans('sulu_category.categories', [], 'admin')->willReturn('Categories');
        $this->translator->trans('sulu_tag.tags', [], 'admin')->willReturn('Tags');

        $navigationItems = $this->navigationRegistry->getNavigationItems();
        $this->assertCount(2, $navigationItems);
        $this->assertEquals('Snippets', $navigationItems[0]->getLabel());
        $this->assertEquals('Settings', $navigationItems[1]->getLabel());

        // check for children of first navigation
        $this->assertCount(2, $navigationItems[1]->getChildren());
        $this->assertEquals(
            'Categories',
            $navigationItems[1]->getChildren()[0]->getLabel()
        );
        // check for created child views
        $this->assertCount(
            1,
            $navigationItems[1]->getChildren()[0]->getChildViews()
        );
        $this->assertEquals(
            'view2_child1',
            $navigationItems[1]->getChildren()[0]->getChildViews()[0]
        );
        // check for "Navigation 2 - Child 2"
        $this->assertEquals(
            'Tags',
            $navigationItems[1]->getChildren()[1]->getLabel()
        );
    }

    public function testGetNavigationMemoryCache(): void
    {
        $navigationItem1 = new NavigationItem('navigation_1');
        $navigationItem1->setView('view1');

        $this->admin1->configureNavigationItems(Argument::any())->will(function($arguments) use ($navigationItem1): void {
            $arguments[0]->add($navigationItem1);
        })->shouldBeCalledTimes(1);

        $this->admin2->configureNavigationItems(Argument::any())->will(function($arguments) use ($navigationItem1): void {
            $arguments[0]->add($navigationItem1);
        })->shouldBeCalledTimes(1);

        $view1 = $this->prophesize(View::class);
        $view1->getPath()->willReturn('/view1');
        $view1->getName()->willReturn('view1');

        $this->viewRegistry->getViews()->willReturn([$view1->reveal()])
            ->shouldBeCalledTimes(1);

        $this->viewRegistry->findViewByName('view1')->shouldBeCalled()
            ->willReturn($view1->reveal())->shouldBeCalledTimes(1);

        $this->translator->trans(Argument::cetera())
            ->willReturnArgument(0);

        $this->navigationRegistry->getNavigationItems();
    }

    public function testGetNavigationWithChildren(): void
    {
        $navigationItem1 = new NavigationItem('navigation_1');
        $navigationItem1->setView('view1');

        $view1 = $this->prophesize(View::class);
        $view1->getPath()->willReturn('/view1');
        $view1->getName()->willReturn('view1');

        $view11 = $this->prophesize(View::class);
        $view11->getPath()->willReturn('/view1/child1');
        $view11->getName()->willReturn('view11');

        $view21 = $this->prophesize(View::class);
        $view21->getPath()->willReturn('/view2/view1');
        $view21->getName()->willReturn('view2_1');

        $this->admin1->configureNavigationItems(Argument::any())->will(function($arguments) use ($navigationItem1): void {
            $arguments[0]->add($navigationItem1);
        });

        $this->viewRegistry->getViews()->willReturn([$view1, $view11, $view21]);
        $this->viewRegistry->findViewByName('view1')->willReturn($view1);

        $this->translator->trans(Argument::cetera())
            ->willReturnArgument(0);

        $navigation = $this->navigationRegistry->getNavigationItems();

        $this->assertEquals(['view1', 'view11'], $navigation[0]->getChildViews());
    }

    public function testGetNavigationWithChildrenSlashOnly(): void
    {
        $navigationItem1 = new NavigationItem('navigation_1');
        $navigationItem1->setView('view1');

        $navigationItem2 = new NavigationItem('navigation_2');
        $navigationItem2->setView('view2');

        $this->admin1->configureNavigationItems(Argument::any())
             ->will(function($arguments) use ($navigationItem1, $navigationItem2): void {
                 $arguments[0]->add($navigationItem1);
                 $arguments[0]->add($navigationItem2);
             });

        $view1 = $this->prophesize(View::class);
        $view1->getPath()->willReturn('/');
        $view1->getName()->willReturn('view1');

        $view2 = $this->prophesize(View::class);
        $view2->getPath()->willReturn('/view2');
        $view2->getName()->willReturn('view2');

        $this->viewRegistry->getViews()->willReturn([$view1, $view2]);
        $this->viewRegistry->findViewByName('view1')->willReturn($view1);
        $this->viewRegistry->findViewByName('view2')->willReturn($view2);

        $this->translator->trans(Argument::cetera())
            ->willReturnArgument(0);

        $navigationItems = $this->navigationRegistry->getNavigationItems();

        $this->assertEquals([], $navigationItems[0]->getChildViews());
        $this->assertEquals(['view2'], $navigationItems[1]->getChildViews());
    }
}
