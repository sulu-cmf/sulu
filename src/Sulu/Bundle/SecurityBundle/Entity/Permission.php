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

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Sulu\Component\Security\Authentication\RoleInterface;

/**
 * Permission.
 */
#[ExclusionPolicy('all')] // ;
class Permission
{
    /**
     * @var string
     */
    #[Expose]
    private $context;

    /**
     * @var int
     */
    #[Expose]
    private $permissions;

    /**
     * @var int
     */
    #[Expose]
    private $id;

    /**
     * @var RoleInterface
     */
    private $role;

    /**
     * @var string|null
     */
    #[Expose]
    private $module;

    /**
     * Set context.
     *
     * @param string $context
     *
     * @return Permission
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get context.
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set permissions.
     *
     * @param int $permissions
     *
     * @return Permission
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * Get permissions.
     *
     * @return int
     */
    public function getPermissions()
    {
        return $this->permissions;
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
     * Set role.
     *
     * @return Permission
     */
    public function setRole(?RoleInterface $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role.
     *
     * @return RoleInterface
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set module.
     *
     * @param string $module
     *
     * @return Permission
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get module.
     *
     * @return string|null
     */
    public function getModule()
    {
        return $this->module;
    }

    public function __toString()
    {
        return (string) $this->context;
    }
}
