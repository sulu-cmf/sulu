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

namespace Sulu\Content\Tests\Functional\Application\ContentResolver;

use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Application\PropertyResolver\Resolver\DateTimePropertyResolver;
use Sulu\Content\Tests\Functional\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Sulu\Content\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Content\Tests\Traits\CreateExampleTrait;

class ContentResolverTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateExampleTrait;
    use CreateMediaTrait;
    use CreateTagTrait;

    private ContentResolverInterface $contentResolver;
    private ContentAggregatorInterface $contentAggregator;

    protected function setUp(): void
    {
        self::purgeDatabase();
        self::initPhpcr();

        $this->contentResolver = self::getContainer()->get('sulu_content.content_resolver');
        $this->contentAggregator = self::getContainer()->get('sulu_content.content_aggregator');
    }

    //TODO add tests for
    //account selection / contact selection / image map / blocks 2 / excerpt / seo

    public function testResolveContentDefaultFields(): void
    {
        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'text_editor' => '<p>Lorem Ipsum dolor sit amet</p>',
                        'text_line' => 'Lorem Ipsum dolor sit amet',
                        'number' => 1337,
                        'phone' => '+49 123 456 789',
                        'single_select' => 'value-2',
                        'select' => [
                            'value-2',
                            'value-3',
                        ],
                        'checkbox' => true,
                        'color' => '#ff0000',
                        'time' => '13:37',
                        'date' => '2020-01-01',
                        'datetime' => '2020-01-01T13:37:00',
                        'email' => 'example@sulu.io',
                        'external_url' => 'https://sulu.io',
                        'text_area' => 'Lorem Ipsum dolor sit amet',
                        'excerptTitle' => 'excerpt-title-1',
                        'excerptMore' => 'excerpt-more-1',
                        'excerptDescription' => 'excerpt-description-1',
                        'seoTitle' => 'seo-title-1',
                        'seoDescription' => 'seo-description-1',
                        'seoKeywords' => 'seo-keywords-1',
                        'seoCanonicalUrl' => 'https://sulu.io',
                        'seoNoIndex' => true,
                        'seoNoFollow' => true,
                        'seoHideInSitemap' => true,
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        /** @var mixed[] $result */
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var mixed[] $content */
        $content = $result['content'];

        self::assertSame('Lorem Ipsum', $content['title']);
        self::assertSame('/lorem-ipsum', $content['url']);
        self::assertSame('<p>Lorem Ipsum dolor sit amet</p>', $content['text_editor']);
        self::assertSame('Lorem Ipsum dolor sit amet', $content['text_line']);
        self::assertSame(1337, $content['number']);
        self::assertSame('+49 123 456 789', $content['phone']);
        self::assertSame('value-2', $content['single_select']);
        self::assertSame(['value-2', 'value-3'], $content['select']);
        self::assertTrue($content['checkbox']);
        self::assertSame('#ff0000', $content['color']);
        self::assertSame('13:37', $content['time']);
        self::assertSame('2020-01-01', $content['date']);

        /** @var \DateTime|null $dateTime */
        $dateTime = $content['datetime'];
        self::assertSame(\DateTime::createFromFormat(DateTimePropertyResolver::FORMAT, '2020-01-01T13:37:00')->getTimestamp(), $dateTime->getTimestamp());
        self::assertSame('example@sulu.io', $content['email']);
        self::assertSame('https://sulu.io', $content['external_url']);
        self::assertSame('Lorem Ipsum dolor sit amet', $content['text_area']);
        self::assertSame('excerpt-title-1', $content['excerpt']['excerptTitle']);
        self::assertSame('excerpt-more-1', $content['excerpt']['excerptMore']);
        self::assertSame('excerpt-description-1', $content['excerpt']['excerptDescription']);
        self::assertSame('seo-title-1', $content['seo']['seoTitle']);
        self::assertSame('seo-description-1', $content['seo']['seoDescription']);
        self::assertSame('seo-keywords-1', $content['seo']['seoKeywords']);
        self::assertSame('https://sulu.io', $content['seo']['seoCanonicalUrl']);
        self::assertTrue($content['seo']['seoNoIndex']);
        self::assertTrue($content['seo']['seoNoFollow']);
        self::assertTrue($content['seo']['seoHideInSitemap']);
    }

    public function testResolveMedias(): void
    {
        self::markTestSkipped('This test is skipped because it somehow fails in the CI.');

        // @phpstan-ignore-next-line
        $collection1 = self::createCollection(['title' => 'collection-1', 'locale' => 'en']);
        $mediaType = self::createMediaType(['name' => 'Image', 'description' => 'This is an image']);
        $media1 = self::createMedia($collection1, $mediaType, ['title' => 'media-1', 'locale' => 'en']);
        $media2 = self::createMedia($collection1, $mediaType, ['title' => 'media-2', 'locale' => 'en']);
        $media3 = self::createMedia($collection1, $mediaType, ['title' => 'media-3', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'media_selection' => [
                            'ids' => [$media1->getId(), $media2->getId(), $media3->getId()],
                            'displayOption' => 'left',
                        ],
                        'single_media_selection' => [
                            'id' => $media1->getId(),
                            'displayOption' => 'left',
                        ],
                        'excerptIcon' => [
                            'id' => $media1->getId(),
                        ],
                        'excerptImage' => [
                            'id' => $media2->getId(),
                        ],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        /** @var mixed[] $result */
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var mixed[] $content */
        $content = $result['content'];

        $mediaSelection = $content['media_selection'];
        self::assertIsArray($mediaSelection);
        self::assertCount(3, $mediaSelection);
        $contentMedia1 = $mediaSelection[0];
        self::assertInstanceOf(Media::class, $contentMedia1);
        self::assertSame($media1->getId(), $contentMedia1->getId());
        $contentMedia2 = $mediaSelection[1];
        self::assertInstanceOf(Media::class, $contentMedia2);
        self::assertSame($media2->getId(), $contentMedia2->getId());
        $contentMedia3 = $mediaSelection[2];
        self::assertInstanceOf(Media::class, $contentMedia3);
        self::assertSame($media3->getId(), $contentMedia3->getId());

        $contentMedia1 = $content['single_media_selection'];
        self::assertInstanceOf(Media::class, $contentMedia1);
        self::assertSame($media1->getId(), $contentMedia1->getId());

        /** @var mixed[] $excerpt */
        $excerpt = $content['excerpt'];
        $contentMedia1 = $excerpt['excerptIcon'];
        self::assertInstanceOf(Media::class, $contentMedia1);
        self::assertSame($media1->getId(), $contentMedia1->getId());

        $contentMedia2 = $excerpt['excerptImage'];
        self::assertInstanceOf(Media::class, $contentMedia2);
        self::assertSame($media2->getId(), $contentMedia2->getId());
    }

    public function testResolveCollections(): void
    {
        self::markTestSkipped('This test is skipped because it somehow fails in the CI.');

        // @phpstan-ignore-next-line
        $collection1 = self::createCollection(['title' => 'collection-1', 'locale' => 'en']);
        $collection2 = self::createCollection([
            'title' => 'collection-2',
            'locale' => 'en',
            'name' => 'collection-2',
            'key' => 'collection-2',
        ]);

        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'collection_selection' => [$collection1->getId(), $collection2->getId()],
                        'single_collection_selection' => $collection1->getId(),
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        /** @var mixed[] $result */
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var mixed[] $content */
        $content = $result['content'];

        $contentSelection = $content['collection_selection'];
        self::assertIsArray($contentSelection);
        self::assertCount(2, $contentSelection);
        $contentCollection1 = $contentSelection[0];
        self::assertInstanceOf(Collection::class, $contentCollection1);
        self::assertSame($collection1->getId(), $contentCollection1->getId());
        $contentCollection2 = $contentSelection[1];
        self::assertInstanceOf(Collection::class, $contentCollection2);
        self::assertSame($collection2->getId(), $contentCollection2->getId());

        $singleCollectionSelection = $content['single_collection_selection'];
        self::assertInstanceOf(Collection::class, $singleCollectionSelection);
        self::assertSame($collection1->getId(), $singleCollectionSelection->getId());
    }

    public function testResolveCategories(): void
    {
        self::markTestSkipped('This test is skipped because it somehow fails in the CI.');

        // @phpstan-ignore-next-line
        $category1 = self::createCategory(['key' => 'category-1']);
        $category2 = self::createCategory(['key' => 'category-2']);
        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'category_selection' => [$category1->getId(), $category2->getId()],
                        'single_category_selection' => $category1->getId(),
                        'excerptCategories' => [$category1->getId(), $category2->getId()],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        /** @var mixed[] $result */
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var mixed[] $content */
        $content = $result['content'];

        $categorySelection = $content['category_selection'];
        self::assertIsArray($categorySelection);
        self::assertCount(2, $categorySelection);
        $contentCategory1 = $categorySelection[0];
        self::assertSame($category1->getId(), $contentCategory1->getId());
        $contentCategory2 = $categorySelection[1];
        self::assertSame($category2->getId(), $contentCategory2->getId());

        $singleCategorySelection = $content['single_category_selection'];
        self::assertInstanceOf(Category::class, $singleCategorySelection);
        self::assertSame($category1->getId(), $singleCategorySelection->getId());

        /** @var mixed[] $excerpt */
        $excerpt = $content['excerpt'];

        $excerptCategories = $excerpt['excerptCategories'];
        self::assertIsArray($excerptCategories);
        self::assertCount(2, $excerptCategories);
        $excerptCategory1 = $excerptCategories[0];
        self::assertSame($category1->getId(), $excerptCategory1->getId());
        $excerptCategory2 = $excerptCategories[1];
        self::assertSame($category2->getId(), $excerptCategory2->getId());
    }

    public function testResolveTags(): void
    {
        $tag1 = self::createTag(['name' => 'tag-1']);
        $tag2 = self::createTag(['name' => 'tag-2']);
        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'tag_selection' => [$tag1->getName()],
                        'excerptTags' => [$tag1->getName(), $tag2->getName()],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        /** @var mixed[] $result */
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var mixed[] $content */
        $content = $result['content'];

        /** @var mixed[] $excerpt */
        $excerpt = $content['excerpt'];

        $tagSelection = $content['tag_selection'];
        self::assertIsArray($tagSelection);
        self::assertSame('tag-1', $tagSelection[0]);

        $excerptTags = $excerpt['excerptTags'];
        self::assertIsArray($excerptTags);
        self::assertSame('tag-1', $excerptTags[0]);
        self::assertSame('tag-2', $excerptTags[1]);
    }

    public function testResolveContentBlocks(): void
    {
        self::markTestSkipped('This test is skipped because it somehow fails in the CI.');

        // @phpstan-ignore-next-line
        $collection1 = self::createCollection(['title' => 'collection-1', 'locale' => 'en']);
        $mediaType = self::createMediaType(['name' => 'Image', 'description' => 'This is an image']);
        $media1 = self::createMedia($collection1, $mediaType, ['title' => 'media-1', 'locale' => 'en']);
        $media2 = self::createMedia($collection1, $mediaType, ['title' => 'media-2', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $example1 = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'full-content',
                        'title' => 'Lorem Ipsum',
                        'url' => '/lorem-ipsum',
                        'blocks' => [
                            [
                                'type' => 'editor',
                                'text_editor' => '<p>Block Level 0: Lorem Ipsum dolor sit amet</p>',
                            ],
                            [
                                'type' => 'media',
                                'media_selection' => [
                                    'ids' => [$media1->getId()],
                                ],
                            ],
                            [
                                'type' => 'block',
                                'blocks' => [
                                    [
                                        'type' => 'editor',
                                        'text_editor' => '<p>Block Level 1: Lorem Ipsum dolor sit amet</p>',
                                    ],
                                    [
                                        'type' => 'media',
                                        'media_selection' => [
                                            'ids' => [$media1->getId(), $media2->getId()],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'create_route' => true,
            ]
        );

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example1, ['locale' => 'en', 'stage' => 'live']);
        /** @var mixed[] $result */
        $result = $this->contentResolver->resolve($dimensionContent);

        /** @var mixed[] $content */
        $content = $result['content'];

        self::assertSame('Lorem Ipsum', $content['title']);
        self::assertSame('/lorem-ipsum', $content['url']);

        // block 0
        self::assertSame('editor', $content['blocks'][0]['type']);
        self::assertSame('<p>Block Level 0: Lorem Ipsum dolor sit amet</p>', $content['blocks'][0]['text_editor']);

        // block 1
        self::assertSame('media', $content['blocks'][1]['type']);
        $mediaSelection = $content['blocks'][1]['media_selection'];
        self::assertCount(1, $mediaSelection);
        $mediaApi1 = $mediaSelection[0];
        self::assertInstanceOf(Media::class, $mediaApi1);
        self::assertSame($media1->getId(), $mediaApi1->getId());

        // block 2
        self::assertSame('block', $content['blocks'][2]['type']);
        self::assertSame('<p>Block Level 1: Lorem Ipsum dolor sit amet</p>', $content['blocks'][2]['blocks'][0]['text_editor']);
        self::assertSame('editor', $content['blocks'][2]['blocks'][0]['type']);

        self::assertSame('media', $content['blocks'][2]['blocks'][1]['type']);
        $mediaSelection = $content['blocks'][2]['blocks'][1]['media_selection'];
        self::assertCount(2, $mediaSelection);
        $mediaApi1 = $content['blocks'][2]['blocks'][1]['media_selection'][0];
        self::assertInstanceOf(Media::class, $mediaApi1);
        self::assertSame($media1->getId(), $mediaApi1->getId());
        $mediaApi2 = $content['blocks'][2]['blocks'][1]['media_selection'][1];
        self::assertInstanceOf(Media::class, $mediaApi2);
        self::assertSame($media2->getId(), $mediaApi2->getId());
    }
}
