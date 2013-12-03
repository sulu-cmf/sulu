<?php
namespace Sulu\Bundle\CoreBundle\Entity;

use Doctrine\Common\Inflector\Inflector;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;

abstract class ApiEntity
{
    /**
     * general base path of entities
     * @var string
     * @Exclude
     */
    protected $apiBasePath = '/admin/api';

    /**
     * $apiPath must be overriden by base entity
     * @var string
     * @Exclude
     */
    protected $apiPath;

    /**
     * @var int
     * @Exclude
     */
    private $id;

    /**
     * property to be shown in serialized object
     * @Accessor(getter="getLinks")
     * @var string
     */
    private $_links;

    /**
     * returns the id of an entity
     * @return mixed
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
        if (count($this->_links) < 1) {
            $this->createSelfLink();
        }

        return $this->_links;
    }

    /**
     * creates the _links array including the self path
     */
    public function createSelfLink()
    {
        // if no apiPath is not set generate it from basepath
        if (is_null($this->getApiPath())) {
            $class = explode('\\', get_class($this));
            $plural = Inflector::pluralize(strtolower(end($class)));
            $this->apiPath = $this->apiBasePath . '/' . $plural;
        }

        // add id to path
        $idPath = '';
        if ($this->getId()) {
            $idPath = '/' . $this->getId();
        }
        $this->_links = array(
            'self' => $this->getApiPath() . $idPath,
        );
    }

    /**
     * @return string
     */
    public function getApiPath()
    {
        return $this->apiPath;
    }

    /**
     * returns if api path is set
     * @return bool
     */
    public function hasApiPath()
    {
        return isset($this->apiPath);
    }
}
