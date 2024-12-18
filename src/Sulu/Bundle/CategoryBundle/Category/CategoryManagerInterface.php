<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotUniqueException;
use Sulu\Component\Rest\Exception\MissingArgumentException;

/**
 * Defines the operations of the CategoryManager.
 * The CategoryManager is responsible for the centralized management of our categories.
 */
interface CategoryManagerInterface
{
    /**
     * Returns the category which is assigned to the given id.
     *
     * @param int $id
     *
     * @return CategoryInterface
     *
     * @throws CategoryIdNotFoundException if the given id is not assigned to an existing category
     */
    public function findById($id);

    /**
     * Returns the category which is assigned to the given key.
     *
     * @param string $key
     *
     * @return CategoryInterface
     *
     * @throws CategoryKeyNotFoundException if the given key is not assigned to an existing category
     */
    public function findByKey($key);

    /**
     * Returns an array of categories which are assigned to the given array of ids.
     * If an id of the array is not assigned to a category, no error is thrown.
     *
     * @return CategoryInterface[]
     */
    public function findByIds(array $ids);

    /**
     * Returns the whole category graph.
     * If parentId is set, only the sub-graph below the category which is assigned to the given id is returned.
     *
     * @param int|null $parentId
     *
     * @return array
     *
     * @throws CategoryIdNotFoundException if the parentId is not assigned to an existing category
     */
    public function findChildrenByParentId($parentId = null);

    /**
     * Returns the whole category graph.
     * If parentKey is set, only the sub-graph below the category which is assigned to the given key is returned.
     *
     * @param string|null $parentKey
     *
     * @return array
     *
     * @throws CategoryKeyNotFoundException if the parentKey is not assigned to an existing category
     */
    public function findChildrenByParentKey($parentKey = null);

    /**
     * Creates or updates the given data as category in the given locale and return the saved category.
     * If data.id is set, the category which is assigned to the given id is overwritten.
     * If patch is set, the category which is assigned to the given id is updated partially.
     *
     * @param array $data
     * @param $userId int Id of the user which is set as creator/changer. If null, the user of the request is set
     * @param string $locale
     * @param bool $patch
     *
     * @return CategoryInterface
     *
     * @throws CategoryIdNotFoundException if data.id is set, but the id is not assigned to a existing category
     * @throws CategoryKeyNotUniqueException
     * @throws MissingArgumentException
     */
    public function save($data, $userId, $locale, $patch = false);

    /**
     * Deletes the category which is assigned to the given id.
     *
     * @param int $id
     *
     * @throws CategoryIdNotFoundException if the given id is not assigned to an existing category
     */
    public function delete($id/*, bool $forceRemoveChildren = false*/);

    /**
     * Returns an API-Object for a given category-entity. The API-Object wraps the entity
     * and provides neat getters and setters.
     *
     * @param CategoryInterface $category
     * @param string $locale
     *
     * @return Category
     */
    public function getApiObject($category, $locale);

    /**
     * Same as getApiObject, but takes multiple category-entities.
     *
     * @param CategoryInterface[] $categories
     * @param string $locale
     *
     * @return Category[]
     */
    public function getApiObjects($categories, $locale);

    /**
     * Move category to new parent.
     *
     * @param int $id
     * @param int|null $parent
     *
     * @return CategoryInterface
     */
    public function move($id, $parent);
}
