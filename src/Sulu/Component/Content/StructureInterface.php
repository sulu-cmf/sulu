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

use DateTime;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Structure for template
 */
interface StructureInterface extends \JsonSerializable
{
    /**
     * id of node
     * @return int
     */
    public function getUuid();

    /**
     * sets id of node
     * @param $uuid
     */
    public function setUuid($uuid);

    /**
     * returns id of creator
     * @return int
     */
    public function getCreator();

    /**
     * sets user id of creator
     * @param $userId int id of creator
     */
    public function setCreator($userId);

    /**
     * returns user id of changer
     * @return int
     */
    public function getChanger();

    /**
     * sets user id of changer
     * @param $userId int id of changer
     */
    public function setChanger($userId);

    /**
     * return created datetime
     * @return DateTime
     */
    public function getCreated();

    /**
     * sets created datetime
     * @param DateTime $created
     */
    public function setCreated(DateTime $created);

    /**
     * returns changed DateTime
     * @return DateTime
     */
    public function getChanged();

    /**
     * sets changed datetime
     * @param DateTime $changed
     */
    public function setChanged(DateTime $changed);

    /**
     * key of template definition
     * @return string
     */
    public function getKey();

    /**
     * twig template of template definition
     * @return string
     */
    public function getView();

    /**
     * controller which renders the template definition
     * @return string
     */
    public function getController();

    /**
     * cacheLifeTime of template definition
     * @return int
     */
    public function getCacheLifeTime();

    /**
     * returns a property instance with given name
     * @param $name string name of property
     * @return PropertyInterface
     * @throws NoSuchPropertyException
     */
    public function getProperty($name);

    /**
     * checks if a property exists
     * @param string $name
     * @return boolean
     */
    public function hasProperty($name);

    /**
     * returns an array of properties
     * @return array
     */
    public function getProperties();

    /**
     * @param boolean $hasChildren
     */
    public function setHasChildren($hasChildren);

    /**
     * @return boolean
     */
    public function getHasChildren();

    /**
     * @param StructureInterface[] $children
     */
    public function setChildren($children);

    /**
     * @return StructureInterface[]
     */
    public function getChildren();

    /**
     * returns an array of property value pairs
     * @return array
     */
    public function toArray();
}
