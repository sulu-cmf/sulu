<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\SecurityBundle\Admin\SecurityAdmin;
use Sulu\Component\Security\Authentication\RoleInterface;

class RoleCreatedEvent extends DomainEvent
{
    /**
     * @param mixed[] $payload
     */
    public function __construct(private RoleInterface $role, private array $payload)
    {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'created';
    }

    /**
     * @return mixed[]|null
     */
    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return RoleInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->role->getId();
    }

    public function getResourceSecurityContext(): ?string
    {
        return SecurityAdmin::ROLE_SECURITY_CONTEXT;
    }

    public function getRole(): RoleInterface
    {
        return $this->role;
    }

    public function getResourceTitle(): ?string
    {
        return $this->role->getName();
    }
}
