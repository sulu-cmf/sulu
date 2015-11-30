<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface MediaManagerInterface
{
    /**
     * Returns media with a given collection and/or ids and/or limit
     * if no arguments passed returns all media.
     *
     * @param string $locale the locale which the object will be returned
     * @param array $filter collection, ids, types
     * @param int $limit to limit the output
     * @param int $offset to offset the output
     *
     * @return Media[]
     */
    public function get($locale, $filter = [], $limit = null, $offset = null);

    /**
     * Return the count of the last get.
     *
     * @return int
     */
    public function getCount();

    /**
     * Returns a media with a given id.
     *
     * @param int $id the id of the category
     * @param string $locale the locale which the object will be returned
     *
     * @return Media
     */
    public function getById($id, $locale);

    /**
     * Returns a media entity with a given id.
     *
     * @param int $id
     *
     * @return MediaEntity
     */
    public function getEntityById($id);

    /**
     * Returns the medias with the given ids in the specified order.
     *
     * @param array $ids
     * @param string $locale
     *
     * @return Media[]
     */
    public function getByIds(array $ids, $locale);

    /**
     * Creates a new media or overrides an existing one.
     *
     * @param UploadedFile $uploadedFile
     * @param array $data The data of the category to save
     * @param int $userId The id of the user, who is doing this change
     *
     * @return Media
     */
    public function save($uploadedFile, $data, $userId);

    /**
     * Persists entity in database.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $media The media entity that should be persisted
     */
    public function saveEntity(\Sulu\Bundle\MediaBundle\Entity\Media $media);

    /**
     * Deletes a media with a given id.
     *
     * @param int $id the id of the category to delete
     */
    public function delete($id, $checkSecurity = false);

    /**
     * Moves a media to a given collection.
     *
     * @param int $id id of media
     * @param string $locale the locale which the object will be returned
     * @param int $destCollection id of destination collection
     *
     * @return Media
     *
     * @throws MediaNotFoundException
     * @throws CollectionNotFoundException
     */
    public function move($id, $locale, $destCollection);

    /**
     * Return the FieldDescriptor by name.
     *
     * @param string $key
     *
     * @return DoctrineFieldDescriptor
     */
    public function getFieldDescriptor($key);

    /**
     * Return the FieldDescriptors.
     *
     * @return array
     */
    public function getFieldDescriptors();

    /**
     * Increase the download counter of a fileVersion.
     *
     * @param int $fileVersionId
     *
     * @return mixed
     */
    public function increaseDownloadCounter($fileVersionId);

    /**
     * Takes an array of media ids and returns an array of formats and urls.
     *
     * @param array $ids
     *
     * @return array
     */
    public function getFormatUrls($ids, $locale);

    /**
     * Adds thumbnails and image urls.
     *
     * @param Media $media
     *
     * @return Media
     */
    public function addFormatsAndUrl(Media $media);
}
