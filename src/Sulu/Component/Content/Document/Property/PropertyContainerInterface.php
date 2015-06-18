<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\Content\Document\Property;

use Sulu\Component\Content\Types\ContentTypeManagerInterface;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Compat\Structure\Structure;

/**
 * Container for content properties.
 */
interface PropertyContainerInterface extends \ArrayAccess
{
    /**
     * Return the named property
     *
     * @param string $name
     */
    public function getProperty($name);

    /**
     * Return true if the container has the named property
     *
     * @param string $name
     */
    public function hasProperty($name);

    /**
     * Bind data to the container
     *
     * If $clearMissing is true then any missing fields will be
     * set to NULL.
     *
     * @param array $data
     * @param boolean $clearMissing
     */
    public function bind($data, $clearMissing = false);

    /**
     * Return an array representation of the containers property values
     *
     * @return array
     */
    public function toArray();

    /**
     * Get staged data, see documentation for commitStagedData
     *
     * @return array
     */
    public function getStagedData();
    
    /**
     * Set staged data, see documentation for commitStagedData
     *
     * @param array $stagedData
     */
    public function setStagedData(array $stagedData);

    /**
     * Commit the staged content data
     *
     * This is necessary because:
     *
     * - We cannot set the content data on a property-by-property basis
     * - Therefore the form framework needs to get/set to a specific property
     * - It uses the stagedData property for this purpose
     * - We then "commit" the staged data after the form has been submitted.
     *
     * We should refactor the content types so that they are not involved
     * in the process of mapping to PHPCR.
     *
     * If $clearMissingContent is true, then fields will be set to NULL
     *
     * @param boolean $clearMissingContent
     */
    public function commitStagedData(bool $clearMissingContent);
}
