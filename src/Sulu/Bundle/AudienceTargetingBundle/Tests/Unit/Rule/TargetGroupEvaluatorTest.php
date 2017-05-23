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

use Prophecy\Argument;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupCondition;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRule;
use Sulu\Bundle\AudienceTargetingBundle\Rule\RuleCollectionInterface;
use Sulu\Bundle\AudienceTargetingBundle\Rule\RuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluator;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;

class TargetGroupEvaluatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RuleCollectionInterface
     */
    private $ruleCollection;

    /**
     * @var TargetGroupRepositoryInterface
     */
    private $targetGroupRepository;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var TargetGroupEvaluator
     */
    private $targetGroupEvaluator;

    public function setUp()
    {
        $this->ruleCollection = $this->prophesize(RuleCollectionInterface::class);
        $this->targetGroupRepository = $this->prophesize(TargetGroupRepositoryInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->targetGroupEvaluator = new TargetGroupEvaluator(
            $this->ruleCollection->reveal(),
            $this->targetGroupRepository->reveal(),
            $this->requestAnalyzer->reveal()
        );
    }

    /**
     * @dataProvider provideEvaluationData
     */
    public function testEvaluate($targetGroups, $ruleWhitelists, $webspaceKey, $evaluatedTargetGroup)
    {
        $webspace = new Webspace();
        $webspace->setKey($webspaceKey);
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $rules = [];
        foreach ($ruleWhitelists as $ruleName => $ruleWhitelist) {
            $rules[$ruleName] = $this->prophesize(RuleInterface::class);
            $rules[$ruleName]->evaluate(Argument::any())->will(function($arguments) use ($ruleWhitelist) {
                return in_array($arguments[0], $ruleWhitelist);
            });
        }
        $this->ruleCollection->getRule(Argument::any())->will(function($arguments) use ($rules) {
            return $rules[$arguments[0]];
        });

        $this->targetGroupRepository->findAllActiveForWebspaceOrderedByPriority($webspaceKey)->willReturn($targetGroups);

        $this->assertEquals($evaluatedTargetGroup, $this->targetGroupEvaluator->evaluate());
    }

    public function provideEvaluationData()
    {
        $targetGroup1 = new TargetGroup();

        $targetGroup2 = new TargetGroup();
        $targetGroupRule2 = new TargetGroupRule();
        $targetGroupCondition2 = new TargetGroupCondition();
        $targetGroupCondition2->setType('rule1');
        $targetGroupCondition2->setCondition(['targetGroup2']);
        $targetGroupRule2->addCondition($targetGroupCondition2);
        $targetGroup2->addRule($targetGroupRule2);

        $targetGroup3 = new TargetGroup();
        $targetGroupRule3 = new TargetGroupRule();
        $targetGroupCondition3 = new TargetGroupCondition();
        $targetGroupCondition3->setType('rule1');
        $targetGroupCondition3->setCondition(['targetGroup3']);
        $targetGroupRule3->addCondition($targetGroupCondition3);
        $targetGroup3->addRule($targetGroupRule3);

        $targetGroup4 = new TargetGroup();
        $targetGroupRule4 = new TargetGroupRule();
        $targetGroupCondition4_1 = new TargetGroupCondition();
        $targetGroupCondition4_1->setType('rule1');
        $targetGroupCondition4_1->setCondition(['targetGroup4']);
        $targetGroupRule4->addCondition($targetGroupCondition4_1);
        $targetGroupCondition4_2 = new TargetGroupCondition();
        $targetGroupCondition4_2->setType('rule2');
        $targetGroupCondition4_2->setCondition(['targetGroup4']);
        $targetGroupRule4->addCondition($targetGroupCondition4_2);
        $targetGroup4->addRule($targetGroupRule4);

        $targetGroup5 = new TargetGroup();
        $targetGroupRule5_1 = new TargetGroupRule();
        $targetGroupCondition5_1 = new TargetGroupCondition();
        $targetGroupCondition5_1->setType('rule1');
        $targetGroupCondition5_1->setCondition(['targetGroup5']);
        $targetGroupRule5_1->addCondition($targetGroupCondition5_1);
        $targetGroup5->addRule($targetGroupRule5_1);
        $targetGroupRule5_2 = new TargetGroupRule();
        $targetGroupCondition5_2 = new TargetGroupCondition();
        $targetGroupCondition5_2->setType('rule2');
        $targetGroupCondition5_2->setCondition(['targetGroup5']);
        $targetGroupRule5_2->addCondition($targetGroupCondition5_2);
        $targetGroup5->addRule($targetGroupRule5_2);

        return [
            [[], [], 'sulu_io', null],
            [[$targetGroup1], [], 'sulu_io', null],
            [[$targetGroup2], ['rule1' => [['targetGroup2']]], 'sulu_io', $targetGroup2],
            [[$targetGroup2], ['rule1' => [['targetGroup2']]], 'test', $targetGroup2],
            [[$targetGroup2], ['rule1' => []], 'sulu_io', null],
            [[$targetGroup2, $targetGroup3], ['rule1' => [['targetGroup2'], ['targetGroup3']]], 'sulu_io', $targetGroup2],
            [[$targetGroup3, $targetGroup2], ['rule1' => [['targetGroup2'], ['targetGroup3']]], 'sulu_io', $targetGroup3],
            [[$targetGroup2, $targetGroup3], ['rule1' => [['targetGroup3']]], 'sulu_io', $targetGroup3],
            [[$targetGroup2, $targetGroup3], ['rule1' => []], 'sulu_io', null],
            [[$targetGroup4], ['rule1' => [[]], 'rule2' => [['targetGroup4']]], 'sulu_io', null],
            [[$targetGroup4], ['rule1' => [['targetGroup4']], 'rule2' => [['targetGroup4']]], 'sulu_io', $targetGroup4],
            [[$targetGroup5], ['rule1' => [['targetGroup5']], 'rule2' => [['targetGroup5']]], 'sulu_io', $targetGroup5],
            [[$targetGroup5], ['rule1' => [], 'rule2' => [['targetGroup5']]], 'sulu_io', $targetGroup5],
            [[$targetGroup5], ['rule1' => [], 'rule2' => []], 'sulu_io', null],
        ];
    }
}
