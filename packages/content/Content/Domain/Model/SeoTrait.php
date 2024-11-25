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

namespace Sulu\Bundle\ContentBundle\Content\Domain\Model;

trait SeoTrait
{
    /**
     * @var string|null
     */
    private $seoTitle;

    /**
     * @var string|null
     */
    private $seoDescription;

    /**
     * @var string|null
     */
    private $seoKeywords;

    /**
     * @var string|null
     */
    private $seoCanonicalUrl;

    /**
     * @var bool
     */
    private $seoNoIndex = false;

    /**
     * @var bool
     */
    private $seoNoFollow = false;

    /**
     * @var bool
     */
    private $seoHideInSitemap = false;

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(?string $seoTitle): void
    {
        $this->seoTitle = $seoTitle;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function setSeoDescription(?string $seoDescription): void
    {
        $this->seoDescription = $seoDescription;
    }

    public function getSeoKeywords(): ?string
    {
        return $this->seoKeywords;
    }

    public function setSeoKeywords(?string $seoKeywords): void
    {
        $this->seoKeywords = $seoKeywords;
    }

    public function getSeoCanonicalUrl(): ?string
    {
        return $this->seoCanonicalUrl;
    }

    public function setSeoCanonicalUrl(?string $seoCanonicalUrl): void
    {
        $this->seoCanonicalUrl = $seoCanonicalUrl;
    }

    public function getSeoNoIndex(): bool
    {
        return $this->seoNoIndex;
    }

    public function setSeoNoIndex(bool $seoNoIndex): void
    {
        $this->seoNoIndex = $seoNoIndex;
    }

    public function getSeoNoFollow(): bool
    {
        return $this->seoNoFollow;
    }

    public function setSeoNoFollow(bool $seoNoFollow): void
    {
        $this->seoNoFollow = $seoNoFollow;
    }

    public function getSeoHideInSitemap(): bool
    {
        return $this->seoHideInSitemap;
    }

    public function setSeoHideInSitemap(bool $seoHideInSitemap): void
    {
        $this->seoHideInSitemap = $seoHideInSitemap;
    }
}
