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
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NodeManager;

/**
 * Handle document re-registration upon deserialization.
 *
 * Documents must implement the UuidBehavior.
 *
 * TODO: Remove this class if at all possible. The document should contain all the fields needed by the preview.
 */
class DocumentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DocumentRegistry $registry,
        private NodeManager $nodeManager,
        private MetadataFactoryInterface $metadataFactory
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::POST_DESERIALIZE,
                'method' => 'onPostDeserialize',
            ],
        ];
    }

    public function onPostDeserialize(ObjectEvent $event)
    {
        $document = $event->getObject();

        // only register documents
        if (!$this->metadataFactory->hasMetadataForClass(\get_class($document))) {
            return;
        }

        if (!$document->getUuid()) {
            return;
        }

        try {
            $node = $this->nodeManager->find($document->getUuid());
        } catch (DocumentNotFoundException $e) {
            return;
        }

        if ($this->registry->hasNode($node, $document->getLocale())) {
            $registeredDocument = $this->registry->getDocumentForNode($node, $document->getLocale());
            $this->registry->deregisterDocument($registeredDocument);
        }

        // TODO use the original locale somehow
        if (!$this->registry->hasDocument($document)) {
            $this->registry->registerDocument($document, $node, $document->getLocale());
        }
    }
}
