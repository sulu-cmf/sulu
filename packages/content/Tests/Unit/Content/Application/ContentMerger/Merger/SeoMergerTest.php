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
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\SeoMerger;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;

class SeoMergerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function getSeoMergerInstance(): MergerInterface
    {
        return new SeoMerger();
    }

    public function testMergeSourceNotImplementSeoInterface(): void
    {
        $merger = $this->getSeoMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(SeoInterface::class);
        $target->setSeoTitle(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementSeoInterface(): void
    {
        $merger = $this->getSeoMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(SeoInterface::class);
        $source->getSeoTitle(Argument::any())->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getSeoMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(SeoInterface::class);
        $source->getSeoTitle()->willReturn('Seo Title')->shouldBeCalled();
        $source->getSeoDescription()->willReturn('Seo Description')->shouldBeCalled();
        $source->getSeoKeywords()->willReturn('Seo Keyword 1, Seo Keyword 2')->shouldBeCalled();
        $source->getSeoCanonicalUrl()->willReturn('https://canonical.localhost/')->shouldBeCalled();
        $source->getSeoNoFollow()->willReturn(true)->shouldBeCalled();
        $source->getSeoNoIndex()->willReturn(true)->shouldBeCalled();
        $source->getSeoHideInSitemap()->willReturn(true)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(SeoInterface::class);
        $target->setSeoTitle('Seo Title')->shouldBeCalled();
        $target->setSeoDescription('Seo Description')->shouldBeCalled();
        $target->setSeoKeywords('Seo Keyword 1, Seo Keyword 2')->shouldBeCalled();
        $target->setSeoCanonicalUrl('https://canonical.localhost/')->shouldBeCalled();
        $target->setSeoNoFollow(true)->shouldBeCalled();
        $target->setSeoNoIndex(true)->shouldBeCalled();
        $target->setSeoHideInSitemap(true)->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeNotSet(): void
    {
        $seoMerger = $this->getSeoMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(SeoInterface::class);
        $source->getSeoTitle()->willReturn(null)->shouldBeCalled();
        $source->getSeoDescription()->willReturn(null)->shouldBeCalled();
        $source->getSeoKeywords()->willReturn(null)->shouldBeCalled();
        $source->getSeoCanonicalUrl()->willReturn(null)->shouldBeCalled();
        $source->getSeoNoFollow()->willReturn(false)->shouldBeCalled();
        $source->getSeoNoIndex()->willReturn(false)->shouldBeCalled();
        $source->getSeoHideInSitemap()->willReturn(false)->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(SeoInterface::class);
        $target->setSeoTitle('Seo Title')->shouldNotBeCalled();
        $target->setSeoDescription('Seo Description')->shouldNotBeCalled();
        $target->setSeoKeywords('Seo Keyword 1, Seo Keyword 2')->shouldNotBeCalled();
        $target->setSeoCanonicalUrl('https://canonical.localhost/')->shouldNotBeCalled();
        $target->setSeoNoFollow(false)->shouldNotBeCalled();
        $target->setSeoNoIndex(false)->shouldNotBeCalled();
        $target->setSeoHideInSitemap(false)->shouldNotBeCalled();

        $seoMerger->merge($target->reveal(), $source->reveal());
    }
}
