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
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;

class SetDomainEventUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security|SymfonyCoreSecurity|null $security
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DomainEvent::class => ['setDomainEventUser', 256],
        ];
    }

    public function setDomainEventUser(DomainEvent $event): void
    {
        if (!$this->security) {
            return;
        }

        $currentUser = $this->security->getUser();

        if ($currentUser instanceof UserInterface && !$event->getUser()) {
            $event->setUser($currentUser);
        }
    }
}
