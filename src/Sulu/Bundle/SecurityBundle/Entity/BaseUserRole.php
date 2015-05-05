<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\SecurityBundle\Entity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * UserRole
 * @ExclusionPolicy("all");
 */
abstract class BaseUserRole extends ApiEntity
{
    /**
     * @var integer
     * @Expose
     */
    protected $id;

    /**
     * @var string
     * @Expose
     */
    protected $locale;

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var RoleInterface
     * @Expose
     */
    protected $role;

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
     * Set locale
     *
     * @param string $locale
     * @return UserRole
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Get Locales as array
     * @return array
     * @VirtualProperty
     * @SerializedName("locales")
     */
    public function getLocales()
    {
        return json_decode($this->locale);
    }

    /**
     * Set user
     *
     * @param UserInterface $user
     * @return UserRole
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set role
     *
     * @param RoleInterface $role
     * @return UserRole
     */
    public function setRole(RoleInterface $role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return RoleInterface
     */
    public function getRole()
    {
        return $this->role;
    }
}
