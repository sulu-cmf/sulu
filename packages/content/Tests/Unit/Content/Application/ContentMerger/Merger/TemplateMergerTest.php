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
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\TemplateMerger;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;

class TemplateMergerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function getTemplateMergerInstance(): MergerInterface
    {
        return new TemplateMerger();
    }

    public function testMergeSourceNotImplementTemplateInterface(): void
    {
        $merger = $this->getTemplateMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(TemplateInterface::class);
        $target->setTemplateKey(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementTemplateInterface(): void
    {
        $merger = $this->getTemplateMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(TemplateInterface::class);
        $source->getTemplateKey(Argument::any())->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getTemplateMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(TemplateInterface::class);
        $source->getTemplateKey()->willReturn('template-key')->shouldBeCalled();
        $source->getTemplateData()->willReturn(['template' => 'data'])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(TemplateInterface::class);
        $target->setTemplateKey('template-key')->shouldBeCalled();
        $target->getTemplateData()->willReturn(['template2' => 'data2'])->shouldBeCalled();
        $target->setTemplateData(['template' => 'data', 'template2' => 'data2'])->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergNotSet(): void
    {
        $merger = $this->getTemplateMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(TemplateInterface::class);
        $source->getTemplateKey()->willReturn('')->shouldBeCalled();
        $source->getTemplateData()->willReturn([])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(TemplateInterface::class);
        $target->setTemplateKey('')->shouldNotBeCalled();
        $target->getTemplateData()->willReturn(['template2' => 'data2'])->shouldBeCalled();
        $target->setTemplateData(['template2' => 'data2'])->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}
