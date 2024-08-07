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

use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * Defines the methods for the doctrine repository which enables accessing the categories.
 *
 * @extends RepositoryInterface<CategoryInterface>
 */
interface CategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns true if the given id is assigned to an existing category.
     *
     * @param int $id
     *
     * @return bool
     */
    public function isCategoryId($id);

    /**
     * Returns true if the given key is assigned to an existing category.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isCategoryKey($key);

    /**
     * Returns the category which is assigned to the given id.
     * If no respective category is found, null is returned.
     *
     * @param int $id
     *
     * @return CategoryInterface|null
     */
    public function findCategoryById($id);

    /**
     * Returns the category which is assigned to the given key.
     * If no respective category is found, null is returned.
     *
     * @param string $key
     *
     * @return CategoryInterface|null
     */
    public function findCategoryByKey($key);

    /**
     * Returns an array of categories which are assigned to the given array of ids.
     *
     * @return CategoryInterface[]
     */
    public function findCategoriesByIds(array $ids);

    /**
     * Returns the whole category graph. Children are available through children-properties of parents.
     * If parentId is set, only the sub-graph below the category which is assigned to the given id is returned.
     *
     * @param int|null $parentId
     *
     * @return CategoryInterface[]
     */
    public function findChildrenCategoriesByParentId($parentId = null);

    /**
     * Returns the whole category graph. Children are available through children-properties of parents.
     * If parentKey is set, only the sub-graph below the category which is assigned to the given key is returned.
     *
     * @param string|null $parentKey
     *
     * @return CategoryInterface[]
     */
    public function findChildrenCategoriesByParentKey($parentKey = null);

    /**
     * Returns an array of ids of categories which are positioned (exlusive) between a category which is assigned to
     * one of the entries of the fromIds array and a category which is assigned to one of the entries
     * of the toIds array.
     * Start- and end-points of a path are not returned.
     *
     * @param array $fromIds Start-points of the paths which are processed
     * @param array $toIds End-points of the paths which are processed
     *
     * @return array
     */
    public function findCategoryIdsBetween($fromIds, $toIds);

    /**
     * @return array<array{id: int, resourceKey: string, depth: int}>
     */
    public function findDescendantCategoryResources(int $ancestorId): array;
}
