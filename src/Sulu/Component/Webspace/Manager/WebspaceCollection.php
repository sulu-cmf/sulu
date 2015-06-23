<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager;

use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\Resource\FileResource;
use Traversable;
use Sulu\Component\Webspace\Exception\UnknownWebspaceException;
use Sulu\Component\Webspace\Exception\UnknownPortalException;

/**
 * A collection of all webspaces and portals in a specific sulu installation.
 */
class WebspaceCollection implements \IteratorAggregate
{
    /**
     * All the webspaces in a specific sulu installation.
     *
     * @var Webspace[]
     */
    private $webspaces;

    /**
     * All the portals in a specific sulu installation.
     *
     * @var Portal[]
     */
    private $portals;

    /**
     * The portals of this specific sulu installation, prefiltered by the environment and url.
     *
     * @var array
     */
    private $portalInformations;

    /**
     * Contains all the resources, which where used to build this collection.
     * Is required by the Symfony CacheConfig-Component.
     *
     * @var FileResource[]
     */
    private $resources;

    /**
     * Adds a new FileResource, which is required to determine if the cache is fresh.
     *
     * @param FileResource $resource
     */
    public function addResource(FileResource $resource)
    {
        $this->resources[] = $resource;
    }

    /**
     * Returns the resources used to build this collection.
     *
     * @return array The resources build to use this collection
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Returns the portal with the given index.
     *
     * @param $key string The index of the portal
     *
     * @return Portal
     * @throws UnknownPortalException
     */
    public function getPortal($key)
    {
        if (!isset($this->portals[$key])) {
            throw new UnknownPortalException($key);
        }

        return $this->portals[$key];
    }

    /**
     * Return true if the collection has the named portal
     *
     * @param string $name
     * @return boolean
     */
    public function hasPortal($key)
    {
        return isset($this->portals[$key]);
    }

    /**
     * Returns the portal informations for the given environment.
     *
     * @param $environment string The environment to deliver
     *
     * @throws \InvalidArgumentException
     *
     * @return PortalInformation[]
     */
    public function getPortalInformations($environment)
    {
        if (!isset($this->portalInformations[$environment])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown portal environment "%s"', $environment
            ));
        }

        return $this->portalInformations[$environment];
    }

    /**
     * Returns the webspace with the given key.
     *
     * @param $key string The key of the webspace
     *
     * @return Webspace
     *
     * @throws UnknownWebspaceException
     */
    public function getWebspace($key)
    {
        if (!isset($this->webspaces[$key])) {
            throw new UnknownWebspaceException($key);
        }

        return $this->webspaces[$key];
    }

    /**
     * Return true if the collection has the webspace with the given key
     *
     * @param string $name
     * @return boolean
     */
    public function hasWebspace($key)
    {
        return isset($this->webspaces[$key]);
    }

    /**
     * Returns the length of the collection.
     *
     * @return int
     */
    public function length()
    {
        return count($this->webspaces);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator.
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->webspaces);
    }

    /**
     * Returns the content of these portals as array.
     *
     * @return array
     */
    public function toArray()
    {
        $collection = array();

        $webspaces = array();
        foreach ($this->webspaces as $webspace) {
            $webspaces[] = $webspace->toArray();
        }

        $portalInformations = array();
        foreach ($this->portalInformations as $environment => $environmentPortalInformations) {
            $portalInformations[$environment] = array();

            foreach ($environmentPortalInformations as $environmentPortalInformation) {
                $portalInformations[$environment][$environmentPortalInformation->getUrl()] = $environmentPortalInformation->toArray();
            }
        }

        $collection['webspaces'] = $webspaces;
        $collection['portalInformations'] = $portalInformations;

        return $collection;
    }

    /**
     * @param \Sulu\Component\Webspace\Webspace[] $webspaces
     */
    public function setWebspaces($webspaces)
    {
        $this->webspaces = $webspaces;
    }

    /**
     * @return \Sulu\Component\Webspace\Webspace[]
     */
    public function getWebspaces()
    {
        return $this->webspaces;
    }

    /**
     * Returns all the portals of this collection.
     *
     * @return Portal[]
     */
    public function getPortals()
    {
        return $this->portals;
    }

    /**
     * Sets the portals for this collection.
     *
     * @param Portal[] $portals
     */
    public function setPortals($portals)
    {
        $this->portals = $portals;
    }

    /**
     * Sets the portal Information for this collection.
     *
     * @param array $portalInformations
     */
    public function setPortalInformations($portalInformations)
    {
        $this->portalInformations = $portalInformations;
    }
}
