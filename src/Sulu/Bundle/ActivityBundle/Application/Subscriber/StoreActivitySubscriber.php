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
use Sulu\Bundle\ActivityBundle\Domain\Repository\ActivityRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StoreActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ActivityRepositoryInterface $activityRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DomainEvent::class => ['storeActivity', -256],
        ];
    }

    public function storeActivity(DomainEvent $event): void
    {
        $activity = $this->activityRepository->createFromDomainEvent($event);
        $this->activityRepository->addAndCommit($activity);
    }
}
