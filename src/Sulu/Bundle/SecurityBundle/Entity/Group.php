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
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * @deprecated The group functionality was deprecated in Sulu 2.1 and will be removed in Sulu 3.0
 */
class Group extends ApiEntity implements AuditableInterface
{
    use AuditableTrait;

    /**
     * @var int
     */
    #[Exclude]
    private $lft;

    /**
     * @var int
     */
    #[Exclude]
    private $rgt;

    /**
     * @var int
     */
    #[Exclude]
    private $depth;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $id;

    /**
     * @var Collection<int, self>
     */
    private $children;

    /**
     * @var Collection<int, UserGroup>
     */
    private $userGroups;

    /**
     * @var Group|null
     */
    private $parent;

    /**
     * @var Collection<int, RoleInterface>
     */
    private $roles;

    /**
     * @var UserInterface|null
     */
    #[Exclude]
    protected $changer;

    /**
     * @var UserInterface|null
     */
    #[Exclude]
    protected $creator;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->userGroups = new ArrayCollection();
        $this->roles = new ArrayCollection();
    }

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return Group
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return Group
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return Group
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Group
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
     * Add children.
     *
     * @return Group
     */
    public function addChildren(self $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @return void
     */
    public function removeChildren(self $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children.
     *
     * @return Collection<int, self>
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add userGroups.
     *
     * @return Group
     */
    public function addUserGroup(UserGroup $userGroups)
    {
        $this->userGroups[] = $userGroups;

        return $this;
    }

    /**
     * Remove userGroups.
     *
     * @return void
     */
    public function removeUserGroup(UserGroup $userGroups)
    {
        $this->userGroups->removeElement($userGroups);
    }

    /**
     * Get userGroups.
     *
     * @return Collection<int, UserGroup>
     */
    public function getUserGroups()
    {
        return $this->userGroups;
    }

    /**
     * Set parent.
     *
     * @return Group
     */
    public function setParent(?self $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return Group|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add roles.
     *
     * @return Group
     */
    public function addRole(RoleInterface $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles.
     *
     * @return void
     */
    public function removeRole(RoleInterface $roles)
    {
        $this->roles->removeElement($roles);
    }

    /**
     * Get roles.
     *
     * @return Collection<int, RoleInterface>
     */
    public function getRoles()
    {
        return $this->roles;
    }
}
