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

use Sulu\Component\Content\Document\Behavior\LastModifiedBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedLastModifiedBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles lastModifiedEnabled and lastModified.
 */
class LastModifiedSubscriber implements EventSubscriberInterface
{
    public const LAST_MODIFIED_PROPERTY_NAME = 'lastModified';

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    public function __construct(
        PropertyEncoder $propertyEncoder,
    ) {
        $this->propertyEncoder = $propertyEncoder;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => 'setLastModifiedOnDocument',
            Events::PERSIST => 'setLastModifiedOnNode',
            Events::PUBLISH => 'setLastModifiedOnNode',
        ];
    }

    /**
     * Set isLastModified/lastModified to document on-hydrate.
     *
     * @return void
     */
    public function setLastModifiedOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof LocalizedLastModifiedBehavior) {
            return;
        }

        $encoding = 'system_localized';
        if ($document instanceof LastModifiedBehavior) {
            $encoding = 'system';
        } elseif (!$event->getLocale()) {
            return;
        }

        $node = $event->getNode();

        /** @var \DateTime|null $lastModified */
        $lastModified = $node->getPropertyValueWithDefault($this->propertyEncoder->encode($encoding, self::LAST_MODIFIED_PROPERTY_NAME, $event->getLocale()), null);
        $document->setLastModified($lastModified);
    }

    /**
     * Set lastModifiedEnabled/lastModified to document on-persist.
     *
     * @return void
     */
    public function setLastModifiedOnNode(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof LocalizedLastModifiedBehavior) {
            return;
        }

        $encoding = 'system_localized';
        if ($document instanceof LastModifiedBehavior) {
            $encoding = 'system';
        } elseif (!$event->getLocale()) {
            return;
        }

        $node = $event->getNode();

        $node->setProperty(
            $this->propertyEncoder->encode($encoding, self::LAST_MODIFIED_PROPERTY_NAME, $event->getLocale()),
            $document->getLastModified(),
        );
    }
}
