<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Collection\Collection;

/**
 * Interface for indexable article-document.
 */
interface ArticleViewDocumentInterface
{
    /**
     * Returns id.
     *
     * @return string
     */
    public function getId();

    /**
     * Set id.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId($id);

    /**
     * Returns uuid.
     *
     * @return string
     */
    public function getUuid();

    /**
     * Set uuid.
     *
     * @param string $uuid
     *
     * @return $this
     */
    public function setUuid($uuid);

    /**
     * Returns locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale);

    /**
     * Returns title.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title);

    /**
     * Returns route-path.
     *
     * @return string
     */
    public function getRoutePath();

    /**
     * Set route-path.
     *
     * @param string $routePath
     *
     * @return $this
     */
    public function setRoutePath($routePath);

    /**
     * Returns parent-page-uuid.
     *
     * @return string
     */
    public function getParentPageUuid();

    /**
     * Set parent-page-uuid.
     *
     * @param string $parentPageUuid
     *
     * @return $this
     */
    public function setParentPageUuid($parentPageUuid);

    /**
     * Returns type.
     *
     * @return string
     */
    public function getType();

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type);

    /**
     * Returns type translation.
     *
     * @return string
     */
    public function getTypeTranslation();

    /**
     * Set type translation.
     *
     * @param string $typeTranslation
     *
     * @return $this
     */
    public function setTypeTranslation($typeTranslation);

    /**
     * Returns structure-type.
     *
     * @return string
     */
    public function getStructureType();

    /**
     * Set structure-type.
     *
     * @param string $structureType
     *
     * @return $this
     */
    public function setStructureType($structureType);

    /**
     * Returns changer full name.
     *
     * @return string
     */
    public function getChangerFullName();

    /**
     * Set changer full name.
     *
     * @param string $changerFullName
     *
     * @return $this
     */
    public function setChangerFullName($changerFullName);

    /**
     * Returns creator full name.
     *
     * @return string
     */
    public function getCreatorFullName();

    /**
     * Set creator full name.
     *
     * @param string $creatorFullName
     *
     * @return $this
     */
    public function setCreatorFullName($creatorFullName);

    /**
     * Return changed.
     *
     * @return \DateTime
     */
    public function getChanged();

    /**
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return $this
     */
    public function setChanged($changed);

    /**
     * Returns created.
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return $this
     */
    public function setCreated($created);

    /**
     * Returns excerpt.
     *
     * @return ExcerptViewObject
     */
    public function getExcerpt();

    /**
     * Set excerpt.
     *
     * @return $this
     */
    public function setExcerpt(ExcerptViewObject $excerpt);

    /**
     * Returns seo.
     *
     * @return SeoViewObject
     */
    public function getSeo();

    /**
     * Set seo.
     *
     * @return $this
     */
    public function setSeo(SeoViewObject $seo);

    /**
     * Returns authored.
     *
     * @return \DateTime
     */
    public function getAuthored();

    /**
     * Set authored date.
     *
     * @return $this
     */
    public function setAuthored(?\DateTime $authored = null);

    /**
     * Returns author full name.
     *
     * @return string
     */
    public function getAuthorFullName();

    /**
     * Set author full name.
     *
     * @param string $authorFullName
     *
     * @return $this
     */
    public function setAuthorFullName($authorFullName);

    /**
     * Returns teaser-description.
     *
     * @return string
     */
    public function getTeaserDescription();

    /**
     * Set teaser-description.
     *
     * @param string $teaserDescription
     *
     * @return $this
     */
    public function setTeaserDescription($teaserDescription);

    /**
     * Returns teaser-media-id.
     *
     * @return int
     */
    public function getTeaserMediaId();

    /**
     * Set teaser-media-id.
     *
     * @param int $teaserMediaId
     *
     * @return $this
     */
    public function setTeaserMediaId($teaserMediaId);

    /**
     * Get published.
     *
     * @return \DateTime
     */
    public function getPublished();

    /**
     * Set published.
     *
     * @return $this
     */
    public function setPublished(?\DateTime $published = null);

    /**
     * Get published state.
     *
     * @return bool
     */
    public function getPublishedState();

    /**
     * Set published state.
     *
     * @param bool $publishedState
     *
     * @return $this
     */
    public function setPublishedState($publishedState);

    /**
     * Get localization state.
     *
     * @return LocalizationStateViewObject
     */
    public function getLocalizationState();

    /**
     * Set localization state.
     *
     * @return $this
     */
    public function setLocalizationState(LocalizationStateViewObject $localizationState);

    /**
     * Set author id.
     *
     * @param string $authorId
     *
     * @return $this
     */
    public function setAuthorId($authorId);

    /**
     * Get author id.
     *
     * @return string
     */
    public function getAuthorId();

    /**
     * Set creator contact id.
     *
     * @param string $creatorContactId
     *
     * @return $this
     */
    public function setCreatorContactId($creatorContactId);

    /**
     * Get creator contact id.
     *
     * @return string
     */
    public function getCreatorContactId();

    /**
     * Set creator contact id.
     *
     * @param string $changerContactId
     *
     * @return $this
     */
    public function setChangerContactId($changerContactId);

    /**
     * Get changer contact id.
     *
     * @return string
     */
    public function getChangerContactId();

    /**
     * Returns pages.
     *
     * @return ArticlePageViewObject[]
     */
    public function getPages();

    /**
     * Set pages.
     *
     * @param Collection|ArrayCollection $pages
     *
     * @return $this
     */
    public function setPages($pages);

    /**
     * Returns contentData.
     *
     * @return string
     */
    public function getContentData();

    /**
     * Set contentData.
     *
     * @param string $contentData
     *
     * @return $this
     */
    public function setContentData($contentData);

    /**
     * Returns content.
     *
     * @return \ArrayObject
     */
    public function getContent();

    /**
     * Set content.
     *
     * @return \ArrayObject
     */
    public function setContent(\ArrayObject $content);

    /**
     * Returns view.
     *
     * @return \ArrayObject
     */
    public function getView();

    /**
     * Set view.
     *
     * @return $this
     */
    public function setView(\ArrayObject $view);

    /**
     * @return string
     */
    public function getMainWebspace();

    /**
     * @param null|string $mainWebspace
     *
     * @return $this
     */
    public function setMainWebspace($mainWebspace);

    /**
     * @return null|string[]
     */
    public function getAdditionalWebspaces();

    /**
     * @param null|string[] $additionalWebspace
     *
     * @return $this
     */
    public function setAdditionalWebspaces($additionalWebspace);

    /**
     * @return string
     */
    public function getTargetWebspace();

    /**
     * @param string $targetWebspace
     *
     * @return $this
     */
    public function setTargetWebspace($targetWebspace);

    /**
     * @return string[]
     */
    public function getContentFields(): array;

    /**
     * @param string[] $searchableContent
     */
    public function setContentFields(array $searchableContent): self;
}
