<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\Entity\PermissionInheritanceInterface;
use Sulu\Bundle\SecurityBundle\EventListener\PermissionInheritanceSubscriber;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;

class PermissionInheritanceSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<AccessControlManagerInterface>
     */
    private $accessControlManager;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    private $permissionInheritanceSubscriber;

    public function setUp(): void
    {
        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);

        $this->permissionInheritanceSubscriber = new PermissionInheritanceSubscriber(
            $this->accessControlManager->reveal()
        );
    }

    public static function providePostPersist()
    {
        return [
            [5, 1, [1 => ['view' => true]]],
            [8, 3, [2 => ['view' => true, 'delete' => false]]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePostPersist')]
    public function testPostPersist($id, $parentId, $permissions): void
    {
        $entity = $this->prophesize(PermissionInheritanceInterface::class);
        $entity->getId()->willReturn($id);
        $entity->getParentId()->willReturn($parentId);
        $event = $this->createPostPersistEvent($entity->reveal());

        $entityClass = \get_class($entity->reveal());
        $this->accessControlManager->getPermissions($entityClass, $parentId)->willReturn($permissions);

        $this->accessControlManager->setPermissions($entityClass, $id, $permissions)->shouldBeCalled();

        $this->permissionInheritanceSubscriber->postPersist($event);
    }

    public function testPostPersistForOtherEntities(): void
    {
        $entity = new \stdClass();
        $event = $this->createPostPersistEvent($entity);

        $this->accessControlManager->setPermissions(Argument::cetera())->shouldNotBeCalled();

        $this->permissionInheritanceSubscriber->postPersist($event);
    }

    public function testPostPersistWithoutParent(): void
    {
        $entity = $this->prophesize(PermissionInheritanceInterface::class);
        $event = $this->createPostPersistEvent($entity->reveal());

        $this->accessControlManager->setPermissions(Argument::cetera())->shouldNotBeCalled();

        $this->permissionInheritanceSubscriber->postPersist($event);
    }

    /**
     * @return LifecycleEventArgs<EntityManagerInterface>
     */
    private function createPostPersistEvent($entity): LifecycleEventArgs
    {
        $event = new LifecycleEventArgs($entity, $this->entityManager->reveal());

        return $event;
    }
}
