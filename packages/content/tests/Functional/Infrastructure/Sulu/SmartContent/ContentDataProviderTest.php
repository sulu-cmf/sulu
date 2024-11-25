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

namespace Sulu\Content\Tests\Functional\Infrastructure\Sulu\SmartContent;

use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Content\Infrastructure\Sulu\SmartContent\Provider\ContentDataProvider;
use Sulu\Content\Tests\Traits\AssertSnapshotTrait;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateExampleTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\ResourceItemInterface;

class ContentDataProviderTest extends SuluTestCase
{
    use AssertSnapshotTrait;
    use CreateCategoryTrait;
    use CreateExampleTrait;
    use CreateTagTrait;

    /**
     * @var ContentDataProvider
     */
    private $contentDataProvider;

    /**
     * @var CategoryInterface
     */
    private static $categoryFoo;

    /**
     * @var CategoryInterface
     */
    private static $categoryBar;

    /**
     * @var CategoryInterface
     */
    private static $categoryBaz;

    /**
     * @var TagInterface
     */
    private static $tagA;

    /**
     * @var TagInterface
     */
    private static $tagB;

    /**
     * @var TagInterface
     */
    private static $tagC;

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();
        parent::setUpBeforeClass();

        self::$categoryFoo = static::createCategory([
            'en' => [
                'title' => 'Foo',
            ],
            'de' => [
                'title' => 'Foo DE',
            ],
        ]);

        self::$categoryBar = static::createCategory([
            'en' => [
                'title' => 'Bar',
            ],
        ]);

        self::$categoryBaz = static::createCategory([
            'de' => [
                'title' => 'Baz',
            ],
        ]);

        self::$tagA = static::createTag([
            'name' => 'tagA',
        ]);

        self::$tagB = static::createTag([
            'name' => 'tagB',
        ]);

        self::$tagC = static::createTag([
            'name' => 'tagC',
        ]);

        static::getEntityManager()->flush();

