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

use Sulu\Bundle\ArticleBundle\Document\Behavior\DateShardingBehavior;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutableBehavior;
use Sulu\Bundle\ArticleBundle\Document\Behavior\RoutablePageBehavior;
use Sulu\Bundle\ArticleBundle\Document\Behavior\WebspaceBehavior;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuditableBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocalizedTitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;
use Sulu\Component\DocumentManager\Behavior\VersionBehavior;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;
use Sulu\Component\DocumentManager\Version;

/**
 * Represents an article in phpcr.
 */
class ArticleDocument implements UuidBehavior,
    NodeNameBehavior,
    AutoNameBehavior,
    PathBehavior,
    LocalizedTitleBehavior,
    StructureBehavior,
    LocalizedStructureBehavior,
    LocalizedAuditableBehavior,
    DateShardingBehavior,
    RoutableBehavior,
    ExtensionBehavior,
    WorkflowStageBehavior,
    VersionBehavior,
    LocalizedAuthorBehavior,
    ChildrenBehavior,
    ArticleInterface,
    ShadowLocaleBehavior,
    WebspaceBehavior,
    LocalizedLastModifiedBehavior
{
    public const RESOURCE_KEY = 'articles';

    public const LIST_KEY = 'articles';

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $nodeName;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var object
     */
    protected $parent;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $pageTitle;

    /**
     * @var array
     */
    protected $pages;

    /**
     * @var RouteInterface
     */
    protected $route;

    /**
     * @var string
     */
    protected $routePath;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $originalLocale;

    /**
     * @var string|null
     */
    protected $structureType;

    /**
     * @var StructureInterface
     */
    protected $structure;

    /**
     * @var int
     */
    protected $creator;

    /**
     * @var int
     */
    protected $changer;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var \DateTime|null
     */
    protected $lastModified;

    /**
     * @var int
     */
    protected $author;

    /**
     * @var \DateTime
     */
    protected $authored;

    /**
     * Document's extensions ie seo, ...
     *
     * @var ExtensionContainer
     */
    protected $extensions;

    /**
     * Workflow Stage currently Test or Published.
     *
     * @var int
     */
    protected $workflowStage;

    /**
     * Is Document is published.
     *
     * @var bool
     */
    protected $published;

    /**
     * List of versions.
     *
     * @var Version[]
     */
    protected $versions = [];

    /**
     * @var ChildrenCollection
     */
    protected $children;

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

    /**
     * Main webspace.
     *
     * @var null|string
     */
    protected $mainWebspace;

    /**
     * Additional webspaces.
     *
     * @var null|string[]
     */
    protected $additionalWebspaces;

    public function __construct()
    {
        $this->structure = new Structure();
        $this->extensions = new ExtensionContainer();
        $this->children = new \ArrayIterator();
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

    public function getNodeName()
    {
        return $this->nodeName;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($document)
    {
        $this->parent = $document;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns route.
     *
     * @return RouteInterface
     */
    public function getRoute()
    {
        return $this->route;
    }

    public function setRoute(RouteInterface $route)
    {
        $this->route = $route;
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

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getOriginalLocale()
    {
        return $this->originalLocale;
    }

    public function setOriginalLocale($locale)
    {
        $this->originalLocale = $locale;
    }

    public function getStructureType(): ?string
    {
        return $this->structureType;
    }

    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;
    }

    public function getStructure(): StructureInterface
    {
        return $this->structure;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param int|null $userId
     *
     * @return void
     */
    public function setCreator($userId)
    {
        $this->creator = $userId;
    }

    public function getChanger()
    {
        return $this->changer;
    }

    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     *
     * @return void
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Returns identifier.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->getUuid();
    }

    public function getExtensionsData()
    {
        return $this->extensions;
    }

    public function setExtensionsData($extensions)
    {
        $this->extensions = $extensions;
    }

    public function setExtension($name, $data)
    {
        $this->extensions[$name] = $data;
    }

    public function getWorkflowStage()
    {
        return $this->workflowStage;
    }

    public function setWorkflowStage($workflowStage)
    {
        $this->workflowStage = $workflowStage;
    }

    public function getPublished()
    {
        return $this->published;
    }

    public function getAuthored()
    {
        return $this->authored;
    }

    /**
     * @return bool
     */
    public function getLastModifiedEnabled()
    {
        return null !== $this->lastModified;
    }

    /**
     * @param \DateTime|null $lastModified
     *
     * @return void
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    public function setAuthored($authored)
    {
        $this->authored = $authored;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getVersions()
    {
        return $this->versions;
    }

    public function setVersions($versions)
    {
        $this->versions = $versions;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getArticleUuid(): string
    {
        return $this->getUuid();
    }

    public function getPageUuid(): string
    {
        return $this->getUuid();
    }

    public function getPageNumber(): int
    {
        return 1;
    }

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

    /**
     * Returns pages.
     *
     * @return array
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Set pages.
     *
     * @param array $pages
     *
     * @return $this
     */
    public function setPages($pages)
    {
        $this->pages = $pages;

        return $this;
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

    public function getMainWebspace(): ?string
    {
        return $this->mainWebspace;
    }

    public function setMainWebspace(?string $mainWebspace): WebspaceBehavior
    {
        $this->mainWebspace = $mainWebspace;

        return $this;
    }

    public function getAdditionalWebspaces(): ?array
    {
        return $this->additionalWebspaces;
    }

    public function setAdditionalWebspaces(?array $additionalWebspaces): WebspaceBehavior
    {
        $this->additionalWebspaces = $additionalWebspaces;

        return $this;
    }
}
