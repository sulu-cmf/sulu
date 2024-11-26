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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentCopier;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentCopier\ContentCopier;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ContentCopierTest extends TestCase
{
    use ProphecyTrait;

    protected function createContentCopierInstance(
        ContentAggregatorInterface $contentAggregator,
        ContentMergerInterface $contentMerger,
        ContentPersisterInterface $contentPersister,
        ContentNormalizerInterface $contentNormalizer
    ): ContentCopierInterface {
        return new ContentCopier(
            $contentAggregator,
            $contentMerger,
            $contentPersister,
            $contentNormalizer
        );
    }

    public function testCopy(): void
    {
        $resolvedSourceContent = $this->prophesize(DimensionContentInterface::class);
        $resolvedTargetContent = $this->prophesize(DimensionContentInterface::class);

        $sourceContentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $sourceDimensionAttributes = ['locale' => 'en'];
        $targetContentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $targetDimensionAttributes = ['locale' => 'de'];

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);

        $contentAggregator->aggregate($sourceContentRichEntity->reveal(), $sourceDimensionAttributes)
            ->willReturn($resolvedSourceContent->reveal())
            ->shouldBeCalled();

        $contentNormalizer->normalize($resolvedSourceContent->reveal())
            ->willReturn(['resolved' => 'data'])
            ->shouldBeCalled();

        $contentPersister->persist($targetContentRichEntity, ['resolved' => 'data'], $targetDimensionAttributes)
            ->willReturn($resolvedTargetContent->reveal())
            ->shouldBeCalled();

        $contentCopier = $this->createContentCopierInstance(
            $contentAggregator->reveal(),
            $contentMerger->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal()
        );

        $this->assertSame(
            $resolvedTargetContent->reveal(),
            $contentCopier->copy(
                $sourceContentRichEntity->reveal(),
                $sourceDimensionAttributes,
                $targetContentRichEntity->reveal(),
                $targetDimensionAttributes
            )
        );
    }

    public function testCopyFromDimensionContentCollection(): void
    {
        $resolvedSourceContent = $this->prophesize(DimensionContentInterface::class);
        $resolvedTargetContent = $this->prophesize(DimensionContentInterface::class);

        $sourceContentDimensionCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $targetContentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $targetDimensionAttributes = ['locale' => 'de'];

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);

        $contentMerger->merge($sourceContentDimensionCollection->reveal())
            ->willReturn($resolvedSourceContent->reveal())
            ->shouldBeCalled();

        $contentNormalizer->normalize($resolvedSourceContent->reveal())
            ->willReturn(['resolved' => 'data'])
            ->shouldBeCalled();

        $contentPersister->persist($targetContentRichEntity, ['resolved' => 'data'], $targetDimensionAttributes)
            ->willReturn($resolvedTargetContent->reveal())
            ->shouldBeCalled();

        $contentCopier = $this->createContentCopierInstance(
            $contentAggregator->reveal(),
            $contentMerger->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal()
        );

        $this->assertSame(
            $resolvedTargetContent->reveal(),
            $contentCopier->copyFromDimensionContentCollection(
                $sourceContentDimensionCollection->reveal(),
                $targetContentRichEntity->reveal(),
                $targetDimensionAttributes
            )
        );
    }

    public function testCopyFromDimensionContent(): void
    {
        $resolvedSourceContent = $this->prophesize(DimensionContentInterface::class);
        $resolvedTargetContent = $this->prophesize(DimensionContentInterface::class);

        $targetContentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $targetDimensionAttributes = ['locale' => 'de'];

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);

        $contentNormalizer->normalize($resolvedSourceContent->reveal())
            ->willReturn(['resolved' => 'data'])
            ->shouldBeCalled();

        $contentPersister->persist($targetContentRichEntity, ['resolved' => 'data'], $targetDimensionAttributes)
            ->willReturn($resolvedTargetContent->reveal())
            ->shouldBeCalled();

        $contentCopier = $this->createContentCopierInstance(
            $contentAggregator->reveal(),
            $contentMerger->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal()
        );

        $this->assertSame(
            $resolvedTargetContent->reveal(),
            $contentCopier->copyFromDimensionContent(
                $resolvedSourceContent->reveal(),
                $targetContentRichEntity->reveal(),
                $targetDimensionAttributes
            )
        );
    }

    public function testCopyFromDimensionContentWithIgnoredAttributesAndData(): void
    {
        $resolvedSourceContent = $this->prophesize(DimensionContentInterface::class);
        $resolvedTargetContent = $this->prophesize(DimensionContentInterface::class);

        $targetContentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $targetDimensionAttributes = ['locale' => 'de'];

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);

        $contentNormalizer->normalize($resolvedSourceContent->reveal())
            ->willReturn([
                'resolved' => 'content',
                'overwritten' => 'old',
                'ignored' => 'value',
            ])
            ->shouldBeCalled();

        $contentPersister->persist($targetContentRichEntity, [
            'resolved' => 'content',
            'overwritten' => 'new',
        ], $targetDimensionAttributes)
            ->willReturn($resolvedTargetContent->reveal())
            ->shouldBeCalled();

        $contentCopier = $this->createContentCopierInstance(
            $contentAggregator->reveal(),
            $contentMerger->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal()
        );

        $this->assertSame(
            $resolvedTargetContent->reveal(),
            $contentCopier->copyFromDimensionContent(
                $resolvedSourceContent->reveal(),
                $targetContentRichEntity->reveal(),
                $targetDimensionAttributes,
                [
                    'data' => [
                        'overwritten' => 'new',
                    ],
                    'ignoredAttributes' => [
                        'ignored',
                    ],
                ],
            )
        );
    }
}
