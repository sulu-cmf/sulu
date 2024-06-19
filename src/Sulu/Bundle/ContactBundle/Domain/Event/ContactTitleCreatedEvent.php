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
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;

class ContactTitleCreatedEvent extends DomainEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(
        private ContactTitle $contactTitle,
        private array $payload
    ) {
        parent::__construct();
    }

    public function getContactTitle(): ContactTitle
    {
        return $this->contactTitle;
    }

    public function getEventType(): string
    {
        return 'created';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return ContactTitle::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->contactTitle->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->contactTitle->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::CONTACT_SECURITY_CONTEXT;
    }
}
