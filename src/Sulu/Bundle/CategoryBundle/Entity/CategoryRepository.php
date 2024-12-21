<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @extends NestedTreeRepository<CategoryInterface>
 */
class CategoryRepository extends NestedTreeRepository implements CategoryRepositoryInterface
{
    public function createNew()
    {
        /** @var class-string<CategoryInterface> */
        $className = $this->getClassName();

        return new $className();
    }

    public function isCategoryId($id)
    {
        $queryBuilder = $this->createQueryBuilder('category')
            ->addSelect('1')
            ->where('category.id = :categoryId')
            ->setMaxResults(1);

        $query = $queryBuilder->getQuery();
        $query->setParameter('categoryId', $id);

        return !empty($query->getResult());
    }

    public function isCategoryKey($key)
    {
        $queryBuilder = $this->createQueryBuilder('category')
            ->addSelect('1')
            ->where('category.key = :categoryKey')
            ->setMaxResults(1);

        $query = $queryBuilder->getQuery();
        $query->setParameter('categoryKey', $key);

        return !empty($query->getResult());
    }

    public function findCategoryById($id)
    {
        $queryBuilder = $this->getCategoryQuery()->where('category.id = :categoryId');
        $query = $queryBuilder->getQuery();
        $query->setParameter('categoryId', $id);

        return $query->getOneOrNullResult();
    }

    public function findCategoryByKey($key)
    {
        $queryBuilder = $this->getCategoryQuery()->where('category.key = :categoryKey');
        $query = $queryBuilder->getQuery();
        $query->setParameter('categoryKey', $key);

        return $query->getOneOrNullResult();
    }

