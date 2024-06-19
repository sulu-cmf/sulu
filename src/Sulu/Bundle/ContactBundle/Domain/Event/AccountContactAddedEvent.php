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
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;

class AccountContactAddedEvent extends DomainEvent
{
    public function __construct(private AccountContact $accountContact)
    {
        parent::__construct();
    }

    public function getAccountContact(): AccountContact
    {
        return $this->accountContact;
    }

    public function getEventType(): string
    {
        return 'contact_added';
    }

    public function getResourceKey(): string
    {
        return AccountInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->accountContact->getAccount()->getId();
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::ACCOUNT_SECURITY_CONTEXT;
    }

    public function getResourceTitle(): ?string
    {
        return $this->accountContact->getAccount()->getName();
    }

    public function getEventContext(): array
    {
        $contact = $this->accountContact->getContact();

        return [
            'contactId' => $contact->getId(),
            'contactName' => $contact->getFirstName() . ' ' . $contact->getLastName(),
        ];
    }
}
