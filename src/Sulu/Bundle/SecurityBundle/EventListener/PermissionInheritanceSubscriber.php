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

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Sulu\Bundle\SecurityBundle\Entity\PermissionInheritanceInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;

class PermissionInheritanceSubscriber implements EventSubscriber
{
    public function __construct(private AccessControlManagerInterface $accessControlManager)
    {
    }

    public function getSubscribedEvents()
    {
        $events = [
            Events::postPersist,
        ];

        return $events;
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof PermissionInheritanceInterface) {
            return;
        }

        $parentId = $entity->getParentId();
        if (!$parentId) {
            return;
        }

        $entityClass = \get_class($entity);

        $this->accessControlManager->setPermissions(
            $entityClass,
            $entity->getId(),
            $this->accessControlManager->getPermissions($entityClass, $parentId)
        );
    }
}
