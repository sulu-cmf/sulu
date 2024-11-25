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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\WebspaceMerger;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WebspaceInterface;

class WebspaceMergerTest extends TestCase
{
    use ProphecyTrait;

    protected function getWebspaceMergerInstance(): MergerInterface
    {
        return new WebspaceMerger();
    }

    public function testMergeSourceNotImplementWebspaceInterface(): void
    {
        $merger = $this->getWebspaceMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(WebspaceInterface::class);
        $target->setMainWebspace(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementWebspaceInterface(): void
    {
        $merger = $this->getWebspaceMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(WebspaceInterface::class);
        $source->setMainWebspace(Argument::any())->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getWebspaceMergerInstance();

        $mainWebspace = 'sulu-io';

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(WebspaceInterface::class);
        $source->getMainWebspace()->willReturn($mainWebspace)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(WebspaceInterface::class);
        $target->setMainWebspace($mainWebspace)->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeNotSet(): void
    {
        $merger = $this->getWebspaceMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(WebspaceInterface::class);
        $source->getMainWebspace()->willReturn(null)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(WebspaceInterface::class);
        $target->setMainWebspace(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}
