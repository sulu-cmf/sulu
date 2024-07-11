<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class TagMergedEvent extends DomainEvent
{
    public function __construct(
        private TagInterface $destinationTag,
        private int $sourceTagId,
        private string $sourceTagName
    ) {
        parent::__construct();
    }

    public function getDestinationTag(): TagInterface
    {
        return $this->destinationTag;
    }

    public function getEventType(): string
    {
        return 'merged';
    }

    public function getEventContext(): array
    {
        return [
            'sourceTagId' => $this->sourceTagId,
            'sourceTagName' => $this->sourceTagName,
        ];
    }

    public function getResourceKey(): string
    {
        return TagInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->destinationTag->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->destinationTag->getName();
    }

    public function getResourceSecurityContext(): ?string
    {
        return TagAdmin::SECURITY_CONTEXT;
    }
}
