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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\CategoryFactoryInterface;

class CategoryFactory implements CategoryFactoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $categories = [];
        foreach ($categoryIds as $categoryId) {
            /** @var CategoryInterface $category */
            $category = $this->entityManager->getPartialReference(
                CategoryInterface::class,
                $categoryId
            );

            $categories[] = $category;
        }

        return $categories;
    }
}
