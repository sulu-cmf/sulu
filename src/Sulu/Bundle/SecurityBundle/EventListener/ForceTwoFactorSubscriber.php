<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;

/**
 * @internal
 */
class ForceTwoFactorSubscriber
{
    public function __construct(private string $twoFactorForcePattern)
    {
    }

    /**
     * @param LifecycleEventArgs<\Doctrine\Persistence\ObjectManager> $event
     */
    public function preUpdate(LifecycleEventArgs $event): void
    {
        $this->handleTwoFactorForce($event);
    }

    /**
     * @param LifecycleEventArgs<\Doctrine\Persistence\ObjectManager> $event
     */
    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->handleTwoFactorForce($event);
    }

    /**
     * @param LifecycleEventArgs<\Doctrine\Persistence\ObjectManager> $event
     */
    private function handleTwoFactorForce(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();

        if (!$entity instanceof User) {
            return;
        }

        if (!\preg_match($this->twoFactorForcePattern, $entity->getEmail() ?: '')) {
            return;
        }

        $twoFactor = $entity->getTwoFactor();

        if (!$twoFactor) {
            $twoFactor = new UserTwoFactor($entity);
            $event->getObjectManager()->persist($twoFactor);
        }

        if (!$twoFactor->getMethod()) {
            $twoFactor->setMethod('email');
        }
    }
}
