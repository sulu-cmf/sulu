<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

class SectionMetadata extends Item
{
    /**
     * The number of grid columns the property should use in the admin interface
     *
     * @var integer
     */
    public $colSpan = null;

    public function getColSpan() 
    {
        return $this->colSpan;
    }
    
}
