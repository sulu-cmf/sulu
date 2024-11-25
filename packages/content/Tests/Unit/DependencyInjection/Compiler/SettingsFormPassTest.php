<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;
use Sulu\Bundle\ContentBundle\DependencyInjection\Compiler\SettingsFormPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SettingsFormPassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess(): void
    {
        $directories = [
            __DIR__ . \DIRECTORY_SEPARATOR . 'SettingsForms',
        ];

        $container = $this->prophesize(ContainerBuilder::class);
        $container->getParameter('sulu_admin.forms.directories')
            ->shouldBeCalled()
            ->willReturn($directories);

        $container->setParameter('sulu_content.content_settings_forms', [
            'content_settings_seo' => [
                'instanceOf' => SeoInterface::class,
                'priority' => 70,
            ],
            'content_settings_author' => [
                'instanceOf' => AuthorInterface::class,
                'priority' => 50,
            ],
        ])->shouldBeCalled();

        $settingsFormPass = new SettingsFormPass();
        $settingsFormPass->process($container->reveal());
    }

    public function testProcessNoInstanceOf(): void
    {
        $directories = [
            __DIR__ . \DIRECTORY_SEPARATOR . 'InvalidSettingsForms' . \DIRECTORY_SEPARATOR . 'NoInterface',
        ];

        $container = $this->prophesize(ContainerBuilder::class);
        $container->getParameter('sulu_admin.forms.directories')
            ->shouldBeCalled()
            ->willReturn($directories);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tags with the name "sulu_content.content_settings_form" must have a valid "instanceOf" attribute!');

        $settingsFormPass = new SettingsFormPass();
        $settingsFormPass->process($container->reveal());
    }

    public function testProcessNoKey(): void
    {
        $directories = [
            __DIR__ . \DIRECTORY_SEPARATOR . 'InvalidSettingsForms' . \DIRECTORY_SEPARATOR . 'NoKey',
        ];

        $container = $this->prophesize(ContainerBuilder::class);
        $container->getParameter('sulu_admin.forms.directories')
            ->shouldBeCalled()
            ->willReturn($directories);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Forms must have a valid "key" element!');

        $settingsFormPass = new SettingsFormPass();
        $settingsFormPass->process($container->reveal());
    }

    public function testProcessInvalidTag(): void
    {
        $directories = [
            __DIR__ . \DIRECTORY_SEPARATOR . 'InvalidSettingsForms' . \DIRECTORY_SEPARATOR . 'InvalidTag',
        ];

        $container = $this->prophesize(ContainerBuilder::class);
        $container->getParameter('sulu_admin.forms.directories')
            ->shouldBeCalled()
            ->willReturn($directories);

        $container->setParameter('sulu_content.content_settings_forms', [])
            ->shouldBeCalled();

        $settingsFormPass = new SettingsFormPass();
        $settingsFormPass->process($container->reveal());
    }
}
