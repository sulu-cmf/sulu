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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentIndexer;

use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\QueryHit;
use Massive\Bundle\SearchBundle\Search\SearchManager;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Massive\Bundle\SearchBundle\Search\SearchQueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentIndexer\ContentIndexer;
use Sulu\Bundle\ContentBundle\Content\Application\ContentIndexer\ContentIndexerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class ContentIndexerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createContentIndexerInstance(
        SearchManagerInterface $searchManager,
        ContentAggregatorInterface $contentAggregator
    ): ContentIndexerInterface {
        /** @var SearchManager $searchManager */

        return new ContentIndexer($searchManager, $contentAggregator);
    }

    public function testIndex(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentAggregator->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => DimensionContentInterface::STAGE_DRAFT];
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->isMerged()->willReturn(true);
        $contentAggregator->aggregate($contentRichEntity->reveal(), $dimensionAttributes)
            ->willReturn($dimensionContent->reveal());
        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getStage()->willReturn(DimensionContentInterface::STAGE_DRAFT);

        $searchManager->index($dimensionContent->reveal())->shouldBeCalled();

        $this->assertSame(
            $dimensionContent->reveal(),
            $contentIndexer->index($contentRichEntity->reveal(), $dimensionAttributes)
        );
    }

    public function testIndexContentNotFound(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentAggregator->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => DimensionContentInterface::STAGE_DRAFT];
        $contentAggregator->aggregate($contentRichEntity->reveal(), $dimensionAttributes)
            ->willThrow(ContentNotFoundException::class);

        $this->expectException(ContentNotFoundException::class);

        $contentIndexer->index($contentRichEntity->reveal(), $dimensionAttributes);
    }

    public function testIndexWrongLocale(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentAggregator->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => DimensionContentInterface::STAGE_DRAFT];
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentAggregator->aggregate($contentRichEntity->reveal(), $dimensionAttributes)
            ->willReturn($dimensionContent->reveal());
        $dimensionContent->getLocale()->willReturn(null);
        $dimensionContent->getStage()->willReturn(DimensionContentInterface::STAGE_DRAFT);

        $this->expectException(ContentNotFoundException::class);

        $contentIndexer->index($contentRichEntity->reveal(), $dimensionAttributes);
    }

    public function testIndexInvalidDimensionAttributes(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentAggregator->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'de'];

        $this->expectException(ContentNotFoundException::class);

        $contentIndexer->index($contentRichEntity->reveal(), $dimensionAttributes);
    }

    public function testDeindex(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentAggregator->reveal()
        );

        $resourceKey = 'examples';
        $resourceId = '123';
        $dimensionAttributes = ['locale' => 'en', 'stage' => DimensionContentInterface::STAGE_DRAFT];

        $searchManager->getIndexNames()->willReturn([
            'massive_page_example-en-i18n' => 'page_example',
            'massive_page_example_published-en-i18n' => 'page_example_published',
            'massive_examples-en-i18n' => 'examples',
            'massive_examples_published-en-i18n' => 'examples_published',
            'massive_contact' => 'contact',
        ]);

        $document = $this->prophesize(Document::class);
        $document->getLocale()->willReturn('en');
        $queryHit = $this->prophesize(QueryHit::class);
        $queryHit->getDocument()->willReturn($document->reveal());

        $searchQueryBuilder = $this->prophesize(SearchQueryBuilder::class);
        $searchQueryBuilder->indexes([
            'massive_examples-en-i18n' => 'examples',
        ])->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->locale('en')->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->execute()->willReturn([$queryHit->reveal()]);
        $searchManager->createSearch('__id:"123"')->willReturn($searchQueryBuilder->reveal());

        $searchManager->deindex($document->reveal(), 'en')->shouldBeCalled();

        $contentIndexer->deindex($resourceKey, $resourceId, $dimensionAttributes);
    }

    public function testDeindexLive(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentAggregator->reveal()
        );

        $resourceKey = 'examples';
        $resourceId = '123';
        $dimensionAttributes = ['locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE];

        $searchManager->getIndexNames()->willReturn([
            'massive_page_example-en-i18n' => 'page_example',
            'massive_page_example_published-en-i18n' => 'page_example_published',
            'massive_examples-en-i18n' => 'examples',
            'massive_examples_published-en-i18n' => 'examples_published',
            'massive_contact' => 'contact',
        ]);

        $document = $this->prophesize(Document::class);
        $document->getLocale()->willReturn('en');
        $queryHit = $this->prophesize(QueryHit::class);
        $queryHit->getDocument()->willReturn($document->reveal());

        $searchQueryBuilder = $this->prophesize(SearchQueryBuilder::class);
        $searchQueryBuilder->indexes([
            'massive_examples_published-en-i18n' => 'examples_published',
        ])->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->locale('en')->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->execute()->willReturn([$queryHit->reveal()]);
        $searchManager->createSearch('__id:"123"')->willReturn($searchQueryBuilder->reveal());

        $searchManager->deindex($document->reveal(), 'en')->shouldBeCalled();

        $contentIndexer->deindex($resourceKey, $resourceId, $dimensionAttributes);
    }

    public function testDeindexNoStage(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentAggregator->reveal()
        );

        $resourceKey = 'examples';
        $resourceId = '123';
        $dimensionAttributes = ['locale' => 'en'];

        $searchManager->getIndexNames()->willReturn([
            'massive_page_example-en-i18n' => 'page_example',
            'massive_page_example_published-en-i18n' => 'page_example_published',
            'massive_examples-en-i18n' => 'examples',
            'massive_examples_published-en-i18n' => 'examples_published',
            'massive_contact' => 'contact',
        ]);

        $document1 = $this->prophesize(Document::class);
        $document1->getLocale()->willReturn('en');
        $queryHit1 = $this->prophesize(QueryHit::class);
        $queryHit1->getDocument()->willReturn($document1->reveal());
        $document2 = $this->prophesize(Document::class);
        $document2->getLocale()->willReturn('en');
        $queryHit2 = $this->prophesize(QueryHit::class);
        $queryHit2->getDocument()->willReturn($document2->reveal());

        $searchQueryBuilder = $this->prophesize(SearchQueryBuilder::class);
        $searchQueryBuilder->indexes([
            'massive_examples-en-i18n' => 'examples',
            'massive_examples_published-en-i18n' => 'examples_published',
        ])->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->locale('en')->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->execute()->willReturn([$queryHit1->reveal(), $queryHit2->reveal()]);
        $searchManager->createSearch('__id:"123"')->willReturn($searchQueryBuilder->reveal());

        $searchManager->deindex($document1->reveal(), 'en')->shouldBeCalled();
        $searchManager->deindex($document2->reveal(), 'en')->shouldBeCalled();

        $contentIndexer->deindex($resourceKey, $resourceId, $dimensionAttributes);
    }

    public function testDeindexNoLocale(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentAggregator->reveal()
        );

        $resourceKey = 'examples';
        $resourceId = '123';
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_LIVE];

        $searchManager->getIndexNames()->willReturn([
            'massive_page_example-en-i18n' => 'page_example',
            'massive_page_example_published-en-i18n' => 'page_example_published',
            'massive_examples-en-i18n' => 'examples',
            'massive_examples_published-en-i18n' => 'examples_published',
            'massive_examples-de-i18n' => 'examples',
            'massive_examples_published-de-i18n' => 'examples_published',
            'massive_contact' => 'contact',
        ]);

        $document1 = $this->prophesize(Document::class);
        $document1->getLocale()->willReturn('en');
        $queryHit1 = $this->prophesize(QueryHit::class);
        $queryHit1->getDocument()->willReturn($document1->reveal());
        $document2 = $this->prophesize(Document::class);
        $document2->getLocale()->willReturn('de');
        $queryHit2 = $this->prophesize(QueryHit::class);
        $queryHit2->getDocument()->willReturn($document2->reveal());

        $searchQueryBuilder = $this->prophesize(SearchQueryBuilder::class);
        $searchQueryBuilder->indexes([
            'massive_examples_published-en-i18n' => 'examples_published',
            'massive_examples_published-de-i18n' => 'examples_published',
        ])->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->locale(Argument::any())->shouldNotBeCalled();
        $searchQueryBuilder->execute()->willReturn([$queryHit1->reveal(), $queryHit2->reveal()]);
        $searchManager->createSearch('__id:"123"')->willReturn($searchQueryBuilder->reveal());

        $searchManager->deindex($document1->reveal(), 'en')->shouldBeCalled();
        $searchManager->deindex($document2->reveal(), 'de')->shouldBeCalled();

        $contentIndexer->deindex($resourceKey, $resourceId, $dimensionAttributes);
    }

    public function testDeindexWithoutDimensionAttributes(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentAggregator->reveal()
        );

        $resourceKey = 'examples';
        $resourceId = '123';

        $searchManager->getIndexNames()->willReturn([
            'massive_page_example-en-i18n' => 'page_example',
            'massive_page_example_published-en-i18n' => 'page_example_published',
            'massive_examples-de-i18n' => 'examples',
            'massive_examples_published-de-i18n' => 'examples_published',
            'massive_examples-en-i18n' => 'examples',
            'massive_examples_published-en-i18n' => 'examples_published',
            'massive_contact' => 'contact',
        ]);

        $document1 = $this->prophesize(Document::class);
        $document1->getLocale()->willReturn('en');
        $queryHit1 = $this->prophesize(QueryHit::class);
        $queryHit1->getDocument()->willReturn($document1->reveal());
        $document2 = $this->prophesize(Document::class);
        $document2->getLocale()->willReturn('de');
        $queryHit2 = $this->prophesize(QueryHit::class);
        $queryHit2->getDocument()->willReturn($document2->reveal());
        $document3 = $this->prophesize(Document::class);
        $document3->getLocale()->willReturn('en');
        $queryHit3 = $this->prophesize(QueryHit::class);
        $queryHit3->getDocument()->willReturn($document3->reveal());

        $searchQueryBuilder = $this->prophesize(SearchQueryBuilder::class);
        $searchQueryBuilder->indexes([
            'massive_examples-de-i18n' => 'examples',
            'massive_examples_published-de-i18n' => 'examples_published',
            'massive_examples-en-i18n' => 'examples',
            'massive_examples_published-en-i18n' => 'examples_published',
        ])->willReturn($searchQueryBuilder->reveal());
        $searchQueryBuilder->locale(Argument::any())->shouldNotBeCalled();
        $searchQueryBuilder->execute()->willReturn([$queryHit1->reveal(), $queryHit2->reveal(), $queryHit3->reveal()]);
        $searchManager->createSearch('__id:"123"')->willReturn($searchQueryBuilder->reveal());

        $searchManager->deindex($document1->reveal(), 'en')->shouldBeCalled();
        $searchManager->deindex($document2->reveal(), 'de')->shouldBeCalled();
        $searchManager->deindex($document3->reveal(), 'en')->shouldBeCalled();

        $contentIndexer->deindex($resourceKey, $resourceId);
    }

    public function testIndexDimensionContent(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentResolver = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentResolver->reveal()
        );

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->isMerged()->willReturn(true);

        $searchManager->index($dimensionContent->reveal())->shouldBeCalled();

        $contentIndexer->indexDimensionContent($dimensionContent->reveal());
    }

    public function testIndexDimensionContentNotMerged(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentResolver = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentResolver->reveal()
        );

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->isMerged()->willReturn(false);

        $searchManager->index($dimensionContent->reveal())->shouldNotBeCalled();

        $contentIndexer->indexDimensionContent($dimensionContent->reveal());
    }

    public function testDeindexDimensionContent(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentResolver = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentResolver->reveal()
        );

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->isMerged()->willReturn(true);

        $searchManager->deindex($dimensionContent->reveal(), 'en')->shouldBeCalled();

        $contentIndexer->deindexDimensionContent($dimensionContent->reveal());
    }

    public function testDeindexDimensionContentNotMerged(): void
    {
        $searchManager = $this->prophesize(SearchManagerInterface::class);
        $contentResolver = $this->prophesize(ContentAggregatorInterface::class);

        $contentIndexer = $this->createContentIndexerInstance(
            $searchManager->reveal(),
            $contentResolver->reveal()
        );

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->isMerged()->willReturn(false);

        $searchManager->deindex($dimensionContent->reveal())->shouldNotBeCalled();

        $contentIndexer->deindexDimensionContent($dimensionContent->reveal());
    }
}
