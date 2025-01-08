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

namespace Sulu\Content\Application\ContentIndexer;

use Massive\Bundle\SearchBundle\Search\QueryHit;
use Massive\Bundle\SearchBundle\Search\SearchManager;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ContentIndexer implements ContentIndexerInterface
{
    /**
     * @param SearchManager $searchManager
     */
    public function __construct(
        private SearchManagerInterface $searchManager,
        private ContentAggregatorInterface $contentAggregator,
    ) {
    }

    public function index(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): DimensionContentInterface
    {
        $dimensionContent = $this->loadDimensionContent($contentRichEntity, $dimensionAttributes);

        $this->indexDimensionContent($dimensionContent);

        return $dimensionContent;
    }

    public function indexDimensionContent(DimensionContentInterface $dimensionContent): void
    {
        if (!$dimensionContent->isMerged()) {
            return;
        }

        $this->searchManager->index($dimensionContent);
    }

    public function deindex(string $resourceKey, $id, array $dimensionAttributes = []): void
    {
        $locale = $dimensionAttributes['locale'] ?? null;
        $stage = $dimensionAttributes['stage'] ?? null;

        $search = $this->searchManager->createSearch(\sprintf('__id:"%s"', $id))
            ->indexes($this->getIndexes($resourceKey, $stage));

        if ($locale) {
            $search->locale($locale);
        }

        $searchResult = $search->execute();

        /** @var QueryHit $hit */
        foreach ($searchResult as $hit) {
            $document = $hit->getDocument();
            $this->searchManager->deindex($document, $document->getLocale());
        }
    }

    public function deindexDimensionContent(DimensionContentInterface $dimensionContent): void
    {
        if (!$dimensionContent->isMerged()) {
            return;
        }

        $this->searchManager->deindex($dimensionContent, $dimensionContent->getLocale());
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     *
     * @return T
     */
    private function loadDimensionContent(
        ContentRichEntityInterface $contentRichEntity,
        array $dimensionAttributes
    ): DimensionContentInterface {
        $locale = $dimensionAttributes['locale'] ?? null;
        $stage = $dimensionAttributes['stage'] ?? null;

        if (null === $locale || null === $stage) {
            throw new ContentNotFoundException($contentRichEntity, $dimensionAttributes);
        }

        $dimensionContent = $this->contentAggregator->aggregate($contentRichEntity, $dimensionAttributes);

        if ($locale !== $dimensionContent->getLocale()
            || $stage !== $dimensionContent->getStage()) {
            throw new ContentNotFoundException($contentRichEntity, $dimensionAttributes);
        }

        return $dimensionContent;
    }

    /**
     * @return string[]
     */
    private function getIndexes(string $resourceKey, ?string $stage): array
    {
        return \array_filter(
            $this->searchManager->getIndexNames(),
            function($indexName) use ($resourceKey, $stage) {
                if (null === $stage) {
                    return $resourceKey === $indexName || $resourceKey . '_published' === $indexName;
                }

                if (DimensionContentInterface::STAGE_DRAFT === $stage) {
                    return $resourceKey === $indexName;
                }

                if (DimensionContentInterface::STAGE_LIVE === $stage) {
                    return $resourceKey . '_published' === $indexName;
                }

                // TODO FIXME add test for this
                return false; // @codeCoverageIgnore
            }
        );
    }
}
