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
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;
use Sulu\Bundle\SecurityBundle\EventListener\ForceTwoFactorSubscriber;

class ForceTwoFactorSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private ObjectProphecy $entityManager;

    private ForceTwoFactorSubscriber $forceTwoFactorSubscriber;

    public function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);

        $this->forceTwoFactorSubscriber = new ForceTwoFactorSubscriber(
            '/^(.*)@sulu\.io$/'
        );
    }

    public function testPreUpdateOtherObject(): void
    {
        $user = new \stdClass();
        $event = $this->createEvent($user);

        $this->forceTwoFactorSubscriber->preUpdate($event);

        $this->entityManager->persist(Argument::cetera())->shouldNotBeCalled();
    }

    public function testPrePersistUserNotMatchingEmail(): void
    {
        $user = new User();
        $user->setEmail('other@localhost');
        $event = $this->createEvent($user);

        $this->forceTwoFactorSubscriber->prePersist($event);

        $this->entityManager->persist(Argument::cetera())->shouldNotBeCalled();
    }

    public function testPrePersistUserMatchingEmail(): void
    {
        $user = new User();
        $user->setEmail('other@sulu.io');
        $event = $this->createEvent($user);

        $this->forceTwoFactorSubscriber->prePersist($event);

        $this->entityManager->persist(Argument::that(function(UserTwoFactor $userTwoFactor) {
            $this->assertSame('email', $userTwoFactor->getMethod());

            return true;
        }))->shouldBeCalled();
    }

    public function testPreUpdateUserMatchingHasTwoFactorEmpty(): void
    {
        $user = new User();
        $user->setEmail('other@sulu.io');
        $userTwoFactor = new UserTwoFactor($user);
        $user->setTwoFactor($userTwoFactor);

        $event = $this->createEvent($user);

        $this->forceTwoFactorSubscriber->preUpdate($event);

        $this->entityManager->persist(Argument::cetera())->shouldNotBeCalled();

        $this->assertSame('email', $userTwoFactor->getMethod());
    }

    public function testPreUpdateUserMatchingHasTwoFactorValue(): void
    {
        $user = new User();
        $user->setEmail('other@sulu.io');
        $userTwoFactor = new UserTwoFactor($user);
        $userTwoFactor->setMethod('other');
        $user->setTwoFactor($userTwoFactor);

        $event = $this->createEvent($user);

        $this->forceTwoFactorSubscriber->preUpdate($event);

        $this->entityManager->persist(Argument::cetera())->shouldNotBeCalled();

        $this->assertSame('other', $userTwoFactor->getMethod());
    }

    /**
     * @return LifecycleEventArgs<EntityManagerInterface>
     */
    private function createEvent(object $object): LifecycleEventArgs
    {
        return new LifecycleEventArgs($object, $this->entityManager->reveal());
    }
}
