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

use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

interface ExcerptInterface
{
    public function getExcerptTitle(): ?string;

    public function setExcerptTitle(?string $excerptTitle): void;

    public function getExcerptMore(): ?string;

    public function setExcerptMore(?string $excerptMore): void;

    public function getExcerptDescription(): ?string;

    public function setExcerptDescription(?string $excerptDescription): void;

    /**
     * @return CategoryInterface[]
     */
    public function getExcerptCategories(): array;

    /**
     * @return int[]
     */
    public function getExcerptCategoryIds(): array;

    /**
     * @param CategoryInterface[] $excerptCategories
     */
    public function setExcerptCategories(array $excerptCategories): void;

    /**
     * @return TagInterface[]
     */
    public function getExcerptTags(): array;

    /**
     * @return string[]
     */
    public function getExcerptTagNames(): array;

    /**
     * @param TagInterface[] $excerptTags
     */
    public function setExcerptTags(array $excerptTags): void;

    /**
     * @return array{id: int}|null
     */
    public function getExcerptImage(): ?array;

    /**
     * @param array{id: int}|null $excerptImage
     */
    public function setExcerptImage(?array $excerptImage): void;

    /**
     * @return array{id: int}|null
     */
    public function getExcerptIcon(): ?array;

    /**
     * @param array{id: int}|null $excerptIcon
     */
    public function setExcerptIcon(?array $excerptIcon): void;
}
