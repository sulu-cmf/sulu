<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Document;

use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuditableBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedLastModifiedBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\NavigationContextBehavior;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocalizedTitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Behavior\VersionBehavior;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;
use Sulu\Component\DocumentManager\Version;

/**
 * Base document for Page-like documents (i.e. Page and Home documents).
 */
class BasePageDocument implements
    NodeNameBehavior,
    LocalizedStructureBehavior,
    ResourceSegmentBehavior,
    NavigationContextBehavior,
    RedirectTypeBehavior,
    WorkflowStageBehavior,
    ShadowLocaleBehavior,
    UuidBehavior,
    ChildrenBehavior,
    PathBehavior,
    ExtensionBehavior,
    OrderBehavior,
    WebspaceBehavior,
    SecurityBehavior,
    LocalizedAuditableBehavior,
    LocalizedTitleBehavior,
    VersionBehavior,
    LocalizedAuthorBehavior,
    LocalizedLastModifiedBehavior
{
    public const RESOURCE_KEY = 'pages';

    /**
     * The name of this node.
     *
     * @var string
     */
    protected $nodeName;

    /**
     * Datetime of create document.
     *
     * @var \DateTime
     */
    protected $created;

    /**
     * Changed date of page.
     *
     * @var \DateTime
     */
    protected $changed;

    /**
     * User ID of creator.
     *
     * @var int|null
     */
    protected $creator;

    /**
     * User ID of changer.
     *
     * @var int|null
     */
    protected $changer;

    /**
     * Title of document.
     *
     * @var string
     */
    protected $title;

    /**
     * Segment.
     *
     * @var string
     */
    protected $resourceSegment;

    /**
     * @var string[]
     */
    protected $navigationContexts = [];

    /**
     * Type of redirection.
     *
     * @var int
     */
    protected $redirectType;

    /**
     * The target of redirection.
     *
     * @var object|null
     */
    protected $redirectTarget;

    /**
     * The External redirect.
     *
     * @var string|null
     */
    protected $redirectExternal;

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
     * Shadow locale is enabled.
     *
     * @var bool
     */
    protected $shadowLocaleEnabled = false;

    /**
     * Shadow locale.
     *
     * @var string|null
     */
    protected $shadowLocale;

    /**
     * Universal Identifier.
     *
     * @var string
     */
    protected $uuid;

    /**
     * Document's type of structure ie default, complex...
     *
     * @var string|null
     */
    protected $structureType;

    /**
     * Structure.
     *
     * @var StructureInterface
     */
    protected $structure;

    /**
     * Document's locale.
     *
     * @var string
     */
    protected $locale;

    /**
     * Document's original locale.
     *
     * @var string
     */
    protected $originalLocale;

    /**
     * Document's children.
     *
     * @var ChildrenCollection
     */
    protected $children;

    /**
     * Path of Document.
     *
     * @var string
     */
    protected $path;

    /**
     * Document's extensions ie seo, ...
     *
     * @var array<mixed[]>|ExtensionContainer
     */
    protected $extensions;

    /**
     * Document's webspace name.
     *
     * @var string
     */
    protected $webspaceName;

    /**
     * Document's order.
     *
     * @var int
     */
    protected $suluOrder;

    /**
     * List of permissions.
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * List of versions.
     *
     * @var Version[]
     */
    protected $versions = [];

    /**
     * Date of lastModified.
     *
     * @var \DateTime|null
     */
    protected $lastModified;

    /**
     * Date of authoring.
     *
     * @var \DateTime
     */
    protected $authored;

    /**
     * Id of author.
     *
     * @var int|null
     */
    protected $author;

    public function __construct()
    {
        $this->workflowStage = WorkflowStage::TEST;
        $this->redirectType = RedirectType::NONE;
        $this->structure = new Structure();
        $this->extensions = new ExtensionContainer();
        $this->children = new \ArrayIterator();
    }

    public function getNodeName()
    {
        return $this->nodeName;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
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

    public function getResourceSegment()
    {
        return $this->resourceSegment;
    }

    public function setResourceSegment($resourceSegment)
    {
        $this->resourceSegment = $resourceSegment;
    }

    public function getNavigationContexts()
    {
        return $this->navigationContexts;
    }

    public function setNavigationContexts(array $navigationContexts = [])
    {
        $this->navigationContexts = $navigationContexts;
    }

    public function getRedirectType()
    {
        return $this->redirectType;
    }

    public function setRedirectType($redirectType)
    {
        $this->redirectType = $redirectType;
    }

    public function getRedirectTarget()
    {
        return $this->redirectTarget;
    }

    public function setRedirectTarget($redirectTarget)
    {
        $this->redirectTarget = $redirectTarget;
    }

    public function getRedirectExternal()
    {
        return $this->redirectExternal;
    }

    public function setRedirectExternal($redirectExternal)
    {
        $this->redirectExternal = $redirectExternal;
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

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getStructureType()
    {
        return $this->structureType;
    }

    public function getStructure()
    {
        return $this->structure;
    }

    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;
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

    public function setOriginalLocale($originalLocale)
    {
        $this->originalLocale = $originalLocale;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getPath()
    {
        return $this->path;
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

    public function getWebspaceName()
    {
        return $this->webspaceName;
    }

    public function getSuluOrder()
    {
        return $this->suluOrder;
    }

    public function setSuluOrder($order)
    {
        $this->suluOrder = $order;
    }

    public function setPermissions(array $permissions)
    {
        $this->permissions = $permissions;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function getVersions()
    {
        return $this->versions;
    }

    public function setVersions($versions)
    {
        $this->versions = $versions;
    }

    public function getLastModifiedEnabled()
    {
        return null !== $this->lastModified;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastModified()
    {
        return $this->lastModified;
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

    public function getAuthored()
    {
        return $this->authored;
    }

    /**
     * @param \DateTime $authored
     *
     * @return void
     */
    public function setAuthored($authored)
    {
        $this->authored = $authored;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param int|null $contactId
     *
     * @return void
     */
    public function setAuthor($contactId)
    {
        $this->author = $contactId;
    }
}
