<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;

/**
 * UrlType.
 */
class UrlType implements \JsonSerializable
{
    /**
     * @var string
     */
    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    private $name;

    /**
     * @var int
     */
    #[Groups(['fullAccount', 'fullContact', 'frontend'])]
    private $id;

    /**
     * @var Collection
     */
    #[Exclude]
    private $urls;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->urls = new ArrayCollection();
    }

    /**
     * To force id = 1 in load fixtures.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return UrlType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add urls.
     *
     * @return UrlType
     */
    public function addUrl(Url $urls)
    {
        $this->urls[] = $urls;

        return $this;
    }

    /**
     * Remove urls.
     */
    public function removeUrl(Url $urls)
    {
        $this->urls->removeElement($urls);
    }

    /**
     * Get urls.
     *
     * @return Collection
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON.
     *
     * @see http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
        ];
    }
}
