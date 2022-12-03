<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait CreateCategoryTrait
{
    /**
     * @param array{
     *     key?: ?string,
     *     default_locale?: ?string,
     * } $data
     */
    public function createCategory(array $data = []): CategoryInterface
    {
        $categoryRepository = static::getContainer()->get('sulu.repository.category');
        /** @var CategoryInterface $category */
        $category = $categoryRepository->createNew();
        $category->setKey($data['key'] ?? null);
        $category->setDefaultLocale($data['default_locale'] ?? 'en');

        static::getEntityManager()->persist($category);

        return $category;
    }

    /**
     * @param array{
     *     title?: ?string,
     *     locale?: ?string,
     * } $data
     */
    public function createCategoryTranslation(CategoryInterface $category, array $data = []): CategoryTranslationInterface
    {
        $categoryTranslation = new CategoryTranslation();
        $categoryTranslation->setLocale($data['locale'] ?? 'en');
        $categoryTranslation->setTranslation($data['title'] ?? '');
        $category->addTranslation($categoryTranslation);

        return $categoryTranslation;
    }

    abstract protected static function getEntityManager(): EntityManagerInterface;

    abstract protected static function getContainer(): ContainerInterface;
}
