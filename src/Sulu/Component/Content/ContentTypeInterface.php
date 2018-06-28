<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * Content type definition.
 */
interface ContentTypeInterface
{
    /**
     * Reads the value for given property from the content repository then sets the value of the Sulu property.
     *
     * @param NodeInterface     $node
     * @param PropertyInterface $property
     * @param string            $webspaceKey
     * @param string            $languageCode
     * @param string            $segmentKey
     *
     * @return mixed
     */
    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    );

    /**
     * Checks availability of a value.
     *
     * @param NodeInterface     $node
     * @param PropertyInterface $property
     * @param $webspaceKey
     * @param $languageCode
     * @param $segmentKey
     *
     * @return mixed
     */
    public function hasValue(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    );

    /**
     * Save the value from given property.
     *
     * @param NodeInterface     $node
     * @param PropertyInterface $property
     * @param int               $userId
     * @param string            $webspaceKey
     * @param string            $languageCode
     * @param string            $segmentKey
     *
     * @return mixed
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    );

    /**
     * Remove the Sulu property from given repository node.
     *
     * @param NodeInterface     $node
     * @param PropertyInterface $property
     * @param string            $webspaceKey
     * @param string            $languageCode
     * @param string            $segmentKey
     */
    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    );

    /**
     * Returns default parameters.
     *
     * @param PropertyInterface|null $property
     *
     * @return array
     */
    public function getDefaultParams(PropertyInterface $property = null);

    /**
     * returns default value of content type.
     *
     * @return mixed
     */
    public function getDefaultValue();

    /**
     * Prepare view data (or metadata) for the template.
     *
     * @param PropertyInterface $property
     *
     * @return array
     */
    public function getViewData(PropertyInterface $property);

    /**
     * Prepare content data for the template.
     *
     * @param PropertyInterface $property
     *
     * @return array
     */
    public function getContentData(PropertyInterface $property);
}
