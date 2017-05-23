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

use Sulu\Bundle\AudienceTargetingBundle\Rule\RuleCollection;
use Sulu\Bundle\AudienceTargetingBundle\Rule\RuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Rule\RuleNotFoundException;

class RuleCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetName()
    {
        $rule1 = $this->prophesize(RuleInterface::class);
        $rule2 = $this->prophesize(RuleInterface::class);

        $ruleCollection = new RuleCollection(['rule1' => $rule1->reveal(), 'rule2' => $rule2->reveal()]);

        $this->assertSame($rule1->reveal(), $ruleCollection->getRule('rule1'));
        $this->assertSame($rule2->reveal(), $ruleCollection->getRule('rule2'));
    }

    public function testGetNotExistingName()
    {
        $this->setExpectedException(RuleNotFoundException::class, 'The rule with the name "rule" could not be found.');
        $ruleCollection = new RuleCollection([]);

        $ruleCollection->getRule('rule');
    }
}
