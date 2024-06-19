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
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;

class AccountRemovedEvent extends DomainEvent
{
    public function __construct(
        private int $accountId,
        private string $accountName
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return AccountInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->accountId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->accountName;
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::ACCOUNT_SECURITY_CONTEXT;
    }
}
