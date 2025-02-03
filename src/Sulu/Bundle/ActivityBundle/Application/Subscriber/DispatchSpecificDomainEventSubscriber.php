<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Application\Subscriber;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DispatchSpecificDomainEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DomainEvent::class => ['dispatchDomainEventWithSpecificEventName', 0],
        ];
    }

    public function dispatchDomainEventWithSpecificEventName(DomainEvent $event): void
    {
        // the DomainEventDispatcher service uses DomainEvent::class as event-name when dispatching events. this
        // allows to register listeners that listen to all domain events.
        // this subscriber additionally dispatches the event with a specific event-name such as TagRemovedEvent::class
        // to allow for registering listeners for a specific type of event.
        $this->eventDispatcher->dispatch($event);
    }
}
