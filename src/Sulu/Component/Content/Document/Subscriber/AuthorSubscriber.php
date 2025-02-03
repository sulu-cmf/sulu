<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\AuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles author and authored.
 */
class AuthorSubscriber implements EventSubscriberInterface
{
    public const AUTHORED_PROPERTY_NAME = 'authored';

    public const AUTHOR_PROPERTY_NAME = 'author';

    public function __construct(
        private PropertyEncoder $propertyEncoder,
        private UserRepositoryInterface $userRepository,
        private MetadataFactoryInterface $metadataFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::HYDRATE => 'setAuthorOnDocument',
            Events::PERSIST => 'setAuthorOnNode',
            Events::PUBLISH => 'setAuthorOnNode',
        ];
    }

    /**
     * Set author/authored to document on-hydrate.
     */
    public function setAuthorOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof LocalizedAuthorBehavior) {
            return;
        }

        $encoding = 'system_localized';
        if ($document instanceof AuthorBehavior) {
            $encoding = 'system';
        } elseif (!$event->getLocale()) {
            return;
        }

        $node = $event->getNode();
        $document->setAuthored(
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, self::AUTHORED_PROPERTY_NAME, $event->getLocale()),
                null
            )
        );
        $document->setAuthor(
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, self::AUTHOR_PROPERTY_NAME, $event->getLocale()),
                null
            )
        );
    }

    /**
     * Set author/authored to document on-persist.
     */
    public function setAuthorOnNode(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof LocalizedAuthorBehavior) {
            return;
        }

        // Set default value if authored is not set.
        if (null === $document->getAuthored()) {
            $document->setAuthored(new \DateTime());
        }

        $metadata = $this->metadataFactory->getMetadataForClass(\get_class($document));
        if ($metadata->getSetDefaultAuthor()) {
            $this->setDefaultAuthor($document);
        }

        $encoding = 'system_localized';
        if ($document instanceof AuthorBehavior) {
            $encoding = 'system';
        } elseif (!$event->getLocale()) {
            return;
        }

        $node = $event->getNode();
        $node->setProperty(
            $this->propertyEncoder->encode($encoding, self::AUTHORED_PROPERTY_NAME, $event->getLocale()),
            $document->getAuthored()
        );
        $node->setProperty(
            $this->propertyEncoder->encode($encoding, self::AUTHOR_PROPERTY_NAME, $event->getLocale()),
            $document->getAuthor()
        );
    }

    /**
     * Set default author (if not set) to given document.
     */
    private function setDefaultAuthor(LocalizedAuthorBehavior $document)
    {
        $creator = $document->getCreator();

        if ($document->getAuthor() || !$creator) {
            return;
        }

        $user = $this->userRepository->findUserById($creator);
        if ($user && $user->getContact()) {
            $document->setAuthor($user->getContact()->getId());
        }
    }
}
