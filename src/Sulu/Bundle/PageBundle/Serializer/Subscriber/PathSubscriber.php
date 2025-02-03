<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;

/**
 * Adds the relative path to the serialization of a document.
 */
class PathSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DocumentInspector $documentInspector,
        private DocumentRegistry $documentRegistry,
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
     * Adds the relative path to the serialization.
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $visitor = $event->getVisitor();
        $document = $event->getObject();

        if (!$document instanceof PathBehavior || !$this->documentRegistry->hasDocument($document)) {
            return;
        }

        $path = $this->documentInspector->getContentPath($document);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'path', $path),
            $path
        );
    }
}
