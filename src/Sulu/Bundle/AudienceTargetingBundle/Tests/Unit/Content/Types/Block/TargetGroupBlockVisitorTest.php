<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types\Block;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AudienceTargetingBundle\Content\Types\Block\TargetGroupBlockVisitor;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\Metadata;

class TargetGroupBlockVisitorTest extends TestCase
{
    /**
     * @var TargetGroupStoreInterface
     */
    private $targetGroupStore;

    /**
     * @var TargetGroupBlockVisitor
     */
    private $targetGroupBlockVisitor;

    public function setUp(): void
    {
        $this->targetGroupStore = $this->prophesize(TargetGroupStoreInterface::class);
        $this->targetGroupBlockVisitor = new TargetGroupBlockVisitor($this->targetGroupStore->reveal());
    }

    public function testShouldNotSkipWithObjectAsSettings()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(new \stdClass());

        $this->assertEquals($blockPropertyType, $this->targetGroupBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithEmptyArrayAsSettings()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings([]);

        $this->assertEquals($blockPropertyType, $this->targetGroupBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldSkipWithOtherTargetGroup()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['target_groups_enabled' => true, 'target_groups' => [1, 2]]);

        $this->targetGroupStore->getTargetGroupId()->willReturn(3);

        $this->assertNull($this->targetGroupBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithSameTargetGroup()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['target_groups_enabled' => true, 'target_groups' => [1, 2, 3]]);

        $this->targetGroupStore->getTargetGroupId()->willReturn(3);

        $this->assertEquals($blockPropertyType, $this->targetGroupBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithoutTargetGroups()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['target_groups_enabled' => true]);

        $this->targetGroupStore->getTargetGroupId()->willReturn(3);

        $this->assertEquals($blockPropertyType, $this->targetGroupBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithDisabledTargetGroups()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['target_groups_enabled' => false, 'target_groups' => [1, 2]]);

        $this->targetGroupStore->getTargetGroupId()->willReturn(3);

        $this->assertEquals($blockPropertyType, $this->targetGroupBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithoutTargetGroupsFlag()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['target_groups' => [1, 2]]);

        $this->targetGroupStore->getTargetGroupId()->willReturn(3);

        $this->assertEquals($blockPropertyType, $this->targetGroupBlockVisitor->visit($blockPropertyType));
    }
}
