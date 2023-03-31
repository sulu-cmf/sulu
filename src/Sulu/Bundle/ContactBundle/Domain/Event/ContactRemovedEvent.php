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
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;

class ContactRemovedEvent extends DomainEvent
{
    private int $contactId;

    private string $contactName;

    public function __construct(
        int $contactId,
        string $contactName
    ) {
        parent::__construct();

        $this->contactId = $contactId;
        $this->contactName = $contactName;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return ContactInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->contactId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->contactName;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::CONTACT_SECURITY_CONTEXT;
    }
}
