<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Domain\Event;

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\WebsiteBundle\Admin\WebsiteAdmin;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;

class AnalyticsRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $webspaceKey;

    public function __construct(int $id, string $webspaceKey)
    {
        parent::__construct();

        $this->id = $id;
        $this->webspaceKey = $webspaceKey;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceId(): string
    {
        return (string) $this->id;
    }

    public function getResourceKey(): string
    {
        return AnalyticsInterface::RESOURCE_KEY;
    }

    public function getResourceSecurityContext(): ?string
    {
        return WebsiteAdmin::getAnalyticsSecurityContext($this->webspaceKey);
    }
}
