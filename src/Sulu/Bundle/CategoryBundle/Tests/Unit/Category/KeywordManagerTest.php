<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Category;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Category\KeywordManager;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryKeywordAddedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryKeywordModifiedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryKeywordRemovedEvent;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordRepositoryInterface;

class KeywordManagerTest extends TestCase
{
    use ProphecyTrait;

    public static function provideSaveData()
    {
        return [
            [],
            [true],
            [true, true],
            [false, true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSaveData')]
    public function testSave($exists = false, $has = false, $keywordString = 'Test', $locale = 'de'): void
    {
        $repository = $this->prophesize(KeywordRepositoryInterface::class);
        $categoryTranslationRepository = $this->prophesize(CategoryTranslationRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $otherKeyword = null;
        if ($exists) {
            $otherKeyword = $this->prophesize(KeywordInterface::class);
            $otherKeyword->getKeyword()->willReturn($keywordString);
            $otherKeyword->getLocale()->willReturn($locale);
            $otherKeyword->getId()->willReturn(15);
        }
        $repository->findByKeyword($keywordString, $locale)->willReturn($otherKeyword ? $otherKeyword->reveal() : null);

        $keyword = $this->prophesize(KeywordInterface::class);
        $keyword->getKeyword()->willReturn($keywordString);
        $keyword->getLocale()->willReturn($locale);
        $keyword->isReferencedMultiple()->willReturn(false);
        $keyword->getId()->willReturn(null);

        $categoryTranslation = $this->prophesize(CategoryTranslationInterface::class);
        $categoryTranslation->hasKeyword($exists ? $otherKeyword->reveal() : $keyword->reveal())->willReturn($has);
        $categoryTranslation->addKeyword($exists ? $otherKeyword->reveal() : $keyword->reveal())
            ->shouldBeCalledTimes($has ? 0 : 1);

        $category = $this->prophesize(CategoryInterface::class);
        $category->findTranslationByLocale($locale)->willReturn($categoryTranslation->reveal());
        $categoryTranslation->getCategory()->willReturn($category->reveal());

        $categoryTranslation->setChanged(Argument::any())->willReturn(null);
        $category->setChanged(Argument::any())->willReturn(null);

        if ($exists) {
            $keyword->removeCategoryTranslation($categoryTranslation->reveal())->shouldBeCalled();
            $keyword->isReferenced()->willReturn(true);
            $categoryTranslation->removeKeyword($keyword->reveal())->shouldBeCalled();

            $otherKeyword->addCategoryTranslation($categoryTranslation->reveal())->shouldBeCalledTimes($has ? 0 : 1);
            $otherKeyword->getCategoryTranslations()->willReturn([$categoryTranslation->reveal()]);

            $domainEventCollector->collect(Argument::type(CategoryKeywordModifiedEvent::class))->shouldBeCalled();
        } else {
            $keyword->addCategoryTranslation($categoryTranslation->reveal())->shouldBeCalledTimes($has ? 0 : 1);
            $keyword->getCategoryTranslations()->willReturn([$categoryTranslation->reveal()]);

            $domainEventCollector->collect(Argument::type(CategoryKeywordAddedEvent::class))->shouldBeCalled();
        }

        $manager = new KeywordManager(
            $repository->reveal(),
            $categoryTranslationRepository->reveal(),
            $entityManager->reveal(),
            $domainEventCollector->reveal()
        );
        $result = $manager->save($keyword->reveal(), $category->reveal());

        $this->assertEquals($exists ? $otherKeyword->reveal() : $keyword->reveal(), $result);
    }

    public function testSaveWithNotExistingCategoryTranslation(): void
    {
        $repository = $this->prophesize(KeywordRepositoryInterface::class);
        $categoryTranslationRepository = $this->prophesize(CategoryTranslationRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $keywordString = 'my-keyword';
        $locale = 'it';

        $repository->findByKeyword($keywordString, $locale)->willReturn(null);

        $keyword = $this->prophesize(KeywordInterface::class);
        $category = $this->prophesize(CategoryInterface::class);

        $categoryTranslation = $this->prophesize(CategoryTranslationInterface::class);
        $categoryTranslationRepository->createNew()->willReturn($categoryTranslation->reveal());

        $categoryTranslation->setLocale('it')->shouldBeCalled();
        $categoryTranslation->setTranslation('')->shouldBeCalled();
        $categoryTranslation->setCategory($category->reveal())->shouldBeCalled();
        $categoryTranslation->setChanged(Argument::any())->willReturn(null);
        $categoryTranslation->getCategory()->willReturn($category->reveal());
        $categoryTranslation->hasKeyword($keyword->reveal())->willReturn(false);
        $categoryTranslation->addKeyword($keyword->reveal())->shouldBeCalled();

        $keyword->addCategoryTranslation($categoryTranslation->reveal())->willReturn($keyword->reveal());
        $keyword->getCategoryTranslations()->willReturn([$categoryTranslation->reveal()]);
        $keyword->getKeyword()->willReturn($keywordString);
        $keyword->getLocale()->willReturn($locale);
        $keyword->isReferencedMultiple()->willReturn(false);
        $keyword->getId()->willReturn(null);

        $category->addTranslation($categoryTranslation->reveal())->willReturn($category->reveal());
        $category->findTranslationByLocale($locale)->willReturn(false);
        $category->setChanged(Argument::any())->willReturn(null);

        $domainEventCollector->collect(Argument::type(CategoryKeywordAddedEvent::class))->shouldBeCalled();

        $manager = new KeywordManager(
            $repository->reveal(),
            $categoryTranslationRepository->reveal(),
            $entityManager->reveal(),
            $domainEventCollector->reveal()
        );
        $result = $manager->save($keyword->reveal(), $category->reveal());

        $this->assertEquals($keyword->reveal(), $result);
    }

    public static function provideDeleteData()
    {
        return [
            [],
            [true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDeleteData')]
    public function testDelete($referenced = false, $keywordString = 'Test', $locale = 'de'): void
    {
        $repository = $this->prophesize(KeywordRepositoryInterface::class);
        $categoryTranslationRepository = $this->prophesize(CategoryTranslationRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $keyword = $this->prophesize(KeywordInterface::class);
        $keyword->getKeyword()->willReturn($keywordString);
        $keyword->getLocale()->willReturn($locale);
        $keyword->getId()->willReturn(1234);
        $keyword->isReferenced()->willReturn($referenced);

        $categoryTranslation = $this->prophesize(CategoryTranslationInterface::class);
        $categoryTranslation->hasKeyword($keyword->reveal())->willReturn(true);
        $categoryTranslation->removeKeyword($keyword->reveal())->shouldBeCalled();
        $categoryTranslation->setChanged(Argument::any())->shouldBeCalled();

        $category = $this->prophesize(CategoryInterface::class);
        $category->findTranslationByLocale($locale)->willReturn($categoryTranslation->reveal());
        $category->setChanged(Argument::any())->shouldBeCalled();

        $domainEventCollector->collect(Argument::type(CategoryKeywordRemovedEvent::class))->shouldBeCalled();

        $keyword->removeCategoryTranslation($categoryTranslation->reveal())->shouldBeCalled();

        if (!$referenced) {
            $entityManager->remove($keyword->reveal())->shouldBeCalled();
        }

        $manager = new KeywordManager(
            $repository->reveal(),
            $categoryTranslationRepository->reveal(),
            $entityManager->reveal(),
            $domainEventCollector->reveal()
        );
        $result = $manager->delete($keyword->reveal(), $category->reveal());

        $this->assertEquals(!$referenced, $result);
    }
}
