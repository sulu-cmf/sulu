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

namespace Sulu\Bundle\PageBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;

class PageRemovedEvent extends DomainEvent
{
    /**
     * @var string
     */
    private $pageId;

    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var string|null
     */
    private $pageTitle;

    /**
     * @var string|null
     */
    private $pageTitleLocale;

    /**
     * @var array<string, mixed>
     */
    private $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $pageId,
        string $webspaceKey,
        ?string $pageTitle,
        ?string $pageTitleLocale,
        array $context = []
    ) {
        parent::__construct();

        $this->pageId = $pageId;
        $this->webspaceKey = $webspaceKey;
        $this->pageTitle = $pageTitle;
        $this->pageTitleLocale = $pageTitleLocale;
        $this->context = $context;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getEventContext(): array
    {
        return $this->context;
    }

    public function getResourceKey(): string
    {
        return BasePageDocument::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return $this->pageId;
    }

    public function getResourceWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function getResourceTitle(): ?string
    {
        return $this->pageTitle;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->pageTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return PageAdmin::getPageSecurityContext(static::getResourceWebspaceKey());
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return SecurityBehavior::class;
    }
}
