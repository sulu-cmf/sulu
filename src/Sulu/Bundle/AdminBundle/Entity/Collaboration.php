<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Entity;

use JMS\Serializer\Annotation\Exclude;

/**
 * Represents a user, which is currently editing an entity.
 */
class Collaboration
{
    /**
     * @var int
     * @Exclude
     */
    private $connectionId;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $fullName;

    /**
     * @var string
     */
    private $resourceKey;

    /**
     * @var mixed
     */
    private $id;

    /**
     * @var int
     */
    private $changed;

    public function __construct($connectionId, $userId, $username, $fullName, $resourceKey, $id)
    {
        $this->connectionId = $connectionId;
        $this->userId = $userId;
        $this->username = $username;
        $this->fullName = $fullName;
        $this->resourceKey = $resourceKey;
        $this->id = $id;
        $this->changed = time();
    }

    public function getConnectionId()
    {
        return $this->connectionId;
    }

    public function getResourceKey()
    {
        return $this->resourceKey;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getChanged()
    {
        return $this->changed;
    }
}
