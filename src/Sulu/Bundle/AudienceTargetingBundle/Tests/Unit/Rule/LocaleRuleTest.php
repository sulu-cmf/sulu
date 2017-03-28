<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Rule;

use Sulu\Bundle\AudienceTargetingBundle\Rule\LocaleRule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LocaleRuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var LocaleRule
     */
    private $localeRule;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());

        $this->localeRule = new LocaleRule($this->requestStack->reveal());
    }

    /**
     * @dataProvider provideEvaluationData
     */
    public function testEvaluate($languages, $options, $result)
    {
        $this->request->getLanguages()->willReturn($languages);
        $this->assertEquals($result, $this->localeRule->evaluate($options));
    }

    public function provideEvaluationData()
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
        ];
    }
}
