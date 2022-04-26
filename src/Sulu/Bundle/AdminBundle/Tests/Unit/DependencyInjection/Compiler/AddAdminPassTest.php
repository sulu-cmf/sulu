<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddAdminPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class AddAdminPassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess(): void
    {
        $adminPass = new AddAdminPass();

        $poolDefinition = $this->prophesize(Definition::class);

        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition(AddAdminPass::ADMIN_POOL_DEFINITION_ID)
            ->willReturn(true)
            ->shouldBeCalled();
        $container->getDefinition(AddAdminPass::ADMIN_POOL_DEFINITION_ID)
            ->willReturn($poolDefinition->reveal())
            ->shouldBeCalled();

        $adminDefinition1 = $this->prophesize(Definition::class);
        $adminDefinition1->getClass()
            ->willReturn(TestAdmin1::class)
            ->shouldBeCalled();

        $adminDefinition2 = $this->prophesize(Definition::class);
        $adminDefinition2->getClass()
            ->willReturn('%class2%')
            ->shouldBeCalled();

        $container->findTaggedServiceIds(AddAdminPass::ADMIN_TAG)->willReturn(
            [
                'test_admin1' => [],
                'test_admin2' => [],
            ]
        )->shouldBeCalled();

        $container->getDefinition('test_admin1')
            ->willReturn($adminDefinition1->reveal())
            ->shouldBeCalled();
        $container->getDefinition('test_admin2')
            ->willReturn($adminDefinition2->reveal())
            ->shouldBeCalled();

        $parameterBag = $this->prophesize(ParameterBag::class);
        $container->getParameterBag()
            ->willReturn($parameterBag->reveal())
            ->shouldBeCalled();

        $parameterBag->resolveValue(TestAdmin1::class)
            ->willReturn(TestAdmin1::class)
            ->shouldBeCalled();
        $parameterBag->resolveValue('%class2%')
            ->willReturn(TestAdmin2::class)
            ->shouldBeCalled();

        $admins = [];

        $poolDefinition->addMethodCall('addAdmin', [$adminDefinition1->reveal()])->will(
            function() use (&$admins, $adminDefinition1) {
                $admins[] = $adminDefinition1;
            }
        )->shouldBeCalled();

        $poolDefinition->addMethodCall('addAdmin', [$adminDefinition2->reveal()])->will(
            function() use (&$admins, $adminDefinition2) {
                $admins[] = $adminDefinition2;
            }
        )->shouldBeCalled();

        $adminPass->process($container->reveal());

        $this->assertEquals([$adminDefinition1, $adminDefinition2], $admins);
    }

    public function testProcessOne(): void
    {
        $adminPass = new AddAdminPass();

        $poolDefinition = $this->prophesize(Definition::class);

        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition(AddAdminPass::ADMIN_POOL_DEFINITION_ID)
            ->willReturn(true)
            ->shouldBeCalled();
        $container->getDefinition(AddAdminPass::ADMIN_POOL_DEFINITION_ID)
            ->willReturn($poolDefinition->reveal())
            ->shouldBeCalled();

        $adminDefinition1 = $this->prophesize(Definition::class);
        $adminDefinition1->getClass()
            ->willReturn(TestAdmin1::class)
            ->shouldBeCalled();

        $container->findTaggedServiceIds(AddAdminPass::ADMIN_TAG)->willReturn(
            [
                'test_admin1' => [],
            ]
        )->shouldBeCalled();

        $container->getDefinition('test_admin1')
            ->willReturn($adminDefinition1->reveal())
            ->shouldBeCalled();

        $parameterBag = $this->prophesize(ParameterBag::class);
        $container->getParameterBag()
            ->willReturn($parameterBag->reveal())
            ->shouldBeCalled();

        $parameterBag->resolveValue(TestAdmin1::class)->willReturn(TestAdmin1::class);

        $admins = [];

        $poolDefinition->addMethodCall('addAdmin', [$adminDefinition1->reveal()])->will(
            function() use (&$admins, $adminDefinition1) {
                $admins[] = $adminDefinition1;
            }
        )->shouldBeCalled();

        $adminPass->process($container->reveal());

        $this->assertEquals([$adminDefinition1], $admins);
    }
}

class TestAdmin1 extends Admin
{
    public static function getPriority(): int
    {
        return 1;
    }
}

class TestAdmin2 extends Admin
{
    public static function getPriority(): int
    {
        return -1;
    }
}
