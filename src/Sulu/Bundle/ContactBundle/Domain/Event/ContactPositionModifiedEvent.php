<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\Position;

class ContactPositionModifiedEvent extends DomainEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        private Position $position,
        private array $payload
    ) {
        parent::__construct();
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function getEventType(): string
    {
        return 'modified';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return Position::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->position->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->position->getPosition();
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::CONTACT_SECURITY_CONTEXT;
    }
}
