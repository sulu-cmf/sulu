<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Category;

use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Argument;
use Sulu\Bundle\CategoryBundle\Category\KeywordManager;
use Sulu\Bundle\CategoryBundle\Category\KeywordRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Entity\Keyword;

class KeywordManagerTest extends \PHPUnit_Framework_TestCase
{
    public function provideSaveData()
    {
        return [
            [],
            [true],
            [true, true],
            [false, true],
        ];
    }

    /**
     * @dataProvider provideSaveData
     */
    public function testSave($exists = false, $has = false, $keywordString = 'Test', $locale = 'de')
    {
        $repository = $this->prophesize(KeywordRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $otherKeyword = null;
        if ($exists) {
            $otherKeyword = $this->prophesize(Keyword::class);
            $otherKeyword->getKeyword()->willReturn($keywordString);
            $otherKeyword->getLocale()->willReturn($locale);
            $otherKeyword->getId()->willReturn(15);
        }
        $repository->findByKeyword($keywordString, $locale)->willReturn($otherKeyword ? $otherKeyword->reveal() : null);

        $keyword = $this->prophesize(Keyword::class);
        $keyword->getKeyword()->willReturn($keywordString);
        $keyword->getLocale()->willReturn($locale);
        $keyword->isReferencedMultiple()->willReturn(false);
        $keyword->getId()->willReturn(null);

        $categoryTranslation = $this->prophesize(CategoryTranslation::class);
        $categoryTranslation->hasKeyword($exists ? $otherKeyword->reveal() : $keyword->reveal())->willReturn($has);
        $categoryTranslation->addKeyword($exists ? $otherKeyword->reveal() : $keyword->reveal())
            ->shouldBeCalledTimes($has ? 0 : 1);

        $category = $this->prophesize(Category::class);
        $category->findTranslationByLocale($locale)->willReturn($categoryTranslation->reveal());

        $categoryTranslation->setChanged(Argument::any())->willReturn(null);
        $category->setChanged(Argument::any())->willReturn(null);

        if ($exists) {
            $otherKeyword->addCategoryTranslation($categoryTranslation->reveal())->shouldBeCalledTimes($has ? 0 : 1);
            $keyword->removeCategoryTranslation($categoryTranslation->reveal())->shouldBeCalled();
            $keyword->isReferenced()->willReturn(true);
            $categoryTranslation->removeKeyword($keyword->reveal())->shouldBeCalled();
        } else {
            $keyword->addCategoryTranslation($categoryTranslation->reveal())->shouldBeCalledTimes($has ? 0 : 1);
        }

        $manager = new KeywordManager($repository->reveal(), $entityManager->reveal());
        $result = $manager->save($keyword->reveal(), $category->reveal());

        $this->assertEquals($exists ? $otherKeyword->reveal() : $keyword->reveal(), $result);
    }

    public function testSaveWithNotExistingCategoryTranslation()
    {
        $repository = $this->prophesize(KeywordRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $keywordString = 'my-keyword';
        $locale = 'it';

        $repository->findByKeyword($keywordString, $locale)->willReturn(null);

        $keyword = $this->prophesize(Keyword::class);
        $keyword->addCategoryTranslation(Argument::type(CategoryTranslation::class))->willReturn(null);
        $keyword->getKeyword()->willReturn($keywordString);
        $keyword->getLocale()->willReturn($locale);
        $keyword->isReferencedMultiple()->willReturn(false);
        $keyword->getId()->willReturn(null);

        $category = $this->prophesize(Category::class);
        $category->addTranslation(Argument::type(CategoryTranslation::class))->willReturn(null);
        $category->findTranslationByLocale($locale)->willReturn(false);
        $category->setChanged(Argument::any())->willReturn(null);

        $manager = new KeywordManager($repository->reveal(), $entityManager->reveal());
        $result = $manager->save($keyword->reveal(), $category->reveal());

        $this->assertEquals($keyword->reveal(), $result);
    }

    public function provideDeleteData()
    {
        return [
            [],
            [true],
        ];
    }

    /**
     * @dataProvider provideDeleteData
     */
    public function testDelete($referenced = false, $keywordString = 'Test', $locale = 'de')
    {
        $repository = $this->prophesize(KeywordRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $keyword = $this->prophesize(Keyword::class);
        $keyword->getKeyword()->willReturn($keywordString);
        $keyword->getLocale()->willReturn($locale);
        $keyword->getId()->shouldNotBeCalled();
        $keyword->isReferenced()->willReturn($referenced);

        $categoryTranslation = $this->prophesize(CategoryTranslation::class);
        $categoryTranslation->hasKeyword($keyword->reveal())->willReturn(true);
        $categoryTranslation->removeKeyword($keyword->reveal())->shouldBeCalled();
        $categoryTranslation->setChanged(Argument::any())->shouldBeCalled();

        $category = $this->prophesize(Category::class);
        $category->findTranslationByLocale($locale)->willReturn($categoryTranslation->reveal());
        $category->setChanged(Argument::any())->shouldBeCalled();

        $keyword->removeCategoryTranslation($categoryTranslation->reveal())->shouldBeCalled();

        if (!$referenced) {
            $entityManager->remove($keyword->reveal())->shouldBeCalled();
        }

        $manager = new KeywordManager($repository->reveal(), $entityManager->reveal());
        $result = $manager->delete($keyword->reveal(), $category->reveal());

        $this->assertEquals(!$referenced, $result);
    }
}
