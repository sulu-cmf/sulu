<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Defines the method for the doctrine repository
 * @package Sulu\Bundle\MediaBundle\Entity
 */
interface MediaRepositoryInterface
{

    /**
     * Finds the media with a given id
     * @param $id
     * @return Media
     */
    public function findMediaById($id);

    /**
     * finds all media, can be filtered with parent
     * @param array $filter
     * @param int $limit
     * @param int $offset
     * @return Paginator
     */
    public function findMedia($filter = array(), $limit = null, $offset = null);

    /**
     * @param string $filename
     * @param int $collectionId
     * @return Media
     */
    public function findMediaWithFilenameInCollectionWithId($filename, $collectionId);

    /**
     * @param $collectionId
     * @param $limit
     * @param $offset
     * @return mixed
     */
    public function findMediaByCollectionId($collectionId, $limit, $offset);
}
