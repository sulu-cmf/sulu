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
use Sulu\Bundle\SecurityBundle\Entity\PermissionInheritanceInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;

class PermissionInheritanceSubscriber
{
    public function __construct(private AccessControlManagerInterface $accessControlManager)
    {
    }

    /**
     * @param LifecycleEventArgs<\Doctrine\Persistence\ObjectManager> $event
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();

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
