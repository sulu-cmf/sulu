<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Unit\Application\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Subscriber\SetDomainEventUserSubscriber;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;

class SetDomainEventUserSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<Security|SymfonyCoreSecurity>
     */
    private $security;

    public function setUp(): void
    {
        $this->security = $this->prophesize(
            \class_exists(Security::class)
                ? Security::class
                : SymfonyCoreSecurity::class
        );
    }

    public function testSetDomainEventUser(): void
    {
        $subscriber = $this->createSetDomainEventUserSubscriber();

        $currentUser = $this->prophesize(UserInterface::class);
        $this->security->getUser()->willReturn($currentUser->reveal());

        $event = $this->prophesize(DomainEvent::class);
        $event->getUser()->willReturn(null);
        $event->setUser($currentUser->reveal())->shouldBeCalled();

        $subscriber->setDomainEventUser($event->reveal());
    }

    public function testSetDomainEventUserNoUser(): void
    {
        $subscriber = $this->createSetDomainEventUserSubscriber();

        $this->security->getUser()->willReturn(null);

        $event = $this->prophesize(DomainEvent::class);
        $event->setUser(Argument::cetera())->shouldNotBeCalled();

        $subscriber->setDomainEventUser($event->reveal());
    }

    public function testSetDomainEventUserUserAlreadySet(): void
    {
        $subscriber = new SetDomainEventUserSubscriber(null);

        $event = $this->prophesize(DomainEvent::class);
        $event->setUser(Argument::cetera())->shouldNotBeCalled();

        $subscriber->setDomainEventUser($event->reveal());
    }

    public function testSetDomainEventUserNoSecurity(): void
    {
        $subscriber = $this->createSetDomainEventUserSubscriber();

        $this->security->getUser()->willReturn(null);

        $event = $this->prophesize(DomainEvent::class);
        $event->setUser(Argument::cetera())->shouldNotBeCalled();

        $subscriber->setDomainEventUser($event->reveal());
    }

    private function createSetDomainEventUserSubscriber(): SetDomainEventUserSubscriber
    {
        return new SetDomainEventUserSubscriber(
            $this->security->reveal()
        );
    }
}
