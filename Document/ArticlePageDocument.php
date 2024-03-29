<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use Sulu\Bundle\ArticleBundle\Document\Behavior\PageBehavior;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutablePageBehavior;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocalizedTitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;

/**
 * Represents an article-page in phpcr.
 */
class ArticlePageDocument implements UuidBehavior,
    LocalizedTitleBehavior,
    ParentBehavior,
    AutoNameBehavior,
    PathBehavior,
    StructureBehavior,
    LocalizedStructureBehavior,
    RoutablePageBehavior,
    PageBehavior,
    ArticleInterface,
    ShadowLocaleBehavior
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $pageTitle;

    /**
     * @var ArticleDocument
     */
    protected $parent;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $originalLocale;

    /**
     * @var string
     */
    protected $structureType;

    /**
     * @var StructureInterface
     */
    protected $structure;

    /**
     * @var RouteInterface
     */
    protected $route;

    /**
     * @var string
     */
    protected $routePath;

    /**
     * @var int
     */
    protected $pageNumber;

    /**
     * Shadow locale is enabled.
     *
     * @var bool
     */
    protected $shadowLocaleEnabled = false;

    /**
     * Shadow locale.
     *
     * @var string
     */
    protected $shadowLocale;

    public function __construct()
    {
        $this->structure = new Structure();
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid): RoutablePageBehavior
    {
        $this->uuid = $uuid;

        return $this;
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
     * Returns pageTitle.
     */
    public function getPageTitle(): ?string
    {
        return $this->pageTitle;
    }

    /**
     * Set pageTitle.
     *
     * @param string $pageTitle
     *
     * @return $this
     */
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function getOriginalLocale()
    {
        return $this->originalLocale;
    }

    public function setOriginalLocale($originalLocale)
    {
        $this->originalLocale = $originalLocale;

        return $this;
    }

    public function getStructureType(): ?string
    {
        return $this->structureType;
    }

    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;

        return $this;
    }

    public function getStructure(): StructureInterface
    {
        return $this->structure;
    }

    public function getId()
    {
        return $this->uuid;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function setRoute(RouteInterface $route)
    {
        $this->route = $route;

        return $this;
    }

    public function removeRoute(): RoutablePageBehavior
    {
        $this->route = null;
        $this->routePath = null;

        return $this;
    }

    public function getRoutePath(): ?string
    {
        return $this->routePath;
    }

    public function setRoutePath($routePath): RoutablePageBehavior
    {
        $this->routePath = $routePath;

        return $this;
    }

    public function getClass(): string
    {
        return \get_class($this);
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function setPageNumber(int $pageNumber): PageBehavior
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    public function getArticleUuid(): string
    {
        return $this->getParent()->getUuid();
    }

    public function getPageUuid(): string
    {
        return $this->getUuid();
    }

    public function getWorkflowStage()
    {
        return $this->getParent()->getWorkflowStage();
    }

    public function getExtensionsData()
    {
        return $this->getParent()->getExtensionsData();
    }

    public function getShadowLocale()
    {
        return $this->shadowLocale;
    }

    public function setShadowLocale($shadowLocale)
    {
        $this->shadowLocale = $shadowLocale;
    }

    public function isShadowLocaleEnabled()
    {
        return $this->shadowLocaleEnabled;
    }

    public function setShadowLocaleEnabled($shadowLocaleEnabled)
    {
        $this->shadowLocaleEnabled = $shadowLocaleEnabled;
    }
}
