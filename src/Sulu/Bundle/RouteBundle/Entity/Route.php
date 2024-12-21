<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * Represents a concrete route in the route-pool.
 */
#[ExclusionPolicy('all')]
class Route implements RouteInterface, AuditableInterface
{
    use AuditableTrait;

    /**
     * @var int
     */
    #[Expose]
    private $id;

    /**
     * @var string
     */
    #[Expose]
    private $path;

    /**
     * @var string
     */
    #[Expose]
    private $locale;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var string
     */
    private $entityId;

    /**
     * @var bool
     */
    #[Expose]
    private $history = false;

    /**
     * @var RouteInterface|null
     */
    private $target;

    /**
     * @var Collection<int, RouteInterface>
     */
    protected $histories;

    /**
     * @param string $path
     * @param string $entityId
     * @param string $entityClass
     * @param string $locale
     */
    public function __construct($path = null, $entityId = null, $entityClass = null, $locale = null)
    {
        $this->path = $path;
        $this->entityId = $entityId;
        $this->entityClass = $entityClass;
        $this->locale = $locale;
        $this->histories = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getEntityClass()
    {
        return $this->entityClass;
    }

    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    public function getEntityId()
    {
        return $this->entityId;
    }

    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function isHistory()
    {
        return $this->history;
    }

    public function setHistory($history)
    {
        $this->history = $history;

        return $this;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function setTarget(?RouteInterface $target = null)
    {
        $this->target = $target;

        return $this;
    }

    public function removeTarget()
    {
        $this->target = null;

        return $this;
    }

    public function getHistories()
    {
        return $this->histories;
    }

    public function addHistory(RouteInterface $history)
    {
        $this->histories[] = $history;

        return $this;
    }

    #[VirtualProperty]
    public function getCreated()
    {
        return $this->created;
    }

    #[VirtualProperty]
    public function getResourcelocator()
    {
        return $this->path;
    }
}
