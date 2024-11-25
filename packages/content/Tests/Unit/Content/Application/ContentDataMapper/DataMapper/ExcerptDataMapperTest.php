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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\ExcerptDataMapper;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\CategoryFactoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\TagFactoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\TagBundle\Entity\Tag;

class ExcerptDataMapperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|TagFactoryInterface
     */
    private $tagFactory;

    /**
     * @var ObjectProphecy|CategoryFactoryInterface
     */
    private $categoryFactory;

    protected function setUp(): void
    {
        $this->tagFactory = $this->prophesize(TagFactoryInterface::class);
        $this->categoryFactory = $this->prophesize(CategoryFactoryInterface::class);
    }

    protected function createExcerptDataMapperInstance(): ExcerptDataMapper
    {
        return new ExcerptDataMapper($this->tagFactory->reveal(), $this->categoryFactory->reveal());
    }

    public function testMapNoExcerptInterface(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptImage' => ['id' => 1],
            'excerptIcon' => ['id' => 2],
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [3, 4],
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $this->tagFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $this->categoryFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
    }

    public function testMapNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $this->tagFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $this->categoryFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getExcerptTitle());
        $this->assertNull($localizedDimensionContent->getExcerptDescription());
        $this->assertNull($localizedDimensionContent->getExcerptIcon());
        $this->assertNull($localizedDimensionContent->getExcerptImage());
        $this->assertCount(0, $localizedDimensionContent->getExcerptTagNames());
        $this->assertCount(0, $localizedDimensionContent->getExcerptCategoryIds());
    }

    public function testMapUnlocalizedExcerpt(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptImage' => ['id' => 1],
            'excerptIcon' => ['id' => 2],
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [3, 4],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $tag1 = new Tag();
        $tag1->setName('Tag 1');
        $tag2 = new Tag();
        $tag2->setName('Tag 2');

        $this->tagFactory->create(['Tag 1', 'Tag 2'])->willReturn([$tag1, $tag2])->shouldBeCalled();

        $category1 = new Category();
        $category1->setId(3);
        $category2 = new Category();
        $category2->setId(4);
        $this->categoryFactory->create([3, 4])->willReturn([$category1, $category2])->shouldBeCalled();

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Excerpt Title', $localizedDimensionContent->getExcerptTitle());
        $this->assertSame('Excerpt Description', $localizedDimensionContent->getExcerptDescription());
        $this->assertSame(['id' => 1], $localizedDimensionContent->getExcerptImage());
        $this->assertSame(['id' => 2], $localizedDimensionContent->getExcerptIcon());
        $this->assertSame(['Tag 1', 'Tag 2'], $localizedDimensionContent->getExcerptTagNames());
        $this->assertSame([3, 4], $localizedDimensionContent->getExcerptCategoryIds());
    }
}
