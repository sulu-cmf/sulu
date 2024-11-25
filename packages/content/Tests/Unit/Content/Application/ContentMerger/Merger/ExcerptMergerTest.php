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
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\ExcerptMerger;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class ExcerptMergerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function getExcerptMergerInstance(): MergerInterface
    {
        return new ExcerptMerger();
    }

    public function testMergeSourceNotImplementExcerptInterface(): void
    {
        $merger = $this->getExcerptMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ExcerptInterface::class);
        $target->setExcerptTitle(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementExcerptInterface(): void
    {
        $merger = $this->getExcerptMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ExcerptInterface::class);
        $source->getExcerptTitle(Argument::any())->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getExcerptMergerInstance();

        $tag1 = $this->prophesize(TagInterface::class);
        $tag1->getId()->willReturn(1);
        $tag2 = $this->prophesize(TagInterface::class);
        $tag2->getId()->willReturn(2);

        $category1 = $this->prophesize(CategoryInterface::class);
        $category1->getId()->willReturn(3);
        $category2 = $this->prophesize(CategoryInterface::class);
        $category2->getId()->willReturn(4);

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ExcerptInterface::class);
        $source->getExcerptTitle()->willReturn('Excerpt Title')->shouldBeCalled();
        $source->getExcerptDescription()->willReturn('Excerpt Description')->shouldBeCalled();
        $source->getExcerptMore()->willReturn('Excerpt More')->shouldBeCalled();
        $source->getExcerptTags()->willReturn([$tag1->reveal(), $tag2->reveal()])->shouldBeCalled();
        $source->getExcerptCategories()->willReturn([$category1->reveal(), $category2->reveal()])->shouldBeCalled();
        $source->getExcerptImage()->willReturn(['id' => 8])->shouldBeCalled();
        $source->getExcerptIcon()->willReturn(['id' => 9])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ExcerptInterface::class);
        $target->setExcerptTitle('Excerpt Title')->shouldBeCalled();
        $target->setExcerptDescription('Excerpt Description')->shouldBeCalled();
        $target->setExcerptMore('Excerpt More')->shouldBeCalled();
        $target->setExcerptTags(Argument::that(function($tags) {
            return \array_map(function(TagInterface $tag) {
                return $tag->getId();
            }, $tags) === [1, 2];
        }))->shouldBeCalled();
        $target->setExcerptCategories(Argument::that(function($categories) {
            return \array_map(function(CategoryInterface $category) {
                return $category->getId();
            }, $categories) === [3, 4];
        }))->shouldBeCalled();
        $target->setExcerptImage(['id' => 8])->shouldBeCalled();
        $target->setExcerptIcon(['id' => 9])->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeNotSet(): void
    {
        $merger = $this->getExcerptMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(ExcerptInterface::class);
        $source->getExcerptTitle()->willReturn(null)->shouldBeCalled();
        $source->getExcerptDescription()->willReturn(null)->shouldBeCalled();
        $source->getExcerptMore()->willReturn(null)->shouldBeCalled();
        $source->getExcerptTags()->willReturn([])->shouldBeCalled();
        $source->getExcerptCategories()->willReturn([])->shouldBeCalled();
        $source->getExcerptImage()->willReturn(null)->shouldBeCalled();
        $source->getExcerptIcon()->willReturn(null)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(ExcerptInterface::class);
        $target->setExcerptTitle(Argument::any())->shouldNotBeCalled();
        $target->setExcerptDescription(Argument::any())->shouldNotBeCalled();
        $target->setExcerptMore(Argument::any())->shouldNotBeCalled();
        $target->setExcerptTags(Argument::any())->shouldNotBeCalled();
        $target->setExcerptCategories(Argument::any())->shouldNotBeCalled();
        $target->setExcerptImage(Argument::any())->shouldNotBeCalled();
        $target->setExcerptIcon(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}
