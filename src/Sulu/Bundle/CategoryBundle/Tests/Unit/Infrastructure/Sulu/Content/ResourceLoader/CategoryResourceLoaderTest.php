<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Infrastructure\Sulu\Content\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Content\ResourceLoader\CategoryResourceLoader;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;

class CategoryResourceLoaderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<CategoryManagerInterface>
     */
    private ObjectProphecy $categoryManager;

    private CategoryResourceLoader $loader;

    public function setUp(): void
    {
        $this->categoryManager = $this->prophesize(CategoryManagerInterface::class);
        $this->loader = new CategoryResourceLoader($this->categoryManager->reveal());
    }

    public function testGetKey(): void
    {
        $this->assertSame('category', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $category1 = $this->createCategory(1);
        $category2 = $this->createCategory(3);

        $this->categoryManager->findByIds([1, 3])->willReturn([
            $category1,
            $category2,
        ])
            ->shouldBeCalled();

        $result = $this->loader->load([1, 3], 'en', []);

        $this->assertSame([
            1 => $category1,
            3 => $category2,
        ], $result);
    }

    private static function createCategory(int $id): Category
    {
        $category = new Category();
        static::setPrivateProperty($category, 'id', $id);
        $category->setKey('category-' . $id);

        return $category;
    }
}
