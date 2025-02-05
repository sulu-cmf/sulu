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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleSettingInterface;

/**
 * Role.
 */
class Role implements RoleInterface
{
    use AuditableTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $key;

    /**
     * @var string
     */
    private $system;

    /**
     * @var Collection<int, Permission>
     */
    #[Groups(['fullRole'])]
    private $permissions;

    /**
     * @var Collection<int, UserRole>
     */
    #[Exclude]
    private $userRoles;

    /**
     * @deprecated The group functionality was deprecated in Sulu 2.1 and will be removed in Sulu 3.0
     *
     * @var Collection<int, Group>
     */
    #[Exclude]
    private $groups;

    /**
     * @var Collection<string, RoleSettingInterface>
     */
    private $settings;

    /**
     * @var bool
     */
    private $anonymous = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->userRoles = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->settings = new ArrayCollection();
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
     * @deprecated since 2.1 and will be removed in 3.0. Use "getIdentifier" instead.
     *
     * @return string
     */
    public function getRole()
    {
        @trigger_deprecation('sulu/sulu', '2.1', 'The "%s" method is deprecated, use "%s" instead.', __METHOD__, 'getIdentifier');

        return $this->getIdentifier();
    }

    public function getIdentifier()
    {
        if ($this->anonymous) {
            return RoleInterface::IS_SULU_ANONYMOUS;
        }

        $key = $this->getKey();

        // keep backwards compatibility as name was used for generating identifier before key was introduced
        if (!$key) {
            $key = $this->getName();
        }

        return 'ROLE_SULU_' . \strtoupper($key);
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    public function setSystem($system)
    {
        $this->system = $system;

        return $this;
    }

    public function getSystem()
    {
        return $this->system;
    }

    public function addPermission(Permission $permissions)
    {
        $this->permissions[] = $permissions;

        return $this;
    }

    public function removePermission(Permission $permissions)
    {
        $this->permissions->removeElement($permissions);
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function addUserRole(UserRole $userRoles)
    {
        $this->userRoles[] = $userRoles;

        return $this;
    }

    public function removeUserRole(UserRole $userRoles)
    {
        $this->userRoles->removeElement($userRoles);
    }

    public function getUserRoles()
    {
        return $this->userRoles;
    }

    public function addGroup(Group $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    public function removeGroup(Group $groups)
    {
        $this->groups->removeElement($groups);
    }

    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return $this
     */
    public function addSetting(RoleSettingInterface $setting)
    {
        $this->settings->set($setting->getKey(), $setting);

        return $this;
    }

    /**
     * @return void
     */
    public function removeSetting(RoleSettingInterface $setting)
    {
        $this->settings->remove($setting->getKey());
    }

    /**
     * Get settings.
     *
     * @return Collection<string, RoleSettingInterface>
     */
    public function getSettings()
    {
        return $this->settings;
    }

    public function getSetting($key)
    {
        return $this->settings->get($key);
    }

    public function getAnonymous(): bool
    {
        return $this->anonymous;
    }

    public function setAnonymous(bool $anonymous)
    {
        $this->anonymous = $anonymous;
    }
}
