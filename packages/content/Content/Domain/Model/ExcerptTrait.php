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

use Doctrine\Common\Collections\ArrayCollection;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

trait ExcerptTrait
{
    /**
     * @var string|null
     */
    private $excerptTitle;

    /**
     * @var string|null
     */
    private $excerptDescription;

    /**
     * @var string|null
     */
    private $excerptMore;

    /**
     * @var ArrayCollection<int, CategoryInterface>
     */
    private $excerptCategories;

    /**
     * @var ArrayCollection<int, TagInterface>
     */
    private $excerptTags;

    /**
     * @var int|null
     */
    private $excerptImageId;

    /**
     * @var int|null
     */
    private $excerptIconId;

    public function getExcerptTitle(): ?string
    {
        return $this->excerptTitle;
    }

    public function setExcerptTitle(?string $excerptTitle): void
    {
        $this->excerptTitle = $excerptTitle;
    }

    public function getExcerptDescription(): ?string
    {
        return $this->excerptDescription;
    }

    public function setExcerptDescription(?string $excerptDescription): void
    {
        $this->excerptDescription = $excerptDescription;
    }

    public function getExcerptMore(): ?string
    {
        return $this->excerptMore;
    }

    public function setExcerptMore(?string $excerptMore): void
    {
        $this->excerptMore = $excerptMore;
    }

    /**
     * @return int[]
     */
    public function getExcerptCategoryIds(): array
    {
        $this->initializeCategories();
        $categoryIds = [];
        foreach ($this->excerptCategories as $excerptCategory) {
            $categoryIds[] = $excerptCategory->getId();
        }

        return $categoryIds;
    }

    /**
     * @return CategoryInterface[]
     */
    public function getExcerptCategories(): array
    {
        $this->initializeCategories();

        return $this->excerptCategories->toArray();
    }

    /**
     * @param CategoryInterface[] $excerptCategories
     */
    public function setExcerptCategories(array $excerptCategories): void
    {
        $this->initializeCategories();
        $this->excerptCategories->clear();

        foreach ($excerptCategories as $excerptCategory) {
            $this->excerptCategories->add($excerptCategory);
        }
    }

    /**
     * @return TagInterface[]
     */
    public function getExcerptTags(): array
    {
        $this->initializeTags();

        return $this->excerptTags->toArray();
    }

    /**
     * @return string[]
     */
    public function getExcerptTagNames(): array
    {
        $this->initializeTags();
        $tagNames = [];
        foreach ($this->excerptTags as $excerptTag) {
            $tagNames[] = $excerptTag->getName();
        }

        return $tagNames;
    }

    /**
     * @param TagInterface[] $excerptTags
     */
    public function setExcerptTags(array $excerptTags): void
    {
        $this->initializeTags();
        $this->excerptTags->clear();

        foreach ($excerptTags as $excerptTag) {
            $this->excerptTags->add($excerptTag);
        }
    }

    /**
     * @return array{id: int}|null
     */
    public function getExcerptImage(): ?array
    {
        if (!$this->excerptImageId) {
            return null;
        }

        return [
            'id' => $this->excerptImageId,
        ];
    }

    /**
     * @param array{id: int}|null $excerptImage
     */
    public function setExcerptImage(?array $excerptImage): void
    {
        $this->excerptImageId = $excerptImage['id'] ?? null;
    }

    /**
     * @return array{id: int}|null
     */
    public function getExcerptIcon(): ?array
    {
        if (!$this->excerptIconId) {
            return null;
        }

        return [
            'id' => $this->excerptIconId,
        ];
    }

    /**
     * @param array{id: int}|null $excerptIcon
     */
    public function setExcerptIcon(?array $excerptIcon): void
    {
        $this->excerptIconId = $excerptIcon['id'] ?? null;
    }

    private function initializeTags(): void
    {
        if (null === $this->excerptTags) {
            $this->excerptTags = new ArrayCollection();
        }
    }

    private function initializeCategories(): void
    {
        if (null === $this->excerptCategories) {
            $this->excerptCategories = new ArrayCollection();
        }
    }
}
