<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

/**
 * Item to display in smart-content UI.
 */
interface ItemInterface
{
    /**
     * Returns resource behind the item.
     *
     * @return mixed
     */
    public function getResource();

    /**
     * Returns id of item.
     *
     * @return string
     *
     * @throws \Sulu\Component\SmartContent\Exception\NoSuchPropertyException
     */
    public function getId();

    /**
     * Returns title of the item.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Returns full qualified title of item.
     * For example path or breadcrumb.
     *
     * @return mixed
     */
    public function getFullQualifiedTitle();

    /**
     * Returns URL to image.
     *
     * @return string
     */
    public function getImage();
}
