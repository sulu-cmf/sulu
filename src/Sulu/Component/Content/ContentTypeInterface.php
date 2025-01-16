<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string|null $segmentKey
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
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string|null $segmentKey
     *
     * @return bool
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
     * @param int $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string|null $segmentKey
     *
     * @return void
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
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string|null $segmentKey
     *
     * @return void
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
     * @return array
     */
    public function getDefaultParams(?PropertyInterface $property = null);

    /**
     * returns default value of content type.
     */
    public function getDefaultValue();

    /**
     * Prepare view data (or metadata) for the template.
     *
     * @return mixed[]
     */
    public function getViewData(PropertyInterface $property);

    /**
     * Prepare content data for the template.
     *
     * @return mixed
     */
    public function getContentData(PropertyInterface $property);
}
