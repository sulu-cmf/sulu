<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Document\Subscriber;

use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Responsible for integrating the security part of Sulu to the DocumentManager.
 */
class SecuritySubscriber implements EventSubscriberInterface
{
    public const USER_OPTION = 'user';

    public function __construct(private ?TokenStorageInterface $tokenStorage = null)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::CONFIGURE_OPTIONS => 'setDefaultUser',
        ];
    }

    /**
     * Sets the default user from the session.
     */
    public function setDefaultUser(ConfigureOptionsEvent $event)
    {
        $optionsResolver = $event->getOptions();

        $optionsResolver->setDefault(static::USER_OPTION, null);

        if (null === $this->tokenStorage) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token || $token instanceof AnonymousToken) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $optionsResolver->setDefault(static::USER_OPTION, $user->getId());
    }
}
