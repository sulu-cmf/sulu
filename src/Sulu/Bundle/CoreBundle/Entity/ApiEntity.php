<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Entity;

use Doctrine\Inflector\InflectorFactory;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;

/**
 * An abstract class for Entities, defining some links.
 *
 * @deprecated
 */
abstract class ApiEntity
{
    /**
     * general base path of entities.
     *
     * @var string
     */
    #[Exclude]
    protected $apiBasePath = '/admin/api';

    /**
     * $apiPath must be overriden by base entity.
     *
     * @var string
     */
    #[Exclude]
    protected $apiPath;

    /**
     * @var int
     */
    #[Exclude]
    private $id;

    /**
     * property to be shown in serialized object.
     *
     * @var string
     */
    #[Accessor(getter: 'getLinks')]
    private $_links = [];

    /**
     * returns the id of an entity.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLinks()
    {
        if (\count($this->_links) < 1) {
            $this->createSelfLink();
        }

        return $this->_links;
    }

    /**
     * creates the _links array including the self path.
     */
    public function createSelfLink()
    {
        // if no apiPath is not set generate it from basepath
        if (\is_null($this->getApiPath())) {
            $class = \explode('\\', \get_class($this));
            $inflector = InflectorFactory::create()->build();
            $plural = $inflector->pluralize(\strtolower(\end($class)));
            $this->apiPath = $this->apiBasePath . '/' . $plural;
        }

        // add id to path
        $idPath = '';
        if ($this->getId()) {
            $idPath = '/' . $this->getId();
        }
        $this->_links = [
            'self' => $this->getApiPath() . $idPath,
        ];
    }

    /**
     * @return string
     */
    public function getApiPath()
    {
        return $this->apiPath;
    }

    /**
     * returns if api path is set.
     *
     * @return bool
     */
    public function hasApiPath()
    {
        return isset($this->apiPath);
    }
}
