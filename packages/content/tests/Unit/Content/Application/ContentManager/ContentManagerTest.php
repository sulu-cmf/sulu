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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentManager;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Application\ContentIndexer\ContentIndexerInterface;
use Sulu\Content\Application\ContentManager\ContentManager;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ContentManagerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createContentManagerInstance(
        ContentAggregatorInterface $contentAggregator,
        ContentPersisterInterface $contentPersister,
        ContentNormalizerInterface $contentNormalizer,
        ContentCopierInterface $contentCopier,
        ContentWorkflowInterface $contentWorkflow,
        ContentIndexerInterface $contentIndexer
    ): ContentManagerInterface {
        return new ContentManager(
            $contentAggregator,
            $contentPersister,
            $contentNormalizer,
            $contentCopier,
            $contentWorkflow,
            $contentIndexer
        );
    }

    public function testResolve(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);
        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $contentIndexer = $this->prophesize(ContentIndexerInterface::class);

        $contentManager = $this->createContentManagerInstance(
            $contentAggregator->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal(),
            $contentCopier->reveal(),
            $contentWorkflow->reveal(),
            $contentIndexer->reveal()
        );

        $contentAggregator->aggregate($contentRichEntity->reveal(), $dimensionAttributes)
            ->willReturn($dimensionContent->reveal())
            ->shouldBeCalled();

        $this->assertSame(
            $dimensionContent->reveal(),
            $contentManager->resolve($contentRichEntity->reveal(), $dimensionAttributes)
        );
    }

    public function testPersist(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $data = ['data' => 'value'];
        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);
        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $contentIndexer = $this->prophesize(ContentIndexerInterface::class);

        $contentManager = $this->createContentManagerInstance(
            $contentAggregator->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal(),
            $contentCopier->reveal(),
            $contentWorkflow->reveal(),
            $contentIndexer->reveal()
        );

        $contentPersister->persist($contentRichEntity->reveal(), $data, $dimensionAttributes)
            ->willReturn($dimensionContent->reveal())
            ->shouldBeCalled();

        $this->assertSame(
            $dimensionContent->reveal(),
            $contentManager->persist($contentRichEntity->reveal(), $data, $dimensionAttributes)
        );
    }

    public function testNormalize(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);
        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $contentIndexer = $this->prophesize(ContentIndexerInterface::class);

        $contentManager = $this->createContentManagerInstance(
            $contentAggregator->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal(),
            $contentCopier->reveal(),
            $contentWorkflow->reveal(),
            $contentIndexer->reveal()
        );

        $contentNormalizer->normalize($dimensionContent->reveal())
            ->willReturn(['resolved' => 'data'])
            ->shouldBeCalled();

        $this->assertSame(
            ['resolved' => 'data'],
            $contentManager->normalize($dimensionContent->reveal())
        );
    }

    public function testCopy(): void
    {
        $copiedContent = $this->prophesize(DimensionContentInterface::class);

        $sourceContentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $sourceDimensionAttributes = ['locale' => 'en'];
        $targetContentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $targetDimensionAttributes = ['locale' => 'de'];

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);
        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $contentIndexer = $this->prophesize(ContentIndexerInterface::class);

        $contentManager = $this->createContentManagerInstance(
            $contentAggregator->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal(),
            $contentCopier->reveal(),
            $contentWorkflow->reveal(),
            $contentIndexer->reveal()
        );

        $contentCopier->copy(
            $sourceContentRichEntity->reveal(),
            $sourceDimensionAttributes,
            $targetContentRichEntity->reveal(),
            $targetDimensionAttributes
        )
            ->willReturn($copiedContent->reveal())
            ->shouldBeCalled();

        $this->assertSame(
            $copiedContent->reveal(),
            $contentManager->copy(
                $sourceContentRichEntity->reveal(),
                $sourceDimensionAttributes,
                $targetContentRichEntity->reveal(),
                $targetDimensionAttributes
            )
        );
    }

    public function testApplyTransition(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en'];
        $transitionName = 'review';

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);
        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $contentIndexer = $this->prophesize(ContentIndexerInterface::class);

        $contentManager = $this->createContentManagerInstance(
            $contentAggregator->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal(),
            $contentCopier->reveal(),
            $contentWorkflow->reveal(),
            $contentIndexer->reveal()
        );

        $contentWorkflow->apply(
            $contentRichEntity->reveal(),
            $dimensionAttributes,
            $transitionName
        )
            ->willReturn($dimensionContent->reveal())
            ->shouldBeCalled();

        $this->assertSame(
            $dimensionContent->reveal(),
            $contentManager->applyTransition(
                $contentRichEntity->reveal(),
                $dimensionAttributes,
                $transitionName
            )
        );
    }

    public function testIndex(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE];

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);
        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $contentIndexer = $this->prophesize(ContentIndexerInterface::class);

        $contentManager = $this->createContentManagerInstance(
            $contentAggregator->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal(),
            $contentCopier->reveal(),
            $contentWorkflow->reveal(),
            $contentIndexer->reveal()
        );

        $contentIndexer->index(
            $contentRichEntity->reveal(),
            $dimensionAttributes
        )
            ->willReturn($dimensionContent->reveal())
            ->shouldBeCalled();

        $this->assertSame(
            $dimensionContent->reveal(),
            $contentManager->index(
                $contentRichEntity->reveal(),
                $dimensionAttributes
            )
        );
    }

    public function testDeindex(): void
    {
        $resourceKey = 'examples';
        $resourceId = '123';
        $dimensionAttributes = ['locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE];

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);
        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $contentIndexer = $this->prophesize(ContentIndexerInterface::class);

        $contentManager = $this->createContentManagerInstance(
            $contentAggregator->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal(),
            $contentCopier->reveal(),
            $contentWorkflow->reveal(),
            $contentIndexer->reveal()
        );

        $contentIndexer->deindex($resourceKey, $resourceId, $dimensionAttributes)->shouldBeCalled();
        $contentManager->deindex(
            $resourceKey,
            $resourceId,
            $dimensionAttributes
        );
    }

    public function testDeindexWithoutDimensionAttributes(): void
    {
        $resourceKey = 'examples';
        $resourceId = '123';

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentPersister = $this->prophesize(ContentPersisterInterface::class);
        $contentNormalizer = $this->prophesize(ContentNormalizerInterface::class);
        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentWorkflow = $this->prophesize(ContentWorkflowInterface::class);
        $contentIndexer = $this->prophesize(ContentIndexerInterface::class);

        $contentManager = $this->createContentManagerInstance(
            $contentAggregator->reveal(),
            $contentPersister->reveal(),
            $contentNormalizer->reveal(),
            $contentCopier->reveal(),
            $contentWorkflow->reveal(),
            $contentIndexer->reveal()
        );

        $contentIndexer->deindex($resourceKey, $resourceId, [])->shouldBeCalled();
        $contentManager->deindex(
            $resourceKey,
            $resourceId
        );
    }
}
