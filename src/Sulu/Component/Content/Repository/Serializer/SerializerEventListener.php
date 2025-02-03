<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Appends additional data to content-serialization.
 */
class SerializerEventListener implements EventSubscriberInterface
{
    public function __construct(
        private AccessControlManagerInterface $accessControlManager,
        private ?TokenStorageInterface $tokenStorage = null,
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
     * Add data for serialization of content objects.
     */
    public function onPostSerialize(ObjectEvent $event): void
    {
        /** @var Content $content */
        $content = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        if (!($content instanceof Content)) {
            return;
        }

        foreach ($content->getData() as $key => $value) {
            $visitor->visitProperty(
                new StaticPropertyMetadata('', $key, $value),
                $value
            );
        }

        $publishedState = WorkflowStage::PUBLISHED === $content->getWorkflowStage();
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'publishedState', $publishedState),
            $publishedState
        );

        $linked = null;
        if (RedirectType::EXTERNAL === $content->getNodeType()) {
            $linked = 'external';
        } elseif (RedirectType::INTERNAL === $content->getNodeType()) {
            $linked = 'internal';
        }

        if ($linked) {
            $visitor->visitProperty(
                new StaticPropertyMetadata('', 'linked', $linked),
                $linked
            );
        }

        if (null !== $content->getLocalizationType()) {
            $localizationType = $content->getLocalizationType();
            $visitor->visitProperty(
                new StaticPropertyMetadata('', 'type', $localizationType),
                $localizationType->toArray()
            );

            if (LocalizationState::GHOST === $localizationType->getName()) {
                $ghostLocale = $localizationType->getValue();
                $visitor->visitProperty(
                    new StaticPropertyMetadata('', 'ghostLocale', $ghostLocale),
                    $ghostLocale
                );
            }

            if (LocalizationState::SHADOW === $localizationType->getName()) {
                $shadowLocale = $localizationType->getValue();
                $visitor->visitProperty(
                    new StaticPropertyMetadata('', 'shadowLocale', $shadowLocale),
                    $shadowLocale
                );
            }
        }

        if (!$this->tokenStorage) {
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

        $permissions = $this->accessControlManager->getUserPermissionByArray(
            $content->getLocale(),
            PageAdmin::SECURITY_CONTEXT_PREFIX . $content->getWebspaceKey(),
            $content->getPermissions(),
            $user
        );
        $visitor->visitProperty(
            new StaticPropertyMetadata('', '_permissions', $permissions),
            $permissions
        );

        $hasPermissions = !empty($content->getPermissions());
        $visitor->visitProperty(
            new StaticPropertyMetadata('', '_hasPermissions', $hasPermissions),
            $hasPermissions
        );
    }
}
