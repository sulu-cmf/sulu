<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Content;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\ExcerptViewObject;
use Sulu\Bundle\ArticleBundle\Document\SeoViewObject;
use Sulu\Component\SmartContent\ResourceItemInterface;

/**
 * Represents a resource-item for smart-content data-provider.
 */
class ArticleResourceItem implements ResourceItemInterface
{
    /**
     * @var ArticleViewDocumentInterface
     */
    private $article;

    /**
     * @var ArticleDocument
     */
    private $resource;

    public function __construct(ArticleViewDocumentInterface $article, ArticleDocument $resource)
    {
        $this->article = $article;
        $this->resource = $resource;
    }

    /**
     * Returns uuid.
     */
    public function getUuid(): string
    {
        return $this->article->getUuid();
    }

    /**
     * Returns locale.
     */
    public function getLocale(): string
    {
        return $this->article->getLocale();
    }

    /**
     * Returns title.
     */
    public function getTitle(): string
    {
        return $this->article->getTitle();
    }

    /**
     * Returns type.
     */
    public function getType(): string
    {
        return $this->article->getType();
    }

    /**
     * Returns changer.
     */
    public function getChanger(): string
    {
        return $this->article->getChangerFullName();
    }

    /**
     * Returns creator.
     */
    public function getCreator(): string
    {
        return $this->article->getCreatorFullName();
    }

    /**
     * Return changed.
     */
    public function getChanged(): \DateTime
    {
        return $this->article->getChanged();
    }

    /**
     * Returns created.
     */
    public function getCreated(): \DateTime
    {
        return $this->article->getCreated();
    }

    /**
     * Returns published.
     */
    public function getPublished(): \DateTime
    {
        return $this->article->getPublished();
    }

    /**
     * Returns lastModified.
     */
    public function getLastModified(): ?\DateTime
    {
        return $this->article->getLastModified();
    }

    /**
     * Returns authored.
     */
    public function getAuthored(): \DateTime
    {
        return $this->article->getAuthored();
    }

    /**
     * Returns excerpt.
     */
    public function getExcerpt(): ExcerptViewObject
    {
        return $this->article->getExcerpt();
    }

    /**
     * Returns seo.
     */
    public function getSeo(): SeoViewObject
    {
        return $this->article->getSeo();
    }

    /**
     * Returns route-path.
     */
    public function getRoutePath(): string
    {
        return $this->article->getRoutePath();
    }

    /**
     * Returns view-object.
     */
    public function getContent(): ArticleViewDocumentInterface
    {
        return $this->article;
    }

    public function getTargetWebspace(): string
    {
        return $this->article->getTargetWebspace();
    }

    public function getMainWebspace(): string
    {
        return $this->article->getMainWebspace();
    }

    /**
     * @return string[]|null
     */
    public function getAdditionalWebspaces(): ?array
    {
        return $this->article->getAdditionalWebspaces();
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getId()
    {
        return $this->getUuid();
    }

    public function getAuthorId()
    {
        return $this->article->getAuthorId();
    }

    public function getAuthorFullName()
    {
        return $this->article->getAuthorFullName();
    }
}
