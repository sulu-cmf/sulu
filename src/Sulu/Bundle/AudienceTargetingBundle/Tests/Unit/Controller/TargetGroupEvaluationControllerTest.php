<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AudienceTargetingBundle\Controller\TargetGroupEvaluationController;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Symfony\Component\HttpFoundation\Request;

class TargetGroupEvaluationControllerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<TargetGroupEvaluatorInterface>
     */
    private $targetGroupEvaluator;

    /**
     * @var ObjectProphecy<TargetGroupRepositoryInterface>
     */
    private $targetGroupRepository;

    /**
     * @var ObjectProphecy<TargetGroupStoreInterface>
     */
    private $targetGroupStore;

    public function setUp(): void
    {
        $this->targetGroupEvaluator = $this->prophesize(TargetGroupEvaluatorInterface::class);
        $this->targetGroupRepository = $this->prophesize(TargetGroupRepositoryInterface::class);
        $this->targetGroupStore = $this->prophesize(TargetGroupStoreInterface::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideTargetGroup')]
    public function testTargetGroupAction($header, $currentTargetGroup, $targetGroup, $targetGroupId): void
    {
        if ($currentTargetGroup) {
            $this->targetGroupRepository->find($currentTargetGroup->getId())->willReturn($currentTargetGroup);
            $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_SESSION, $currentTargetGroup)
                ->willReturn($targetGroup);
        } else {
            $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_VISITOR, null)
                ->willReturn($targetGroup);
        }

        $targetGroupEvaluationController = new TargetGroupEvaluationController(
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            $this->targetGroupStore->reveal(),
            $header
        );

        $request = new Request();
        if ($currentTargetGroup) {
            $request->headers->set($header, $currentTargetGroup->getId());
        }

        $response = $targetGroupEvaluationController->targetGroupAction($request);

        $this->assertEquals($targetGroupId, $response->headers->get($header));
    }

    public static function provideTargetGroup()
    {
        $targetGroup1 = new TargetGroup();

        $targetGroup2 = new TargetGroup();
        self::setPrivateProperty($targetGroup2, 'id', 2);
        $targetGroup3 = new TargetGroup();
        self::setPrivateProperty($targetGroup3, 'id', 3);
        $targetGroup4 = new TargetGroup();
        self::setPrivateProperty($targetGroup4, 'id', 4);

        return [
            ['X-Sulu-Target-Group-Hash', null, $targetGroup1, null],
            ['X-Sulu-Target-Group-Hash', null, $targetGroup2, 2],
            ['X-Sulu-Target-Group', null, null, 0],
            ['X-Sulu-Target-Group', $targetGroup3, $targetGroup4, 4],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideTargetGroupHit')]
    public function testTargetGroupHitAction($oldTargetGroup, $newTargetGroup, $newTargetGroupId): void
    {
        $this->targetGroupRepository->find($oldTargetGroup->getId())->willReturn($oldTargetGroup);
        $this->targetGroupEvaluator->evaluate(
            TargetGroupRuleInterface::FREQUENCY_HIT,
            $oldTargetGroup
        )->willReturn($newTargetGroup);
        $this->targetGroupStore->getTargetGroupId(true)->willReturn($oldTargetGroup->getId());
        $targetGroupEvaluationController = new TargetGroupEvaluationController(
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            $this->targetGroupStore->reveal(),
            'X-Sulu-Target-Group'
        );

        if ($newTargetGroupId) {
            $this->targetGroupStore->updateTargetGroupId($newTargetGroupId)->shouldBeCalled();
        } else {
            $this->targetGroupStore->updateTargetGroupId(Argument::any())->shouldNotBeCalled();
        }

        $targetGroupEvaluationController->targetGroupHitAction();
    }

    public static function provideTargetGroupHit()
    {
        $oldTargetGroup1 = new TargetGroup();
        self::setPrivateProperty($oldTargetGroup1, 'id', 1);
        $newTargetGroup1 = new TargetGroup();
        self::setPrivateProperty($newTargetGroup1, 'id', 2);

        return [
            [$oldTargetGroup1, $newTargetGroup1, 2],
            [$oldTargetGroup1, null, null],
        ];
    }
}
