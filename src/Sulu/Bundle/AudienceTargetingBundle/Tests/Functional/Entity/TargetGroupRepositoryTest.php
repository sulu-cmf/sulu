<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Functional\Entity;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupCondition;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepository;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRule;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspace;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TargetGroupRepositoryTest extends SuluTestCase
{
    /**
     * @var TargetGroupRepository
     */
    private $targetGroupRepository;

    public function setUp()
    {
        $this->targetGroupRepository = $this->getEntityManager()->getRepository(TargetGroup::class);
        $this->purgeDatabase();
    }

    public function testFindAllActiveForWebspaceOrderedByPriority()
    {
        $targetGroup1 = $this->createTargetGroup('Target Group 1', false, true, 5);
        $targetGroupRule1 = $this->createTargetGroupRule('Target Group Rule 1', $targetGroup1);
        $targetGroupCondition1 = $this->createTargetGroupCondition($targetGroupRule1);

        $targetGroup2 = $this->createTargetGroup('Target Group 2', true, true, 3);
        $targetGroupRule2 = $this->createTargetGroupRule('Target Group Rule 2', $targetGroup2);
        $targetGroupCondition2 = $this->createTargetGroupCondition($targetGroupRule2);

        $targetGroup3 = $this->createTargetGroup('Target Group 3', true, false, 5);
        $targetGroupRule3 = $this->createTargetGroupRule('Target Group Rule 3', $targetGroup3);
        $targetGroupCondition3 = $this->createTargetGroupCondition($targetGroupRule3);
        $targetGroupWebspace3 = $this->createTargetgroupWebspace('sulu_io', $targetGroup3);

        $targetGroup4 = $this->createTargetGroup('Target Group 4', true, false, 4);
        $targetGroupRule4 = $this->createTargetGroupRule('Target Group Rule 4', $targetGroup4);
        $targetGroupCondition4 = $this->createTargetGroupCondition($targetGroupRule4);
        $targetGroupWebspace4 = $this->createTargetgroupWebspace('test', $targetGroup4);

        $this->getEntityManager()->flush();

        $targetGroups = $this->targetGroupRepository->findAllActiveForWebspaceOrderedByPriority('sulu_io');

        $this->assertCount(2, $targetGroups);
        $this->assertEquals('Target Group 3', $targetGroups[0]->getTitle());
        $this->assertEquals('Target Group 2', $targetGroups[1]->getTitle());
    }

    /**
     * @return TargetGroup
     */
    private function createTargetGroup($name, $active, $allWebspaces, $priority)
    {
        $targetGroup = new TargetGroup();
        $targetGroup->setActive($active);
        $targetGroup->setAllWebspaces($allWebspaces);
        $targetGroup->setPriority($priority);
        $targetGroup->setTitle($name);

        $this->getEntityManager()->persist($targetGroup);

        return $targetGroup;
    }

    /**
     * @param $targetGroup
     *
     * @return TargetGroupRule
     */
    private function createTargetGroupRule($name, TargetGroup $targetGroup)
    {
        $targetGroupRule = new TargetGroupRule();
        $targetGroupRule->setTargetGroup($targetGroup);
        $targetGroupRule->setTitle($name);
        $targetGroupRule->setFrequency(1);

        $this->getEntityManager()->persist($targetGroupRule);

        return $targetGroupRule;
    }

    private function createTargetGroupCondition(TargetGroupRule $targetGroupRule)
    {
        $targetGroupCondition = new TargetGroupCondition();
        $targetGroupCondition->setRule($targetGroupRule);
        $targetGroupCondition->setType('test');
        $targetGroupCondition->setCondition([]);

        $this->getEntityManager()->persist($targetGroupCondition);

        return $targetGroupCondition;
    }

    private function createTargetgroupWebspace($webspaceKey, $targetGroup3)
    {
        $targetGroupWebspace = new TargetGroupWebspace();
        $targetGroupWebspace->setTargetGroup($targetGroup3);
        $targetGroupWebspace->setWebspaceKey($webspaceKey);

        $this->getEntityManager()->persist($targetGroupWebspace);

        return $targetGroupWebspace;
    }
}
