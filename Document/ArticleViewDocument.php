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

use ONGR\ElasticsearchBundle\Annotation\Document;
use ONGR\ElasticsearchBundle\Annotation\Embedded;
use ONGR\ElasticsearchBundle\Annotation\Id;
use ONGR\ElasticsearchBundle\Annotation\Property;

/**
 * Indexable document for articles.
 *
 * Following annotation will be set by the annotation reader if this document is used for mapping.
 * Document(type="article")
 */
class ArticleViewDocument implements ArticleViewDocumentInterface
{
    /**
     * @var string
     *
     * @Id
     */
    protected $id;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    protected $uuid;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    protected $locale;

    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"},
     *            "value":{"type":"text"}
     *         }
     *     }
     * )
     */
    protected $title;

    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *         "analyzer": "pathAnalyzer",
     *         "fields":{
     *            "raw":{"type":"keyword"},
     *            "value":{"type":"text"}
     *         }
     *     }
     * )
     */
    protected $routePath;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    protected $parentPageUuid;

    /**
     * @var string
     *
     * @Property(
     *     type="keyword"
     * )
     */
    protected $type;

    /**
     * @var string
     *
     * @Property(
     *     type="keyword"
     * )
     */
    protected $typeTranslation;

    /**
     * @var string
     *
     * @Property(
     *     type="keyword"
     * )
     */
    protected $structureType;

    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"},
     *            "value":{"type":"text"}
     *         }
     *     }
     * )
     */
    protected $changerFullName;

    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"},
     *            "value":{"type":"text"}
     *         }
     *     }
     * )
     */
    protected $creatorFullName;

    /**
     * @var \DateTime
     *
     * @Property(type="date")
     */
    protected $changed;

    /**
     * @var \DateTime
     *
     * @Property(type="date")
     */
    protected $created;

    /**
     * @var ExcerptViewObject
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\ExcerptViewObject")
     */
    protected $excerpt;

    /**
     * @var SeoViewObject
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\SeoViewObject")
     */
    protected $seo;

    /**
     * @var \DateTime
     *
     * @Property(type="date")
     */
    protected $authored;

    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *         "fields":{
     *            "raw":{"type":"keyword"},
     *            "value":{"type":"text"}
     *         }
     *     }
     * )
     */
    protected $authorFullName;

    /**
     * @var string
     *
     * @Property(type="text")
     */
    protected $teaserDescription = '';

    /**
     * @var int
     *
     * @Property(type="integer")
     */
    protected $teaserMediaId;

    /**
     * @var \DateTime
     *
     * @Property(type="date")
     */
    protected $published;

    /**
     * @var bool
     *
     * @Property(type="boolean")
     */
    protected $publishedState;

    /**
     * @var LocalizationStateViewObject
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\LocalizationStateViewObject")
     */
    protected $localizationState;

    /**
     * @var string
     *
     * @Property(type="text")
     */
    protected $authorId;

    /**
     * @var string
     *
     * @Property(type="text")
     */
    protected $creatorContactId;

    /**
     * @var string
     *
     * @Property(type="text")
     */
    protected $changerContactId;

    /**
     * @var ArticlePageViewObject[]
     *
     * @Embedded(class="Sulu\Bundle\ArticleBundle\Document\ArticlePageViewObject", multiple=true)
     */
    protected $pages = [];

    /**
     * @var string
     *
     * @Property(type="binary")
     */
    protected $contentData;

    /**
     * @var \ArrayObject
     */
    protected $content;

    /**
     * @var \ArrayObject
     */
    protected $view;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    protected $mainWebspace;

    /**
     * @var string[]
     *
     * @Property(type="keyword")
     */
    protected $additionalWebspaces;

    /**
     * @var string
     */
    protected $targetWebspace;

    /**
     * @var string[]
     *
     * @Property(type="text")
     */
    protected $contentFields;

    /**
     * @param string $uuid
     */
    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
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

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getRoutePath()
    {
        return $this->routePath;
    }

    public function setRoutePath($routePath)
    {
        $this->routePath = $routePath;

        return $this;
    }

    public function getParentPageUuid()
    {
        return $this->parentPageUuid;
    }

    public function setParentPageUuid($parentPageUuid)
    {
        $this->parentPageUuid = $parentPageUuid;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeTranslation()
    {
        return $this->typeTranslation;
    }

    public function setTypeTranslation($typeTranslation)
    {
        $this->typeTranslation = $typeTranslation;

        return $this;
    }

    public function getStructureType()
    {
        return $this->structureType;
    }

    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;

        return $this;
    }

    public function getChangerFullName()
    {
        return $this->changerFullName;
    }

    public function setChangerFullName($changerFullName)
    {
        $this->changerFullName = $changerFullName;

        return $this;
    }

    public function getCreatorFullName()
    {
        return $this->creatorFullName;
    }

    public function setCreatorFullName($creatorFullName)
    {
        $this->creatorFullName = $creatorFullName;

        return $this;
    }

    public function getChanged()
    {
        return $this->changed;
    }

    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    public function getExcerpt()
    {
        return $this->excerpt;
    }

    public function setExcerpt(ExcerptViewObject $excerpt)
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getSeo()
    {
        return $this->seo;
    }

    public function setSeo(SeoViewObject $seo)
    {
        $this->seo = $seo;

        return $this;
    }

    public function getAuthored()
    {
        return $this->authored;
    }

    public function setAuthored(?\DateTime $authored = null)
    {
        $this->authored = $authored;

        return $this;
    }

    public function getAuthorFullName()
    {
        return $this->authorFullName;
    }

    public function setAuthorFullName($authorFullName)
    {
        $this->authorFullName = $authorFullName;

        return $this;
    }

    public function getTeaserDescription()
    {
        return $this->teaserDescription;
    }

    public function setTeaserDescription($teaserDescription)
    {
        $this->teaserDescription = $teaserDescription;

        return $this;
    }

    public function getTeaserMediaId()
    {
        return $this->teaserMediaId;
    }

    public function setTeaserMediaId($teaserMediaId)
    {
        $this->teaserMediaId = $teaserMediaId;

        return $this;
    }

    public function getPublished()
    {
        return $this->published;
    }

    public function setPublished(?\DateTime $published = null)
    {
        $this->published = $published;

        return $this;
    }

    public function getPublishedState()
    {
        return $this->publishedState;
    }

    public function setPublishedState($publishedState)
    {
        $this->publishedState = $publishedState;

        return $this;
    }

    public function getLocalizationState()
    {
        return $this->localizationState;
    }

    public function setLocalizationState(LocalizationStateViewObject $localizationState)
    {
        $this->localizationState = $localizationState;

        return $this;
    }

    public function setAuthorId($authorId)
    {
        $this->authorId = $authorId;

        return $this;
    }

    public function getAuthorId()
    {
        return $this->authorId;
    }

    public function setCreatorContactId($creatorContactId)
    {
        $this->creatorContactId = $creatorContactId;

        return $this;
    }

    public function getCreatorContactId()
    {
        return $this->creatorContactId;
    }

    public function setChangerContactId($changerContactId)
    {
        $this->changerContactId = $changerContactId;

        return $this;
    }

    public function getChangerContactId()
    {
        return $this->changerContactId;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function setPages($pages)
    {
        $this->pages = $pages;

        return $this;
    }

    public function getContentData()
    {
        return $this->contentData;
    }

    public function setContentData($contentData)
    {
        $this->contentData = $contentData;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent(\ArrayObject $content)
    {
        $this->content = $content;

        return $this;
    }

    public function getView()
    {
        return $this->view;
    }

    public function setView(\ArrayObject $view)
    {
        $this->view = $view;

        return $this;
    }

    public function getMainWebspace()
    {
        return $this->mainWebspace;
    }

    public function setMainWebspace($mainWebspace)
    {
        $this->mainWebspace = $mainWebspace;

        return $this;
    }

    public function getAdditionalWebspaces()
    {
        return $this->additionalWebspaces;
    }

    public function setAdditionalWebspaces($additionalWebspace)
    {
        $this->additionalWebspaces = $additionalWebspace;

        return $this;
    }

    public function getTargetWebspace()
    {
        return $this->targetWebspace;
    }

    public function setTargetWebspace($targetWebspace)
    {
        $this->targetWebspace = $targetWebspace;

        return $this;
    }

    public function getContentFields(): array
    {
        return $this->contentFields;
    }

    public function setContentFields(array $contentFields): ArticleViewDocumentInterface
    {
        $this->contentFields = $contentFields;

        return $this;
    }
}
