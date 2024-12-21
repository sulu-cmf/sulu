<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use JMS\Serializer\Annotation\Exclude;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Entry for a key-value-store like user setting.
 */
class UserSetting
{
    /**
     * The value of the setting.
     *
     * @var string
     */
    private $value;

    /**
     * The key under which this setting is available.
     *
     * @var string
     */
    private $key;

    /**
     * The user for which this setting is applying.
     *
     * @var UserInterface
     */
    #[Exclude]
    private $user;

    /**
     * Sets the value for this user setting.
     *
     * @param string $value
     *
     * @return UserSetting
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Returns the value for this user setting.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the key for this user setting.
     *
     * @param string $key
     *
     * @return UserSetting
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Returns the key for this user setting.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets the user for this user setting.
     *
     * @return UserSetting
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Returns the user for this user setting.
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }
}
