<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;

class CategoryKeywordRemovedEvent extends DomainEvent
{
    private \Sulu\Bundle\CategoryBundle\Entity\CategoryInterface $category;

    private int $keywordId;

    private string $keywordTitle;

    private string $locale;

    public function __construct(
        CategoryInterface $category,
        string $locale,
        int $keywordId,
        string $keywordTitle
    ) {
        parent::__construct();

        $this->category = $category;
        $this->locale = $locale;
        $this->keywordId = $keywordId;
        $this->keywordTitle = $keywordTitle;
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }

    public function getEventType(): string
    {
        return 'keyword_removed';
    }

    public function getEventContext(): array
    {
        return [
            'keywordId' => $this->keywordId,
            'keywordTitle' => $this->keywordTitle,
        ];
    }

    public function getResourceKey(): string
    {
        return CategoryInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->category->getId();
    }

    public function getResourceLocale(): string
    {
        return $this->locale;
    }

    public function getResourceTitle(): ?string
    {
        $translation = $this->getCategoryTranslation();

        return $translation ? $translation->getTranslation() : null;
    }

    public function getResourceTitleLocale(): ?string
    {
        $translation = $this->getCategoryTranslation();

        return $translation ? $translation->getLocale() : null;
    }

    private function getCategoryTranslation(): ?CategoryTranslationInterface
    {
        if (!$translation = $this->category->findTranslationByLocale($this->locale)) {
            return $this->category->findTranslationByLocale($this->category->getDefaultLocale()) ?: null;
        }

        return $translation;
    }

    public function getResourceSecurityContext(): ?string
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }
}
