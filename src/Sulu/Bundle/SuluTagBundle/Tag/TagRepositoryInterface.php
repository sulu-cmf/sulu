<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag;

use Sulu\Bundle\TagBundle\Entity\Tag;

/**
 * Defines the method for the doctrine repository
 * @package Sulu\Bundle\TagBundle\Tag
 */
interface TagRepositoryInterface
{
    /**
     * Finds the tag with the given ID
     * @param $id
     * @return Tag
     */
    public function findTagById($id);

    /**
     * Searches for all roles
     * @return array
     */
    public function findAllTags();
} 
