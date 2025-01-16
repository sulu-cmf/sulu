<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Mapper;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\ResourceLocator\ResourceLocatorInformation;

/**
 * InterfaceDefinition of Resource Locator Path Mapper.
 */
interface ResourceLocatorMapperInterface
{
    /**
     * @return void
     */
    public function save(ResourceSegmentBehavior $document);

    /**
     * Returns path for given contentNode.
     *
     * @param NodeInterface $contentNode reference node
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string path
     *
     * @throws ResourceLocatorNotFoundException
     */
    public function loadByContent(NodeInterface $contentNode, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * Returns path for given contentNode.
     *
     * @param string $uuid uuid of contentNode
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string path
     *
     * @throws ResourceLocatorNotFoundException
     */
    public function loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * Returns history for given contentNode.
     *
     * @param string $uuid uuid of contentNode
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return ResourceLocatorInformation[]
     */
    public function loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * Returns the uuid of referenced content node.
     *
     * @param string $resourceLocator requested RL
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string uuid of content node
     *
     * @throws ResourceLocatorMovedException resourceLocator has been moved
     * @throws ResourceLocatorNotFoundException resourceLocator not found or has no content reference
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * Checks if given path is unique.
     *
     * @param string $path
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return bool
     */
    public function unique($path, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * Returns a unique path with "-1" if necessary.
     *
     * @param string $path
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    public function getUniquePath($path, $webspaceKey, $languageCode, $segmentKey = null/*, $uuid = null*/);

    /**
     * Returns resource locator for parent node.
     *
     * @param string $uuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    public function getParentPath($uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * Deletes given resource locator node.
     *
     * @param string $id The id of the resource locator node
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function deleteById($id, $languageCode, $segmentKey = null);
}
