<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentMerger\Merger;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\WorkflowMerger;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;

class WorkflowMergerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function getWorkflowMergerInstance(): MergerInterface
    {
        return new WorkflowMerger();
    }

    public function testMergeSourceNotImplementWorkflowInterface(): void
    {
        $merger = $this->getWorkflowMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(WorkflowInterface::class);
        $target->getWorkflowPlace()->shouldNotBeCalled();
        $target->getWorkflowPublished()->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementWorkflowInterface(): void
    {
        $merger = $this->getWorkflowMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(WorkflowInterface::class);
        $source->getWorkflowPlace()->shouldNotBeCalled();
        $source->getWorkflowPublished()->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getWorkflowMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(WorkflowInterface::class);
        $published = new \DateTimeImmutable();
        $source->getWorkflowPlace()->willReturn('draft')->shouldBeCalled();
        $source->getWorkflowPublished()->willReturn($published)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(WorkflowInterface::class);
        $target->setWorkflowPlace('draft')->shouldBeCalled();
        $target->setWorkflowPublished($published)->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}
