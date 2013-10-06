<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

/**
 *
 * @package Sulu\Bundle\AdminBundle\Admin
 */
abstract class ContentNavigation
{
    protected $navigation;

    public function __construct()
    {
        $this->navigation = array();
    }

    public function addNavigationItem($navigationItem)
    {
        $this->navigation[] = $navigationItem;
    }

    public function getNavigation()
    {
        return $this->navigation;
    }

    public function toArray($type = null)
    {
        $navigation = array();

        /** @var $navigationItem NavigationItem */
        foreach ($this->navigation as $navigationItem) {
            if (null === $type || $navigationItem->getType() == $type) {
                $navigation[] = $navigationItem->toArray();
            }
        }

        return $navigation;
    }
}
