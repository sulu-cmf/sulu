<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * Entity class for target group.
 */
class TargetGroup implements TargetGroupInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var bool
     */
    private $allWebspaces = false;

    /**
     * @var bool
     */
    private $active = false;

    /**
     * @var Collection<int, TargetGroupWebspaceInterface>
     */
    private $webspaces;

    /**
     * @var Collection<int, TargetGroupRuleInterface>
     */
    private $rules;

    /**
     * Initialization of collections.
     */
    public function __construct()
    {
        $this->webspaces = new ArrayCollection();
        $this->rules = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    public function isAllWebspaces()
    {
        return $this->allWebspaces;
    }

    public function setAllWebspaces($allWebspaces)
    {
        $this->allWebspaces = $allWebspaces;

        return $this;
    }

    public function isActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    public function getWebspaces()
    {
        return $this->webspaces;
    }

    #[VirtualProperty]
    #[SerializedName('webspaceKeys')]
    public function getWebspaceKeys()
    {
        return \array_values(
            \array_map(function(TargetGroupWebspaceInterface $targetGroupWebspace) {
                return $targetGroupWebspace->getWebspaceKey();
            }, $this->webspaces->toArray())
        );
    }

    public function addWebspace(TargetGroupWebspaceInterface $webspace)
    {
        $this->webspaces[] = $webspace;

        return $this;
    }

    public function removeWebspace(TargetGroupWebspaceInterface $webspace)
    {
        $this->webspaces->removeElement($webspace);

        return $this;
    }

    public function clearWebspaces()
    {
        $this->webspaces->clear();
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function addRule(TargetGroupRuleInterface $rule)
    {
        $this->rules[] = $rule;

        return $this;
    }

    public function removeRule(TargetGroupRuleInterface $rule)
    {
        $this->rules->removeElement($rule);

        return $this;
    }

    public function clearRules()
    {
        $this->rules->clear();
    }
}
