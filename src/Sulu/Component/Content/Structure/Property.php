<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure;

class Property extends Item
{
    /**
     * Type of this property (e.g. "text_line", "smart_content")
     *
     * @var string
     */
    public $type;

    /**
     * Placeholder for property
     */
    public $placeholder;

    /**
     * If the property should be available in different localizations
     *
     * @var boolean
     */
    public $localized = false;

    /**
     * If the property is required
     *
     * @var boolean
     */
    public $required = false;

    /**
     * The number of grid columns the property should use in the admin interface
     *
     * @var integer
     */
    public $colSpan = null;

    /**
     * The CSS class the property should use in the admin interface
     *
     * @var string
     */
    public $cssClass = null;

    /**
     * Tags, e.g. [['name' => 'sulu_search.field', 'type' => 'string']]
     *
     * @var array
     */
    public $tags = array();

    /**
     * @var integer
     */
    public $minOccurs = 1;

    /**
     * @var mixed
     */
    public $maxOccurs = 1;

    /**
     * @var Structure
     */
    public $structure;

    public function getMinOccurs()
    {
        return $this->minOccurs;
    }

    public function getStructure() 
    {
        throw new \InvalidArgumentException(
            'Not implemented'
        );
    }

    public function getMaxOccurs()
    {
        return $this->maxOccurs;
    }

    /**
     * @deprecated - use getType
     */
    public function getContentTypeName() 
    {
        return $this->type;
    }

    /**
     * @deprecated
     */
    public function getIsBlock() 
    {
        return false;
    }

    public function getColSpan() 
    {
        return $this->colSpan;
    }

    public function getPlaceholder($locale) 
    {
        return $this->placeholder[$locale];
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function isMultiple()
    {
        return $this->minOccurs !== $this->maxOccurs;
    }

    public function isLocalized()
    {
        return $this->localized;
    }

    public function getType() 
    {
        return $this->type;
    }
}
