<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Rule;

use DeviceDetector\DeviceDetector;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AudienceTargetingBundle\Rule\DeviceTypeRule;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeviceTypeRuleTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DeviceDetector>
     */
    private $deviceDetector;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    /**
     * @var DeviceTypeRule
     */
    private $deviceTypeRule;

    public function setUp(): void
    {
        $this->deviceDetector = $this->prophesize(DeviceDetector::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->deviceTypeRule = new DeviceTypeRule($this->deviceDetector->reveal(), $this->translator->reveal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideEvaluate')]
    public function testEvaluate($deviceType, $options, $result): void
    {
        $this->deviceDetector->isDesktop()->willReturn(DeviceTypeRule::DESKTOP === $deviceType);
        $this->deviceDetector->isTablet()->willReturn(DeviceTypeRule::TABLET === $deviceType);
        $this->deviceDetector->isSmartphone()->willReturn(DeviceTypeRule::SMARTPHONE === $deviceType);

        $this->assertSame($result, $this->deviceTypeRule->evaluate($options));
    }

    public static function provideEvaluate()
    {
        return [
            [null, ['device_type' => DeviceTypeRule::DESKTOP], false],
            [DeviceTypeRule::DESKTOP, ['device_type' => DeviceTypeRule::DESKTOP], true],
            [DeviceTypeRule::TABLET, ['device_type' => DeviceTypeRule::TABLET], true],
            [DeviceTypeRule::SMARTPHONE, ['device_type' => DeviceTypeRule::SMARTPHONE], true],
            [DeviceTypeRule::DESKTOP, ['device_type' => DeviceTypeRule::SMARTPHONE], false],
            [DeviceTypeRule::DESKTOP, [], false],
        ];
    }
}
