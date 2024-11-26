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

namespace Sulu\Content\Application\ContentCopier;

use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ContentCopier implements ContentCopierInterface
{
    /**
     * @var ContentAggregatorInterface
     */
    private $contentAggregator;

    /**
     * @var ContentMergerInterface
     */
    private $contentMerger;

    /**
     * @var ContentPersisterInterface
     */
    private $contentPersister;

    /**
     * @var ContentNormalizerInterface
     */
    private $contentNormalizer;

    public function __construct(
        ContentAggregatorInterface $contentAggregator,
        ContentMergerInterface $contentMerger,
        ContentPersisterInterface $contentPersister,
        ContentNormalizerInterface $contentNormalizer
    ) {
        $this->contentAggregator = $contentAggregator;
        $this->contentMerger = $contentMerger;
        $this->contentPersister = $contentPersister;
        $this->contentNormalizer = $contentNormalizer;
    }

    public function copy(
        ContentRichEntityInterface $sourceContentRichEntity,
        array $sourceDimensionAttributes,
        ContentRichEntityInterface $targetContentRichEntity,
        array $targetDimensionAttributes,
        array $options = []
    ): DimensionContentInterface {
        $sourceDimensionContent = $this->contentAggregator->aggregate($sourceContentRichEntity, $sourceDimensionAttributes);

        return $this->copyFromDimensionContent($sourceDimensionContent, $targetContentRichEntity, $targetDimensionAttributes, $options);
    }

    public function copyFromDimensionContentCollection(
        DimensionContentCollectionInterface $dimensionContentCollection,
        ContentRichEntityInterface $targetContentRichEntity,
        array $targetDimensionAttributes,
        array $options = []
    ): DimensionContentInterface {
        $sourceDimensionContent = $this->contentMerger->merge($dimensionContentCollection);

        return $this->copyFromDimensionContent($sourceDimensionContent, $targetContentRichEntity, $targetDimensionAttributes, $options);
    }

    public function copyFromDimensionContent(
        DimensionContentInterface $dimensionContent,
        ContentRichEntityInterface $targetContentRichEntity,
        array $targetDimensionAttributes,
        array $options = []
    ): DimensionContentInterface {
        $data = \array_replace($this->contentNormalizer->normalize($dimensionContent), $options['data'] ?? []);

        foreach (($options['ignoredAttributes'] ?? []) as $ignoredAttribute) {
            unset($data[$ignoredAttribute]);
        }

        return $this->contentPersister->persist($targetContentRichEntity, $data, $targetDimensionAttributes);
    }
}
