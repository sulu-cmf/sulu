<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Strategy;

use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Types\ResourceLocator\ResourceLocatorInformation;

/**
 * InterfaceDefinition of Resource Locator Path Strategy.
 */
interface ResourceLocatorStrategyInterface
{
    public const INPUT_TYPE_LEAF = 'leaf';

    public const INPUT_TYPE_FULL = 'full';

    /**
     * Returns the child part from the given resource segment.
     *
     * @param string $resourceSegment
     *
     * @return string
     */
    public function getChildPart($resourceSegment);

    /**
     * Returns whole path for given title and parent-uuid.
     *
     * @param string $title title of new node
     * @param string $parentUuid uuid of the parent of the new node
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string whole path
     */
    public function generate($title, $parentUuid, $webspaceKey, $languageCode, $segmentKey = null/*, $uuid = null*/);

    /**
     * Creates a new route for given path.
     *
     * @param int $userId
     *
     * @return null|bool
     */
    public function save(ResourceSegmentBehavior $document, $userId);

    /**
     * Returns path for given contentNode.
     *
     * @param ResourceSegmentBehavior $document reference node
     *
     * @return string path
     */
    public function loadByContent(ResourceSegmentBehavior $document);

    /**
     * Returns path for given contentNode.
     *
     * @param string $uuid uuid of contentNode
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string path
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
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * Checks if path is valid.
     *
     * @param string $path path of route
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return bool
     */
    public function isValid($path, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * Deletes given resource locator node.
     *
     * @param string $id of resource locator node
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return void
     */
    public function deleteById($id, $languageCode, $segmentKey = null);

    /**
     * Returns input-type for javscript-component.
     *
     * @return string
     */
    public function getInputType();
}
