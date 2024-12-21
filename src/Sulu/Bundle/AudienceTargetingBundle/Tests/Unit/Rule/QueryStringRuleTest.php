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
use Sulu\Bundle\AudienceTargetingBundle\Rule\QueryStringRule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class QueryStringRuleTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    public function setUp(): void
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideEvaluate')]
    public function testEvaluate($url, $urlHeader, $urlHeaderValue, $options, $result): void
    {
        $queryStringRule = new QueryStringRule($this->requestStack->reveal(), $this->translator->reveal(), $urlHeader);

        $request = Request::create($url);
        $request->headers->set($urlHeader, $urlHeaderValue);
        $this->requestStack->getCurrentRequest()->willReturn($request);
        $this->assertEquals($result, $queryStringRule->evaluate($options));
    }

    public static function provideEvaluate()
    {
        return [
            [
                'http://sulu.lo?test=asdf',
                'X-Forwarded-URL',
                null,
                ['parameter' => 'test', 'value' => 'asdf'],
                true,
            ],
            [
                'http://sulu.lo?test1=asdf&test2=jkl',
                'X-Forwarded-URL',
                null,
                ['parameter' => 'test1', 'value' => 'asdf'],
                true,
            ],
            [
                'http://sulu.lo?test1=asdf&test2=jkl',
                'X-Forwarded-URL',
                null,
                ['parameter' => 'test2', 'value' => 'jkl'],
                true,
            ],
            [
                'http://sulu.lo?test=jkl',
                'X-Forwarded-URL',
                null,
                ['parameter' => 'test', 'value' => 'asdf'],
                false,
            ],
            [
                'http://sulu.lo?test=asdf',
                'X-Forwarded-URL',
                null,
                ['parameter' => 'test1', 'value' => 'asdf'],
                false,
            ],
            [
                'http://sulu.lo?test1=asdf&test2=jkl',
                'X-Forwarded-URL',
                null,
                ['parameter' => 'test2', 'value' => 'j'],
                false,
            ],
            [
                'http://sulu.lo?test1=asdf&test2=jkl',
                'X-Forwarded-URL',
                null,
                ['parameter' => 'test3', 'value' => 'asdf'],
                false,
            ],
            [
                'http://sulu.lo',
                'X-Forwarded-URL',
                null,
                ['parameter' => 'test1', 'value' => 'asdf'],
                false,
            ],
            [
                'http://sulu.lo/_target_group_hit',
                'X-Forwarded-URL',
                'http://sulu.lo?test=asdf',
                ['parameter' => 'test', 'value' => 'asdf'],
                true,
            ],
            [
                'http://sulu.lo/_target_group_hit',
                'X-Forwarded-URL',
                'http://sulu.lo?test1=asdf',
                ['parameter' => 'test', 'value' => 'asdf'],
                false,
            ],
            [
                'http://sulu.lo/_target_group_hit?test=asdf',
                'X-Forwarded-URL',
                'http://sulu.lo?test1=asdf',
                ['parameter' => 'test', 'value' => 'asdf'],
                false,
            ],
        ];
    }
}
