<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class PreviewLinkRevokedEvent extends DomainEvent
{
    public function __construct(
        private string $resourceKey,
        private string $resourceId,
        private string $link,
        private ?string $securityContext,
    ) {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'preview_link_revoked';
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->link;
    }

    public function getResourceTitleLocale(): ?string
    {
        return null;
    }

    public function getResourceSecurityContext(): ?string
    {
        return $this->securityContext;
    }
}