        // Example 1
        $example1 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example without categories without tags',
                ],
            ],
            'de' => [
                'draft' => [
                    'title' => 'example without categories without tags draft',
                    'excerptCategories' => [
                        self::$categoryFoo->getId(),
                        self::$categoryBar->getId(),
                    ],
                    'excerptTags' => [
                        self::$tagB->getName(),
                        self::$tagC->getName(),
                    ],
                ],
                'live' => [
                    'title' => 'example without categories without tags',
                ],
            ],
        ]);

        // Example 2
        $example2 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example with some categories without tags',
                    'excerptCategories' => [
                        self::$categoryFoo->getId(),
                    ],
                ],
            ],
            'de' => [
                'draft' => [
                    'title' => 'example with some categories without tags unpublished',
                    'excerptCategories' => [
                        self::$categoryFoo->getId(),
                    ],
                    'excerptTags' => [],
                ],
            ],
        ]);

        // Example 3
        $example3 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example with all categories without tags',
                    'excerptCategories' => [
                        self::$categoryFoo->getId(),
                        self::$categoryBar->getId(),
                        self::$categoryBaz->getId(),
                    ],
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'example with all categories without tags',
                    'excerptCategories' => [
                        self::$categoryFoo->getId(),
                        self::$categoryBar->getId(),
                        self::$categoryBaz->getId(),
                    ],
                    'excerptTags' => [],
                ],
            ],
        ]);

        // Example 4
        $example4 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example without categories with some tags',
                    'excerptTags' => [
                        self::$tagA->getName(),
                    ],
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'example without categories with some tags',
                    'excerptTags' => [
                        self::$tagA->getName(),
                    ],
                ],
            ],
        ]);

        // Example 5
        $example5 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example without categories with all tags',
                    'excerptTags' => [
                        self::$tagA->getName(),
                        self::$tagB->getName(),
                        self::$tagC->getName(),
                    ],
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'example without categories with all tags',
                    'excerptTags' => [
                        self::$tagA->getName(),
                        self::$tagB->getName(),
                        self::$tagC->getName(),
                    ],
                ],
            ],
        ]);

        // Example 6
        $example6 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example with some categories with some tags',
                    'excerptCategories' => [
                        self::$categoryBar->getId(),
                    ],
                    'excerptTags' => [
                        self::$tagB->getName(),
                    ],
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'example with some categories with some tags',
                    'excerptCategories' => [
                        self::$categoryBar->getId(),
                    ],
                    'excerptTags' => [
                        self::$tagB->getName(),
                    ],
                ],
            ],
        ]);

        // Example 7
        $example7 = static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example with all categories with all tags',
                    'excerptCategories' => [
                        self::$categoryFoo->getId(),
                        self::$categoryBar->getId(),
                        self::$categoryBaz->getId(),
                    ],
                    'excerptTags' => [
                        self::$tagA->getName(),
                        self::$tagB->getName(),
                        self::$tagC->getName(),
                    ],
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'example with all categories with all tags',
                    'excerptCategories' => [
                        self::$categoryFoo->getId(),
                        self::$categoryBar->getId(),
                        self::$categoryBaz->getId(),
                    ],
                    'excerptTags' => [
                        self::$tagA->getName(),
                        self::$tagB->getName(),
                        self::$tagC->getName(),
                    ],
                ],
            ],
        ]);

        // Example 8
        static::createExample([
            'en' => [
                'live' => [
                    'title' => 'example with non default template',
                    'template' => 'example-2',
                ],
            ],
        ]);

        static::getEntityManager()->flush();
    }

    protected function setUp(): void
    {
        $this->contentDataProvider = $this->getContainer()->get('example_test.example_data_provider');
    }

    public function testResolveDataItems(): void
    {
        foreach ($this->filters() as $dataset) {
            list($name, $locale, $page, $pageSize, $limit, $filters, $expectedCount, $expectedHasNextPage) = $dataset;

            /** @var string $name */
            /** @var string $locale */
            /** @var int|null $page */
            /** @var int|null $pageSize */
            /** @var int|null $limit */
            /** @var mixed[] $filters */
            /** @var int $expectedCount */
            /** @var bool $expectedHasNextPage */
            $dataProviderResult = $this->contentDataProvider->resolveDataItems(
                $filters, // @phpstan-ignore-line
                [],
                [
                    'locale' => $locale,
                ],
                $limit,
                $page ?: 1,
                $pageSize
            );

            $dataItems = $this->mapDataProviderResult($dataProviderResult);

            $this->assertCount($expectedCount, $dataItems);
            $this->assertSame($expectedHasNextPage, $dataProviderResult->getHasNextPage());
        }
    }

    public function testResolveDataItemsSnapshot(): void
    {
        /** @var int $limit */
        $limit = null;
        /** @var int $page */
        $page = null;
        /** @var int $pageSize */
        $pageSize = null;

        $dataProviderResult = $this->contentDataProvider->resolveDataItems(
            [
                'sortBy' => 'title',
                'sortMethod' => 'desc',
            ],
            [],
            [
                'locale' => 'en',
            ],
            $limit,
            $page,
            $pageSize
        );

        $dataItems = $this->mapDataProviderResult($dataProviderResult);

        $this->assertArraySnapshot('data_items.json', $dataItems);
    }

    public function testResolveResourceItemsSnapshot(): void
    {
        /** @var int $limit */
        $limit = null;
        /** @var int $page */
        $page = null;
        /** @var int $pageSize */
        $pageSize = null;

        $dataProviderResult = $this->contentDataProvider->resolveResourceItems(
            [
                'sortBy' => 'title',
                'sortMethod' => 'asc',
            ],
            [],
            [
                'locale' => 'de',
            ],
            $limit,
            $page,
            $pageSize
        );

        $resourceItems = $this->mapDataProviderResult($dataProviderResult);

        $this->assertArraySnapshot('resource_items.json', $resourceItems);
    }

    /**
     * This method can't be a phpunit dataProvider, because then it wouldn't be possible to access the categories, because they don't exist at the time a dataProvider is called.
     *
     * @return array<int, array{
     *     0: string,
     *     1: string,
     *     2: int|null,
     *     3: int|null,
     *     4: int|null,
     *     5: mixed[],
     *     6: int,
     *     7: bool,
     * }>
     */
    public function filters(): array
    {
        return [
            [
                'noFilters',
                'en',
                null,
                null,
                null,
                [],
                8,
                false,
            ],
            [
                'noFiltersLimited',
                'en',
                null,
                null,
                5,
                [],
                5,
                false,
            ],
            [
                'noFiltersPaginated',
                'en',
                2,
                2,
                3,
                [],
                1,
                false,
            ],
            [
                'noFiltersPaginatedWithoutLimit',
                'en',
                3,
                2,
                null,
                [],
                2,
                true,
            ],
            [
                'withAllCategoriesOR',
                'en',
                null,
                null,
                null,
                [
                    'categories' => [
                        self::$categoryFoo->getId(),
                        self::$categoryBar->getId(),
                        self::$categoryBaz->getId(),
                    ],
                    'categoryOperator' => 'OR',
                ],
                4,
                false,
            ],
            [
                'withAllTagsOR',
                'en',
                null,
                null,
                null,
                [
                    'tags' => [
                        self::$tagA,
                        self::$tagB,
                        self::$tagC,
                    ],
                    'tagOperator' => 'OR',
                ],
                4,
                false,
            ],
            [
                'withAllCategoriesORAllTagsOR',
                'en',
                null,
                null,
                null,
                [
                    'categories' => [
                        self::$categoryFoo->getId(),
                        self::$categoryBar->getId(),
                        self::$categoryBaz->getId(),
                    ],
                    'categoryOperator' => 'OR',
                    'tags' => [
                        self::$tagA,
                        self::$tagB,
                        self::$tagC,
                    ],
                    'tagOperator' => 'OR',
                ],
                2,
                false,
            ],
            [
                'withSomeCategoriesANDSomeWebsiteCategoriesOR',
                'en',
                null,
                null,
                null,
                [
                    'categories' => [
                        self::$categoryFoo->getId(),
                    ],
                    'categoryOperator' => 'AND',
                    'websiteCategories' => [
                        self::$categoryBar->getId(),
                        self::$categoryBaz->getId(),
                    ],
                    'websiteCategoriesOperator' => 'OR',
                ],
                2,
                false,
            ],
            [
                'withAllCategories',
                'en',
                null,
                null,
                null,
                [
                    'categories' => [
                        self::$categoryFoo->getId(),
                    ],
                    'websiteCategories' => [
                        self::$categoryBar->getId(),
                        self::$categoryBaz->getId(),
                    ],
                    'categoryOperator' => 'AND',
                    'websiteCategoriesOperator' => 'AND',
                ],
                2,
                false,
            ],
            [
                'withAllTags',
                'en',
                null,
                null,
                null,
                [
                    'tags' => [
                        self::$tagA,
                        self::$tagB,
                    ],
                    'websiteTags' => [
                        self::$tagC,
                    ],
                    'tagOperator' => 'AND',
                    'websiteTagsOperator' => 'AND',
                ],
                2,
                false,
            ],
            [
                'withAllCategoriesAndAllTags',
                'en',
                null,
                null,
                null,
                [
                    'categories' => [
                        self::$categoryFoo->getId(),
                        self::$categoryBar->getId(),
                        self::$categoryBaz->getId(),
                    ],
                    'categoryOperator' => 'AND',
                    'tags' => [
                        self::$tagA,
                        self::$tagB,
                        self::$tagC,
                    ],
                    'tagOperator' => 'AND',
                ],
                1,
                false,
            ],
            [
                'withOneType',
                'en',
                null,
                null,
                null,
                [
                    'types' => [
                        'example-2',
                    ],
                ],
                1,
                false,
            ],
            [
                'withAllTypes',
                'en',
                null,
                null,
                null,
                [
                    'types' => [
                        'default',
                        'example-2',
                    ],
                ],
                8,
                false,
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    private function mapDataProviderResult(DataProviderResult $dataProviderResult): array
    {
        return \array_map(function(ResourceItemInterface $item) {
            /** @var ResourceItemInterface&ArrayAccessItem $item */
            return [
                'id' => $item->getId(),
                'excerptCategories' => $item['excerptCategories'],
                'excerptTags' => $item['excerptTags'],
                'published' => $item['published'],
                'publishedState' => $item['publishedState'],
                'title' => $item['title'],
                'url' => $item['url'],
            ];
        }, $dataProviderResult->getItems());
    }
}
