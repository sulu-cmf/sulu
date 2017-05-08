<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * This class finds the correct target group (which are defined in the database) for the given circumstances. It
 * therefore uses a set of implemented rules, which also take data from the database.
 */
class TargetGroupEvaluator implements TargetGroupEvaluatorInterface
{
    /**
     * @var TargetGroupRepositoryInterface
     */
    private $targetGroupRepository;

    /**
     * @var RuleCollectionInterface
     */
    private $ruleCollection;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(
        RuleCollectionInterface $ruleCollection,
        TargetGroupRepositoryInterface $targetGroupRepository,
        RequestAnalyzerInterface $requestAnalyzer
    ) {
        $this->ruleCollection = $ruleCollection;
        $this->targetGroupRepository = $targetGroupRepository;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate()
    {
        $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        foreach ($this->targetGroupRepository->findAllActiveForWebspaceOrderedByPriority($webspaceKey) as $targetGroup) {
            if ($this->evaluateTargetGroup($targetGroup)) {
                return $targetGroup;
            }
        }

        return null;
    }

    /**
     * Evaluates if one of the rules of the given TargetGroup match. If one of these rules are matching the entire
     * target group is matching.
     *
     * @param TargetGroupInterface $targetGroup
     *
     * @return bool
     */
    private function evaluateTargetGroup(TargetGroupInterface $targetGroup)
    {
        foreach ($targetGroup->getRules() as $targetGroupRule) {
            if ($this->evaluateTargetGroupRule($targetGroupRule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluates if the given rule is matching. Only returns true if all of the conditions are matching.
     *
     * @param TargetGroupRuleInterface $targetGroupRule
     *
     * @return bool
     */
    private function evaluateTargetGroupRule(TargetGroupRuleInterface $targetGroupRule)
    {
        foreach ($targetGroupRule->getConditions() as $targetGroupCondition) {
            $rule = $this->ruleCollection->getRule($targetGroupCondition->getType());

            if (!$rule->evaluate($targetGroupCondition->getCondition())) {
                return false;
            }
        }

        return true;
    }
}
