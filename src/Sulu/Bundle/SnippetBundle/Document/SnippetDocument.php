<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Document;

use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuditableBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\StructureTypeFilingBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocalizedTitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Behavior\Path\AliasFilingBehavior;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;

/**
 * Snippet document.
 */
class SnippetDocument implements
    NodeNameBehavior,
    LocalizedAuditableBehavior,
    AutoNameBehavior,
    AliasFilingBehavior,
    StructureTypeFilingBehavior,
    StructureBehavior,
    WorkflowStageBehavior,
    UuidBehavior,
    PathBehavior,
    LocalizedTitleBehavior,
    ExtensionBehavior
{
    public const RESOURCE_KEY = 'snippets';
    public const LIST_KEY = 'snippets';

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var int|null
     */
    private $creator;

    /**
     * @var int|null
     */
    private $changer;

    /**
     * @var object
     */
    private $parent;

    /**
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $workflowStage;

    /**
     * @var \DateTime
     */
    private $published;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string|null
     */
    private $structureType;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $originalLocale;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $nodeName;

    /**
     * @var array<mixed[]>|ExtensionContainer
     */
    private $extensions;

    public function __construct()
    {
        $this->workflowStage = WorkflowStage::TEST;
        $this->structure = new Structure();
        $this->extensions = new ExtensionContainer();
    }

    public function getNodeName()
    {
        return $this->nodeName;
    }

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the title.
     */
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

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
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
        $this->structureType = (string) $structureType;
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
}
