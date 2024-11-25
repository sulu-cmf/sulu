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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentNormalizer;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\ContentNormalizer;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\DimensionContentNormalizer;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\ExcerptNormalizer;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\RoutableNormalizer;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\TemplateNormalizer;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\WorkflowNormalizer;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowTrait;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class ContentNormalizerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createContentNormalizerInstance(): ContentNormalizerInterface
    {
        return new ContentNormalizer([
            new DimensionContentNormalizer(),
            new ExcerptNormalizer(),
            new TemplateNormalizer(),
            new WorkflowNormalizer(),
            new RoutableNormalizer(),
        ]);
    }

    public function testResolveSimple(): void
    {
        $contentRichEntityMock = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntityMock->getId()->willReturn(5);

        $object = new class($contentRichEntityMock->reveal()) implements DimensionContentInterface {
            use DimensionContentTrait;

            /**
             * @var ContentRichEntityInterface<self>
             */
            protected $resource;

            /**
             * @param ContentRichEntityInterface<self> $resource
             */
            public function __construct(ContentRichEntityInterface $resource)
            {
                $this->resource = $resource;
                $this->locale = 'de';
                $this->stage = 'live';
                $this->ghostLocale = 'de';
                $this->availableLocales = ['de'];
            }

            public static function getResourceKey(): string
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }

            /**
             * @return ContentRichEntityInterface<self>
             */
            public function getResource(): ContentRichEntityInterface
            {
                return $this->resource;
            }
        };

        $contentNormalizer = $this->createContentNormalizerInstance();
        $this->assertSame([
            'availableLocales' => ['de'],
            'ghostLocale' => 'de',
            'id' => 5,
            'locale' => 'de',
            'stage' => 'live',
        ], $contentNormalizer->normalize($object));
    }

    public function testResolveFull(): void
    {
        $contentRichEntityMock = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntityMock->getId()->willReturn(5);

        $object = new class($contentRichEntityMock->reveal()) implements DimensionContentInterface, ExcerptInterface, SeoInterface, TemplateInterface, WorkflowInterface, RoutableInterface {
            use DimensionContentTrait;
            use ExcerptTrait;
            use RoutableTrait;
            use SeoTrait;
            use TemplateTrait;
            use WorkflowTrait;

            /**
             * @var ContentRichEntityInterface<self>
             */
            protected $resource;

            /**
             * @param ContentRichEntityInterface<self> $resource
             */
            public function __construct(ContentRichEntityInterface $resource)
            {
                $this->resource = $resource;
                $this->locale = 'de';
                $this->stage = 'live';
            }

            public static function getResourceKey(): string
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }

            public static function getTemplateType(): string
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }

            /**
             * @return ContentRichEntityInterface<self>
             */
            public function getResource(): ContentRichEntityInterface
            {
                return $this->resource;
            }
        };

        $object->setGhostLocale('en');
        $object->addAvailableLocale('en');
        $object->addAvailableLocale('de');

        $tag1 = $this->prophesize(TagInterface::class);
        $tag1->getId()->willReturn(1);
        $tag1->getName()->willReturn('Tag 1');
        $tag2 = $this->prophesize(TagInterface::class);
        $tag2->getId()->willReturn(2);
        $tag2->getName()->willReturn('Tag 2');

        $category1 = $this->prophesize(CategoryInterface::class);
        $category1->getId()->willReturn(3);
        $category2 = $this->prophesize(CategoryInterface::class);
        $category2->getId()->willReturn(4);

        $object->setSeoTitle('Seo Title');
        $object->setSeoDescription('Seo Description');
        $object->setSeoKeywords('Seo Keyword 1, Seo Keyword 2');
        $object->setSeoCanonicalUrl('https://caninical.localhost/');
        $object->setSeoNoIndex(true);
        $object->setSeoNoFollow(true);
        $object->setSeoHideInSitemap(true);

        $object->setExcerptTitle('Excerpt Title');
        $object->setExcerptDescription('Excerpt Description');
        $object->setExcerptMore('Excerpt More');
        $object->setExcerptImage(['id' => 8]);
        $object->setExcerptIcon(['id' => 9]);
        $object->setExcerptTags([$tag1->reveal(), $tag2->reveal()]);
        $object->setExcerptCategories([$category1->reveal(), $category2->reveal()]);

        $object->setTemplateKey('template-key');
        $object->setTemplateData(['someTemplate' => 'data']);

        $published = new \DateTimeImmutable('2020-02-02T12:30:00+00:00');
        $object->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_DRAFT);
        $object->setWorkflowPublished($published);

        $contentNormalizer = $this->createContentNormalizerInstance();

        $this->assertSame([
            'availableLocales' => ['en', 'de'],
            'excerptCategories' => [
                3,
                4,
            ],
            'excerptDescription' => 'Excerpt Description',
            'excerptIcon' => ['id' => 9],
            'excerptImage' => ['id' => 8],
            'excerptMore' => 'Excerpt More',
            'excerptTags' => [
                'Tag 1',
                'Tag 2',
            ],
            'excerptTitle' => 'Excerpt Title',
            'ghostLocale' => 'en',
            'id' => 5,
            'locale' => 'de',
            'published' => '2020-02-02T12:30:00+00:00',
            'publishedState' => false,
            'seoCanonicalUrl' => 'https://caninical.localhost/',
            'seoDescription' => 'Seo Description',
            'seoHideInSitemap' => true,
            'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
            'seoNoFollow' => true,
            'seoNoIndex' => true,
            'seoTitle' => 'Seo Title',
            'someTemplate' => 'data',
            'stage' => 'live',
            'template' => 'template-key',
            'workflowPlace' => 'draft',
        ], $contentNormalizer->normalize($object));
    }
}
