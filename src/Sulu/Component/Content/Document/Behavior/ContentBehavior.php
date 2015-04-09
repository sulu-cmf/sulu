<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

/**
 * The implementing document can have dynamic content assigned to it
 */
interface ContentBehavior
{
    /**
     * Return the type of the structure used for the content
     *
     * @return string
     */
    public function getStructureType();

    /**
     * Set the structure type used for the content
     *
     * @param string
     */
    public function setStructureType($structureType);
}


