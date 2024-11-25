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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptTrait;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class ExcerptTraitTest extends TestCase
{
    protected function getExcerptInstance(): ExcerptInterface
    {
        return new class() implements ExcerptInterface {
            use ExcerptTrait;
        };
    }

    public function testGetSetExcerptTitle(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptTitle());
        $model->setExcerptTitle('Excerpt Title');
        $this->assertSame('Excerpt Title', $model->getExcerptTitle());
    }

    public function testGetSetExcerptDescription(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptDescription());
        $model->setExcerptDescription('Excerpt Description');
        $this->assertSame('Excerpt Description', $model->getExcerptDescription());
    }

    public function testGetSetExcerptMore(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptMore());
        $model->setExcerptMore('Excerpt More');
        $this->assertSame('Excerpt More', $model->getExcerptMore());
    }

    public function testGetSetExcerptImageId(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptImage());
        $model->setExcerptImage(['id' => 1]);
        $this->assertNotNull($model->getExcerptImage());
        $this->assertSame(['id' => 1], $model->getExcerptImage());
    }

    public function testGetSetExcerptIconId(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptIcon());
        $model->setExcerptIcon(['id' => 2]);
        $this->assertNotNull($model->getExcerptIcon());
        $this->assertSame(['id' => 2], $model->getExcerptIcon());
    }

    public function testGetSetExcerptTags(): void
    {
        $tag1 = $this->createTag(1);
        $tag2 = $this->createTag(2);

        $model = $this->getExcerptInstance();
        $this->assertEmpty($model->getExcerptTags());
        $model->setExcerptTags([$tag1, $tag2]);
        $this->assertSame([1, 2], \array_map(function(TagInterface $tag) {
            return $tag->getId();
        }, $model->getExcerptTags()));
    }

    public function testGetSetExcerptCategories(): void
    {
        $category1 = $this->createCategory(1);
        $category2 = $this->createCategory(2);

        $model = $this->getExcerptInstance();
        $this->assertEmpty($model->getExcerptCategories());
        $model->setExcerptCategories([$category1, $category2]);
        $this->assertSame([1, 2], \array_map(function(CategoryInterface $category) {
            return $category->getId();
        }, $model->getExcerptCategories()));
    }

    private function createTag(int $id): TagInterface
    {
        $tag = new Tag();
        $tag->setId($id);

        return $tag;
    }

    private function createCategory(int $id): CategoryInterface
    {
        $category = new Category();
        $category->setId($id);

        return $category;
    }
}