    public function findCategoriesByIds(array $ids)
    {
        $queryBuilder = $this->getCategoryQuery();

        $queryBuilder->where($queryBuilder->expr()->in('category.id', ':ids'));
        $queryBuilder->setParameter('ids', $ids);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findChildrenCategoriesByParentId($parentId = null)
    {
        $queryBuilder = $this->getCategoryQuery();

        if (null === $parentId) {
            $queryBuilder->andWhere('category.parent IS NULL');
        } else {
            $queryBuilder->andWhere('categoryParent.id = :parentId');
        }

        $query = $queryBuilder->getQuery();

        if (null !== $parentId) {
            $query->setParameter('parentId', $parentId);
        }

        return $query->getResult();
    }

    public function findChildrenCategoriesByParentKey($parentKey = null)
    {
        $queryBuilder = $this->getCategoryQuery();

        if (null === $parentKey) {
            $queryBuilder->andWhere('category.parent IS NULL');
        } else {
            $queryBuilder->andWhere('categoryParent.key = :parentKey');
        }

        $query = $queryBuilder->getQuery();

        if (null !== $parentKey) {
            $query->setParameter('parentKey', $parentKey);
        }

        return $query->getResult();
    }

    public function findCategoryIdsBetween($fromIds, $toIds)
    {
        $fromIds = \array_filter($fromIds);
        $toIds = \array_filter($toIds);

        $queryBuilder = $this->createQueryBuilder('category');
        if ($fromIds) {
            $queryBuilder->from($this->getEntityName(), 'fromCategory');
        }
        if ($toIds) {
            $queryBuilder->from($this->getEntityName(), 'toCategory');
        }

        $queryBuilder->select('category.id');

        if ($fromIds) {
            $queryBuilder->andWhere('fromCategory.id IN (:fromIds)');
            $queryBuilder->andWhere('category.lft > fromCategory.lft');
            $queryBuilder->andWhere('category.rgt < fromCategory.rgt');
        }
        if ($toIds) {
            $queryBuilder->andWhere('toCategory.id IN (:toIds)');
            $queryBuilder->andWhere('category.lft < toCategory.rgt');
            $queryBuilder->andWhere('category.rgt > toCategory.rgt');
        }

        $query = $queryBuilder->getQuery();
        if ($fromIds) {
            $query->setParameter('fromIds', $fromIds);
        }
        if ($toIds) {
            $query->setParameter('toIds', $toIds);
        }

        return \array_map('current', $query->getScalarResult());
    }

    /**
     * Returns the general part of the query.
     *
     * @return QueryBuilder
     */
    private function getCategoryQuery()
    {
        return $this->createQueryBuilder('category')
            ->leftJoin('category.meta', 'categoryMeta')
            ->leftJoin('category.translations', 'categoryTranslations')
            ->leftJoin('categoryTranslations.keywords', 'categoryKeywords')
            ->leftJoin('category.parent', 'categoryParent')
            ->leftJoin('category.children', 'categoryChildren')
            ->addSelect('categoryMeta')
            ->addSelect('categoryTranslations')
            ->addSelect('categoryKeywords')
            ->addSelect('categoryParent')
            ->addSelect('categoryChildren');
    }

    public function findCategoryByIds(array $ids)
    {
        @trigger_deprecation('sulu/sulu', '1.4', __METHOD__ . '() is deprecated and will be removed in 2.0. Use findCategoriesByIds() instead.');

        return $this->findCategoriesByIds($ids);
    }

    public function findCategories($parent = null, $depth = null, $sortBy = null, $sortOrder = null)
    {
        @trigger_deprecation('sulu/sulu', '1.4', __METHOD__ . '() is deprecated and will be removed in 2.0. Use findChildrenCategoriesByParentId() instead.');

        $queryBuilder = $this->getCategoryQuery();
        $queryBuilder->andWhere('category.parent IS NULL');

        if (null !== $parent) {
            $queryBuilder->andWhere('categoryParent.id = :parentId');
        }
        if (null !== $depth) {
            $queryBuilder->andWhere('category.depth = :depth');
        }

        if ($sortBy) {
            $sortOrder = ($sortOrder) ? $sortOrder : 'asc';
            $queryBuilder->addOrderBy('category.' . $sortBy, $sortOrder);
        }

        $query = $queryBuilder->getQuery();
        if (null !== $parent) {
            $query->setParameter('parentId', $parent);
        }
        if (null !== $depth) {
            $query->setParameter('depth', $depth);
        }

        return $query->getResult();
    }

    public function findChildren($key, $sortBy = null, $sortOrder = null)
    {
        @trigger_deprecation('sulu/sulu', '1.4', __METHOD__ . '() is deprecated and will be removed in 2.0. Use findChildrenCategoriesByParentKey() instead.');

        $queryBuilder = $this->getCategoryQuery()
            ->from(\Sulu\Bundle\CategoryBundle\Entity\Category::class, 'parent')
            ->andWhere('parent.key = :key')
            ->andWhere('category.parent = parent');

        if ($sortBy) {
            $sortOrder = ($sortOrder) ? $sortOrder : 'asc';
            $queryBuilder->addOrderBy('category.' . $sortBy, $sortOrder);
        }

        $query = $queryBuilder->getQuery();
        $query->setParameter('key', $key);

        /** @var CategoryInterface[] */
        return $query->getResult();
    }

    /**
     * @return array<array{id: int, resourceKey: string, depth: int}>
     */
    public function findDescendantCategoryResources(int $ancestorId): array
    {
        return $this->createQueryBuilder('category')
            ->select('category.id AS id')
            ->addSelect('\'' . CategoryInterface::RESOURCE_KEY . '\' AS resourceKey')
            ->addSelect('category.depth AS depth')
            ->distinct()
            ->innerJoin(
                CategoryInterface::class,
                'ancestorCategory',
                Join::WITH,
                'category.lft > ancestorCategory.lft AND category.rgt < ancestorCategory.rgt'
            )
            ->where('ancestorCategory.id = :ancestorId')
            ->orderBy('category.id', 'ASC')
            ->setParameter('ancestorId', $ancestorId)
            ->getQuery()
            ->getArrayResult();
    }
}
