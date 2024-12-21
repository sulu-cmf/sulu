<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;

/**
 * Class AccountListener.
 */
class AccountListener
{
    /**
     * @param LifecycleEventArgs<\Doctrine\Persistence\ObjectManager> $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof AccountInterface) {
            $entityManager = $args->getObjectManager();
            // after saving account check if number is set, else set a new one
            if (null === $entity->getNumber()) {
                $entity->setNumber(\sprintf('%05d', $entity->getId()));
                $entityManager->flush();
            }
        }
    }
}
