<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Appends additional data to content-serialization.
 */
class SecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AccessControlManagerInterface $accessControlManager,
        private ?TokenStorageInterface $tokenStorage = null
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * Adds the permissions for the current user to the serialization.
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $document = $event->getObject();

        if (!($document instanceof SecurityBehavior
            && $document instanceof LocaleBehavior
            && $document instanceof WebspaceBehavior
            && null !== $this->tokenStorage
        )) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        $permissions = $this->accessControlManager->getUserPermissionByArray(
            $document->getLocale(),
            PageAdmin::SECURITY_CONTEXT_PREFIX . $document->getWebspaceName(),
            $document->getPermissions(),
            $user
        );

        $visitor->visitProperty(
            new StaticPropertyMetadata('', '_permissions', $permissions),
            $permissions
        );

        $hasPermissions = !empty($document->getPermissions());
        $visitor->visitProperty(
            new StaticPropertyMetadata('', '_hasPermissions', $hasPermissions),
            $hasPermissions
        );
    }
}
