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
use Sulu\Bundle\AudienceTargetingBundle\Rule\ReferrerRule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReferrerRuleTest extends TestCase
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

    #[\PHPUnit\Framework\Attributes\DataProvider('provideEvaluationData')]
    public function testEvaluate($referrerHeader, $referrer, $options, $result): void
    {
        $referrerRule = new ReferrerRule($this->requestStack->reveal(), $this->translator->reveal(), $referrerHeader);

        $headers = [];
        if (!$referrerHeader) {
            $headers = ['HTTP_REFERER' => $referrer];
        }

        $request = new Request([], [], [], [], [], $headers);
        if ($referrerHeader) {
            $request->headers->add([$referrerHeader => $referrer]);
        }

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertEquals($result, $referrerRule->evaluate($options));
    }

    public static function provideEvaluationData()
    {
        return [
            [null, 'https://www.google.com/test', ['referrer' => 'https://www.google.com/*'], true],
            [null, 'https://www.google.com', ['referrer' => 'www.google.*'], false],
            [null, 'https://www.google.com', ['referrer' => '*.google.*'], true],
            [null, 'https://www.google.com?q=test', ['referrer' => '*.google.*q=test'], true],
            [null, 'https://www.google.com?q=test', ['referrer' => '*.google.*'], true],
            [null, 'https://www.google.com', ['referrer' => '*.google.*q=test'], false],
            [null, '', ['referrer' => '*.google.*q=test'], false],
            [null, null, ['referrer' => '*.google.*q=test'], false],
            [null, 'https://www.google.com', ['referrer' => ''], false],
            ['X-Forwarded-Referer', 'https://www.google.com', ['referrer' => 'www.google.*'], false],
            ['X-Forwarded-Referer', 'https://www.google.com/test', ['referrer' => 'https://www.google.com/*'], true],
        ];
    }

    public function testEvaluateWithBothHeaders(): void
    {
        $referrerRule = new ReferrerRule($this->requestStack->reveal(), $this->translator->reveal(), 'X-Forwarded-Referrer');

        $request = new Request([], [], [], [], [], ['HTTP_REFERER' => '/wrong/referer']);
        $request->headers->add(['X-Forwarded-Referrer' => '/right/referer']);

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertTrue($referrerRule->evaluate(['referrer' => '/right/referer']));
        $this->assertFalse($referrerRule->evaluate(['referrer' => '/wrong/referer']));
    }

    public function testEvaluateWithoutConfiguredHeader(): void
    {
        $referrerRule = new ReferrerRule($this->requestStack->reveal(), $this->translator->reveal(), 'X-Forwarded-Referrer');

        $request = new Request([], [], [], [], [], ['HTTP_REFERER' => '/right/referer']);

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertTrue($referrerRule->evaluate(['referrer' => '/right/referer']));
    }
}
