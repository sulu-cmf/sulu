<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Domain\Event;

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;

class CollectionRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $collectionId;

    /**
     * @var string|null
     */
    private $collectionTitle;

    /**
     * @var string|null
     */
    private $collectionTitleLocale;

    public function __construct(
        int $collectionId,
        ?string $collectionTitle,
        ?string $collectionTitleLocale
    ) {
        parent::__construct();

        $this->collectionId = $collectionId;
        $this->collectionTitle = $collectionTitle;
        $this->collectionTitleLocale = $collectionTitleLocale;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getResourceKey(): string
    {
        return CollectionInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->collectionId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->collectionTitle;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->collectionTitleLocale;
    }

    public function getResourceSecurityContext(): ?string
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return Collection::class;
    }
}
