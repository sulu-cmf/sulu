<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Content\Types;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Content\Types\SingleCategorySelection;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;

class SingleCategorySelectionTest extends TestCase
{
    use ProphecyTrait;

    public function testGetContentData(): void
    {
        $entity = $this->prophesize(CategoryInterface::class);

        $category = $this->prophesize(Category::class);
        $category->toArray()->willReturn(['title' => 'Sulu is awesome']);

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = $this->prophesize(Property::class);
        $property->getValue()->willReturn(1);
        $property->getStructure()->willReturn($structure->reveal());

        $categoryManager = $this->prophesize(CategoryManagerInterface::class);
        $categoryManager->findById(1)->willReturn($entity);
        $categoryManager->getApiObject($entity, 'de')->willReturn($category);

        $categoryList = new SingleCategorySelection($categoryManager->reveal());

        $result = $categoryList->getContentData($property->reveal());

        $this->assertEquals(['title' => 'Sulu is awesome'], $result);
    }

    public function testGetContentDataNullPropertyValue(): void
    {
        $property = $this->prophesize(Property::class);
        $property->getValue()->willReturn(null);

        $categoryManager = $this->prophesize(CategoryManagerInterface::class);
        $categoryManager->findById(Argument::any())->shouldNotBeCalled();

        $categoryList = new SingleCategorySelection($categoryManager->reveal());

        $result = $categoryList->getContentData($property->reveal());

        $this->assertNull($result);
    }
}
