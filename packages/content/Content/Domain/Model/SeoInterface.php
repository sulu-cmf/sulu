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

interface SeoInterface
{
    public function getSeoTitle(): ?string;

    public function setSeoTitle(?string $seoTitle): void;

    public function getSeoDescription(): ?string;

    public function setSeoDescription(?string $seoDescription): void;

    public function getSeoKeywords(): ?string;

    public function setSeoKeywords(?string $seoKeywords): void;

    public function getSeoCanonicalUrl(): ?string;

    public function setSeoCanonicalUrl(?string $seoCanonicalUrl): void;

    public function getSeoNoIndex(): bool;

    public function setSeoNoIndex(bool $seoNoIndex): void;

    public function getSeoNoFollow(): bool;

    public function setSeoNoFollow(bool $seoNoFollow): void;

    public function getSeoHideInSitemap(): bool;

    public function setSeoHideInSitemap(bool $seoHideInSitemap): void;
}
