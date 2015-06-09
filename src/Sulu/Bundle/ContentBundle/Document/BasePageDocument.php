<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Document;

use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\NavigationContextBehavior;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\Behavior\Audit\BlameBehavior;
use Sulu\Component\DocumentManager\Behavior\Audit\TimestampBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;

/**
 * Base document for Page-like documents (i.e. Page and Home documents).
 */
class BasePageDocument implements
    NodeNameBehavior,
    TimestampBehavior,
    BlameBehavior,
    ParentBehavior,
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
    WebspaceBehavior
{
    /**
     * @var string
     */
    protected $nodeName;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var \DateTime
     */
    protected $creator;
    
    /**
     * @var int
     */
    protected $changer;

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
    protected $resourceSegment;

    /**
     * @var string[]
     */
    protected $navigationContexts = array();

    /**
     * @var int
     */
    protected $redirectType;

    /**
     * @var object
     */
    protected $redirectTarget;

    /**
     * @var string
     */
    protected $redirectExternal;

    /**
     * @var int
     */
    protected $workflowStage;

    /**
     * @var bool
     */
    protected $published;

    /**
     * @var bool
     */
    protected $shadowLocaleEnabled = false;

    /**
     * @var string
     */
    protected $shadowLocale;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $structureType;

    /**
     * @var PropertyContainerInterface
     */
    protected $structure;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var ChildrenCollection
     */
    protected $children;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var ExtensionContainer
     */
    protected $extensions;

    /**
     * @var string
     */
    protected $webspaceName;

    public function __construct()
    {
        $this->workflowStage = WorkflowStage::TEST;
        $this->redirectType = RedirectType::NONE;
        $this->structure = new PropertyContainer();
        $this->extensions =  new ExtensionContainer();
        $this->children = new \ArrayIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeName() 
    {
        return $this->nodeName;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle() 
    {
        return $this->title;
    }

    /**
     * {@inheritDoc}
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated() 
    {
        return $this->created;
    }

    /**
     * {@inheritDoc}
     */
    public function getChanged() 
    {
        return $this->changed;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreator() 
    {
        return $this->creator;
    }

    /**
     * {@inheritDoc}
     */
    public function getChanger() 
    {
        return $this->changer;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent() 
    {
        return $this->parent;
    }

    /**
     * {@inheritDoc}
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceSegment() 
    {
        return $this->resourceSegment;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setResourceSegment($resourceSegment)
    {
        $this->resourceSegment = $resourceSegment;
    }

    /**
     * {@inheritDoc}
     */
    public function getNavigationContexts() 
    {
        return $this->navigationContexts;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setNavigationContexts(array $navigationContexts = array())
    {
        $this->navigationContexts = $navigationContexts;
    }

    /**
     * {@inheritDoc}
     */
    public function getRedirectType() 
    {
        return $this->redirectType;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setRedirectType($redirectType)
    {
        $this->redirectType = $redirectType;
    }

    /**
     * {@inheritDoc}
     */
    public function getRedirectTarget() 
    {
        return $this->redirectTarget;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setRedirectTarget($redirectTarget)
    {
        $this->redirectTarget = $redirectTarget;
    }

    /**
     * {@inheritDoc}
     */
    public function getRedirectExternal() 
    {
        return $this->redirectExternal;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setRedirectExternal($redirectExternal)
    {
        $this->redirectExternal = $redirectExternal;
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowStage() 
    {
        return $this->workflowStage;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setWorkflowStage($workflowStage)
    {
        $this->workflowStage = $workflowStage;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * {@inheritDoc}
     */
    public function getShadowLocale() 
    {
        return $this->shadowLocale;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setShadowLocale($shadowLocale)
    {
        $this->shadowLocale = $shadowLocale;
    }

    /**
     * {@inheritDoc}
     */
    public function isShadowLocaleEnabled() 
    {
        return $this->shadowLocaleEnabled;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setShadowLocaleEnabled($shadowLocaleEnabled)
    {
        $this->shadowLocaleEnabled = $shadowLocaleEnabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getUuid() 
    {
        return $this->uuid;
    }

    /**
     * {@inheritDoc}
     */
    public function getStructureType() 
    {
        return $this->structureType;
    }

    /**
     * {@inheritDoc}
     */
    public function getStructure()
    {
        return $this->structure;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;
    }


    /**
     * {@inheritDoc}
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath() 
    {
        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function getExtensionsData() 
    {
        return $this->extensions;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setExtensionsData($extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function setExtension($name, $data)
    {
        $this->extensions[$name] = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getWebspaceName()
    {
        return $this->webspaceName;
    }
}
