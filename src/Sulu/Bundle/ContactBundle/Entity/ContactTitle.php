<?php

namespace Sulu\Bundle\ContactBundle\Entity;

use JsonSerializable;
use JMS\Serializer\Annotation\Groups;

/**
 * ContactTitle
 */
class ContactTitle implements JsonSerializable
{
    /**
     * @var string
     * @Groups({"fullContact", "partialContact"})
     */
    private $title;

    /**
     * @var integer
     * @Groups({"fullContact", "partialContact"})
     */
    private $id;

    /**
     * Set title
     *
     * @param string $title
     * @return ContactTitle
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return [
            'id'=>$this->getId(),
            'title'=>$this->getTitle()
        ];
    }

    /**
     * Return the string representation of this title
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getTitle();
    }
}
