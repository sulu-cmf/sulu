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
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\ShadowMerger;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;

class ShadowMergerTest extends TestCase
{
    use ProphecyTrait;

    protected function getShadowMergerInstance(): MergerInterface
    {
        return new ShadowMerger();
    }

    public function testMergeSourceNotImplementShadowInterface(): void
    {
        $merger = $this->getShadowMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ShadowInterface::class);
        $target->setShadowLocale(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementShadowInterface(): void
    {
        $merger = $this->getShadowMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ShadowInterface::class);
        $source->getShadowLocale(Argument::any())->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getShadowMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ShadowInterface::class);
        $source->getShadowLocale()->willReturn('en')->shouldBeCalled();
        $source->getShadowLocales()->willReturn(['de' => 'en'])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ShadowInterface::class);
        $target->setShadowLocale('en')->shouldBeCalled();
        $target->addShadowLocale('de', 'en')->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeNotSet(): void
    {
        $merger = $this->getShadowMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ShadowInterface::class);
        $source->getShadowLocale()->willReturn(null)->shouldBeCalled();
        $source->getShadowLocales()->willReturn(null)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ShadowInterface::class);
        $target->setShadowLocale(Argument::any())->shouldNotBeCalled();
        $target->addShadowLocale(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}
