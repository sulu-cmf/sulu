<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Document\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

class SecuritySubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private $tokenStorage;

    /**
     * @var SecuritySubscriber
     */
    private $securitySubscriber;

    public function setUp(): void
    {
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->securitySubscriber = new SecuritySubscriber($this->tokenStorage->reveal());
    }

    public function testSetDefaultUser(): void
    {
        $event = $this->prophesize(ConfigureOptionsEvent::class);

        $optionsResolver = $this->prophesize(OptionsResolver::class);
        $event->getOptions()->willReturn($optionsResolver->reveal());

        $token = $this->prophesize(TokenInterface::class);
        $this->tokenStorage->getToken()->willReturn($token->reveal());

        $user = $this->prophesize(UserInterface::class);
        $user->getId()->willReturn(2);
        $token->getUser()->willReturn($user->reveal());

        $optionsResolver->setDefault('user', null)->shouldBeCalled()->willReturn($optionsResolver->reveal());
        $optionsResolver->setDefault('user', 2)->shouldBeCalled()->willReturn($optionsResolver->reveal());

        $this->securitySubscriber->setDefaultUser($event->reveal());
    }

    public function testSetDefaultUserWithNullToken(): void
    {
        $event = $this->prophesize(ConfigureOptionsEvent::class);

        $optionsResolver = $this->prophesize(OptionsResolver::class);
        $event->getOptions()->willReturn($optionsResolver->reveal());

        $this->tokenStorage->getToken()->willReturn(null);

        $optionsResolver->setDefault('user', null)->shouldBeCalled()->willReturn($optionsResolver->reveal());

        $this->securitySubscriber->setDefaultUser($event->reveal());
    }

    public function testSetDefaultUserWithNonSuluUser(): void
    {
        $event = $this->prophesize(ConfigureOptionsEvent::class);

        $optionsResolver = $this->prophesize(OptionsResolver::class);
        $event->getOptions()->willReturn($optionsResolver->reveal());

        $token = $this->prophesize(TokenInterface::class);
        $this->tokenStorage->getToken()->willReturn($token->reveal());

        $token->getUser()->willReturn(new InMemoryUser('test', 'test'));

        $optionsResolver->setDefault('user', null)->shouldBeCalled()->willReturn($optionsResolver->reveal());

        $this->securitySubscriber->setDefaultUser($event->reveal());
    }
}
