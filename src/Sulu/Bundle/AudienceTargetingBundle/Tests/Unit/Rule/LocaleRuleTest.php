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

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AudienceTargetingBundle\Rule\LocaleRule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleRuleTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<Request>
     */
    private $request;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    /**
     * @var LocaleRule
     */
    private $localeRule;

    public function setUp(): void
    {
        $this->request = $this->prophesize(Request::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());
        $this->translator = $this->prophesize(TranslatorInterface::class);

        $this->localeRule = new LocaleRule($this->requestStack->reveal(), $this->translator->reveal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideEvaluationData')]
    public function testEvaluate($languages, $options, $result): void
    {
        $this->request->getLanguages()->willReturn($languages);
        $this->assertEquals($result, $this->localeRule->evaluate($options));
    }

    public static function provideEvaluationData()
    {
        return [
            [['de'], ['locale' => 'de'], true],
            [['de'], ['locale' => 'en'], false],
            [['de', 'en'], ['locale' => 'de'], true],
            [['de_DE', 'en'], ['locale' => 'de'], true],
            [['en_US', 'en'], ['locale' => 'de'], false],
            [[], ['locale' => 'de'], false],
            [['en_US', 'en'], [], false],
            [[], [], false],
            [['de'], ['locale' => 'DE'], true],
            [['de_DE'], ['locale' => 'DE'], true],
        ];
    }
}
