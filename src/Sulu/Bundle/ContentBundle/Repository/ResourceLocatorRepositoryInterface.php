<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Repository;

/**
 * resource locator repository.
 */
interface ResourceLocatorRepositoryInterface
{
    /**
     * generates a resource locator with given title.
     *
     * @param string[] $parts parts of title
     * @param null|string $parentUuid uuid of parent node (can be null)
     * @param null|string $uuid uuid of node (can be null)
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $templateKey
     * @param null|string $segmentKey
     *
     * @return string
     */
    public function generate($parts, $parentUuid, $uuid, $webspaceKey, $languageCode, $templateKey, $segmentKey = null);

    /**
     * @param string $uuid
     * @param string $webspaceKey
     * @param string $languageCode
     *
     * @return array
     */
    public function getHistory($uuid, $webspaceKey, $languageCode);

    /**
     * deletes given resource locator.
     *
     * @param string $path
     * @param string $webspaceKey
     * @param string $languageCode
     * @param null|string $segmentKey
     *
     * @return mixed
     */
    public function delete($path, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * restores given resource locator.
     *
     * @param string $path
     * @param int $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @param null|string $segmentKey
     *
     * @return mixed
     */
    public function restore($path, $userId,  $webspaceKey, $languageCode, $segmentKey = null);
}
