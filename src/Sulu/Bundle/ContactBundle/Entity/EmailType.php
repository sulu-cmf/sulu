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
 * EmailType.
 */
class EmailType implements \JsonSerializable
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
    private $emails;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->emails = new ArrayCollection();
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
     * @return EmailType
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
     * Add emails.
     *
     * @return EmailType
     */
    public function addEmail(Email $emails)
    {
        $this->emails[] = $emails;

        return $this;
    }

    /**
     * Remove emails.
     */
    public function removeEmail(Email $emails)
    {
        $this->emails->removeElement($emails);
    }

    /**
     * Get emails.
     *
     * @return Collection
     */
    public function getEmails()
    {
        return $this->emails;
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
