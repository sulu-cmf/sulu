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

namespace Sulu\Bundle\ContentBundle\Tests\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;

trait CreateCategoryTrait
{
    /**
     * @param array<string, array{title?: string}> $dataSet
     */
    protected static function createCategory(array $dataSet = []): CategoryInterface
    {
        $entityManager = static::getEntityManager();

        $category = new Category();

        foreach ($dataSet as $locale => $data) {
            $category->setDefaultLocale($locale);
            $translation = new CategoryTranslation();
            $translation->setCategory($category);
            $translation->setLocale($locale);
            $translation->setTranslation($data['title'] ?? '');
            $category->addTranslation($translation);
            $entityManager->persist($translation);
        }

        $entityManager->persist($category);

        return $category;
    }

    abstract protected static function getEntityManager(): EntityManagerInterface;
}
