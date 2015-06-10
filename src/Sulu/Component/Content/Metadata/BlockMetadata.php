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

/**
 * Blocks represents a choice of sub structures
 *
 * TODO: Components are basically Snippets, but Snippets are loaded as Structures
 */
class BlockMetadata extends PropertyMetadata
{
    public $components = array();
    public $defaultComponentName;

    /**
     * Return the default component name
     *
     * @return string
     */
    public function getDefaultComponentName()
    {
        return $this->defaultComponentName;
    }

    /**
     * Return the components
     *
     * @return ComponentMetadata[]
     */
    public function getComponents() 
    {
        return $this->components;
    }

    /**
     * Add a new component
     *
     * @param ComponentMetadata $component
     */
    public function addComponent(ComponentMetadata $component)
    {
        $this->components[] = $component;
    }
}
