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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface as SuluUserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Listener to set the last login field.
 */
class LastLoginListener implements EventSubscriberInterface
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    /**
     * Subscribe the login events.
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        ];
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        $this->updateLastLogin($user);
    }

    /**
     * Update the users last login.
     *
     * @param UserInterface $user
     */
    protected function updateLastLogin($user): void
    {
        if ($user instanceof SuluUserInterface) {
            $user->setLastLogin(new \DateTime());
            $this->entityManager->flush();
        }
    }
}
