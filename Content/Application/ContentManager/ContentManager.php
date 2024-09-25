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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentManager;

use Sulu\Bundle\ContentBundle\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentIndexer\ContentIndexerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class ContentManager implements ContentManagerInterface
{
    /**
     * @var ContentAggregatorInterface
     */
    private $contentAggregator;

    /**
     * @var ContentPersisterInterface
     */
    private $contentPersister;

    /**
     * @var ContentNormalizerInterface
     */
    private $contentNormalizer;

    /**
     * @var ContentCopierInterface
     */
    private $contentCopier;

    /**
     * @var ContentWorkflowInterface
     */
    private $contentWorkflow;

    /**
     * @var ContentIndexerInterface
     */
    private $contentIndexer;

    public function __construct(
        ContentAggregatorInterface $contentAggregator,
        ContentPersisterInterface $contentPersister,
        ContentNormalizerInterface $contentNormalizer,
        ContentCopierInterface $contentCopier,
        ContentWorkflowInterface $contentWorkflow,
        ContentIndexerInterface $contentIndexer
    ) {
        $this->contentAggregator = $contentAggregator;
        $this->contentPersister = $contentPersister;
        $this->contentNormalizer = $contentNormalizer;
        $this->contentCopier = $contentCopier;
        $this->contentWorkflow = $contentWorkflow;
        $this->contentIndexer = $contentIndexer;
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
