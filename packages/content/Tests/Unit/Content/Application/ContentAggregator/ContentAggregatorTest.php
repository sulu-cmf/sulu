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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentAggregator;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContentBundle\Content\Application\ContentAggregator\ContentAggregator;
use Sulu\Bundle\ContentBundle\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollection;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Repository\DimensionContentRepositoryInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentAggregatorTest extends TestCase
{
    use ProphecyTrait;

    protected function createContentAggregatorInstance(
        DimensionContentRepositoryInterface $dimensionContentRepository,
        ContentMergerInterface $contentMerger
    ): ContentAggregatorInterface {
        return new ContentAggregator(
            $dimensionContentRepository,
            $contentMerger
        );
    }

    public function testAggregate(): void
    {
        $example = new Example();

        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage(DimensionContentInterface::STAGE_DRAFT);
        $dimensionContent1->setLocale(null);
        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setStage(DimensionContentInterface::STAGE_DRAFT);
        $dimensionContent2->setLocale(null);

        $attributes = [
            'locale' => 'de',
        ];

        $expectedAttributes = [
            'locale' => 'de',
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        $dimensionContentCollection = new DimensionContentCollection(
            [
                $dimensionContent1,
                $dimensionContent2,
            ],
            $expectedAttributes,
            ExampleDimensionContent::class
        );

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $dimensionContentRepository->load($example, $attributes)
            ->willReturn($dimensionContentCollection)
            ->shouldBeCalled();

        $mergedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $contentMerger = $this->prophesize(ContentMergerInterface::class);
        $contentMerger->merge($dimensionContentCollection)
            ->willReturn($mergedDimensionContent->reveal())
            ->shouldBeCalled();

        $contentAggregator = $this->createContentAggregatorInstance(
            $dimensionContentRepository->reveal(),
            $contentMerger->reveal()
        );

        $this->assertSame($mergedDimensionContent->reveal(), $contentAggregator->aggregate($example, $attributes));
    }

    public function testAggregateNotFound(): void
    {
        $this->expectException(ContentNotFoundException::class);

        $example = new Example();

        $attributes = [
            'locale' => 'de',
        ];

        $expectedAttributes = [
            'locale' => 'de',
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        $dimensionContentCollection = new DimensionContentCollection(
            [],
            $expectedAttributes,
            ExampleDimensionContent::class
        );

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $dimensionContentRepository->load($example, $attributes)->willReturn($dimensionContentCollection);

        $contentMerger = $this->prophesize(ContentMergerInterface::class);
        $contentMerger->merge($dimensionContentCollection)->willReturn(Argument::cetera())->shouldNotBeCalled();

        $contentAggregator = $this->createContentAggregatorInstance(
            $dimensionContentRepository->reveal(),
            $contentMerger->reveal()
        );

        $contentAggregator->aggregate($example, $attributes);
    }
}
