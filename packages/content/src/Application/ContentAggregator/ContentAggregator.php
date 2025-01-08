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

namespace Sulu\Content\Application\ContentAggregator;

use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Repository\DimensionContentRepositoryInterface;

class ContentAggregator implements ContentAggregatorInterface
{
    public function __construct(
        private DimensionContentRepositoryInterface $dimensionContentRepository,
        private ContentMergerInterface $contentMerger,
    ) {
    }

    public function aggregate(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): DimensionContentInterface
    {
        $dimensionContentCollection = $this->dimensionContentRepository->load($contentRichEntity, $dimensionAttributes);

        if (0 === \count($dimensionContentCollection)) {
            throw new ContentNotFoundException($contentRichEntity, $dimensionAttributes);
        }

        return $this->contentMerger->merge($dimensionContentCollection);
    }
}
