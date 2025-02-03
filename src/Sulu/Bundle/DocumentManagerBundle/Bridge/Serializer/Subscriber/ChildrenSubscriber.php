<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Bridge\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;

/**
 * Adds information about the children to the serialized document.
 */
class ChildrenSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DocumentInspector $documentInspector,
        private DocumentRegistry $documentRegistry
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
     * Adds a flag to indicate if the document has children.
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $document = $event->getObject();

        if (!$document instanceof ChildrenBehavior || !$this->documentRegistry->hasDocument($document)) {
            return;
        }

        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        $hasSub = $this->documentInspector->hasChildren($document);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'hasSub', $hasSub),
            $hasSub
        );
    }
}
