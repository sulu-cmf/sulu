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

namespace Sulu\Content\Application\ContentManager;

use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Application\ContentIndexer\ContentIndexerInterface;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ContentManager implements ContentManagerInterface
{
    public function __construct(
        private ContentAggregatorInterface $contentAggregator,
        private ContentPersisterInterface $contentPersister,
        private ContentNormalizerInterface $contentNormalizer,
        private ContentCopierInterface $contentCopier,
        private ContentWorkflowInterface $contentWorkflow,
        private ContentIndexerInterface $contentIndexer,
    ) {
    }

    public function resolve(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): DimensionContentInterface
    {
        return $this->contentAggregator->aggregate($contentRichEntity, $dimensionAttributes);
    }

    public function persist(ContentRichEntityInterface $contentRichEntity, array $data, array $dimensionAttributes): DimensionContentInterface
    {
        return $this->contentPersister->persist($contentRichEntity, $data, $dimensionAttributes);
    }

    public function normalize(DimensionContentInterface $dimensionContent): array
    {
        return $this->contentNormalizer->normalize($dimensionContent);
    }

    public function copy(
        ContentRichEntityInterface $sourceContentRichEntity,
        array $sourceDimensionAttributes,
        ContentRichEntityInterface $targetContentRichEntity,
        array $targetDimensionAttributes
    ): DimensionContentInterface {
        return $this->contentCopier->copy(
            $sourceContentRichEntity,
            $sourceDimensionAttributes,
            $targetContentRichEntity,
            $targetDimensionAttributes
        );
    }

    public function applyTransition(
        ContentRichEntityInterface $contentRichEntity,
        array $dimensionAttributes,
        string $transitionName
    ): DimensionContentInterface {
        return $this->contentWorkflow->apply($contentRichEntity, $dimensionAttributes, $transitionName);
    }

    public function index(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): DimensionContentInterface
    {
        return $this->contentIndexer->index($contentRichEntity, $dimensionAttributes);
    }

    public function deindex(string $resourceKey, $id, array $dimensionAttributes = []): void
    {
        $this->contentIndexer->deindex($resourceKey, $id, $dimensionAttributes);
    }
}
