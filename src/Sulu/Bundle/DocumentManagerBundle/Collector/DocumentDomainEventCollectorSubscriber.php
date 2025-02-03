<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Collector;

use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DocumentDomainEventCollectorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DocumentDomainEventCollectorInterface $documentDomainEventCollector
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::CLEAR => ['onClear', -256],
            Events::FLUSH => ['onFlush', -256],
        ];
    }

    public function onClear(ClearEvent $event): void
    {
        $this->documentDomainEventCollector->clear();
    }

    public function onFlush(FlushEvent $event): void
    {
        $this->documentDomainEventCollector->dispatch();
    }
}
