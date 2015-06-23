<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

/**
 * Item for breadcrumb.
 */
interface BreadcrumbItemInterface
{
    /**
     * returns title of node.
     *
     * @return string
     */
    public function getTitle();

    /**
     * returns uuid of node.
     *
     * @return string
     */
    public function getUuid();

    /**
     * returns depth of node.
     *
     * @return int
     */
    public function getDepth();

    /**
     * returns array representation.
     *
     * @return array
     */
    public function toArray();
}
