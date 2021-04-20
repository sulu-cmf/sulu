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

use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

class CategoryKeywordRemovedEvent extends DomainEvent
{
    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * @var int
     */
    private $keywordId;

    /**
     * @var string
     */
    private $keywordTitle;

    /**
     * @var string
     */
    private $locale;

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
        $translation = $this->category->findTranslationByLocale($this->getResourceTitleLocale());

        return $translation ? $translation->getTranslation() : null;
    }

    public function getResourceTitleLocale(): string
    {
        return $this->getResourceLocale();
    }

    public function getResourceSecurityContext(): ?string
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }
}
