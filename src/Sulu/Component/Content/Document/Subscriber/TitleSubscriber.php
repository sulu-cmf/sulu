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

use Sulu\Component\DocumentManager\Behavior\Mapping\LocalizedTitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TitleSubscriber implements EventSubscriberInterface
{
    public const PROPERTY_NAME = 'title';

    public function __construct(private PropertyEncoder $propertyEncoder)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // should happen after content is hydrated
            Events::HYDRATE => ['setTitleOnDocument', -10],
            Events::PERSIST => ['setTitleOnNode', 10],
            Events::PUBLISH => ['setTitleOnNode', 10],
        ];
    }

    /**
     * Sets the title on the document from the node.
     */
    public function setTitleOnDocument(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        if ($document instanceof LocalizedTitleBehavior) {
            if (!$event->getLocale()) {
                return;
            }

            $document->setTitle(
                $event->getNode()->getPropertyValueWithDefault(
                    $this->propertyEncoder->localizedContentName(static::PROPERTY_NAME, $event->getLocale()),
                    ''
                )
            );
        } else {
            $document->setTitle(
                $event->getNode()->getPropertyValueWithDefault(
                    $this->propertyEncoder->contentName(static::PROPERTY_NAME),
                    ''
                )
            );
        }
    }

    /**
     * Sets the title on the node from the value in the document.
     *
     * @param PersistEvent $event
     */
    public function setTitleOnNode(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        if ($document instanceof LocalizedTitleBehavior) {
            if (!$event->getLocale()) {
                return;
            }

            $event->getNode()->setProperty(
                $this->propertyEncoder->localizedContentName(static::PROPERTY_NAME, $event->getLocale()),
                $document->getTitle()
            );
        } else {
            $event->getNode()->setProperty(
                $this->propertyEncoder->contentName(static::PROPERTY_NAME),
                $document->getTitle()
            );
        }
    }

    /**
     * Returns true if the given document is supported by this subscriber.
     *
     * @param object $document
     *
     * @return bool
     */
    private function supports($document)
    {
        return $document instanceof TitleBehavior;
    }
}
