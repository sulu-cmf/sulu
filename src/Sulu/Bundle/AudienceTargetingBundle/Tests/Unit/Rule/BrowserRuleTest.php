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
use Sulu\Bundle\AudienceTargetingBundle\Rule\BrowserRule;
use Symfony\Contracts\Translation\TranslatorInterface;

class BrowserRuleTest extends TestCase
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
     * @var BrowserRule
     */
    private $browserRule;

    public function setUp(): void
    {
        $this->deviceDetector = $this->prophesize(DeviceDetector::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->browserRule = new BrowserRule($this->deviceDetector->reveal(), $this->translator->reveal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideEvaluate')]
    public function testEvaluate($browserShortName, $options, $result): void
    {
        $this->deviceDetector->getClient('short_name')->willReturn($browserShortName);
        $this->assertEquals($result, $this->browserRule->evaluate($options));
    }

    public static function provideEvaluate()
    {
        return [
            ['CH', ['browser' => 'Chrome'], true],
            ['CH', ['browser' => 'Firefox'], false],
            ['FE', ['browser' => 'Chrome'], false],
            [null, ['browser' => 'Chrome'], false],
            ['FE', ['browser' => 'Firefox'], true],
            ['FE', [], false],
        ];
    }
}
