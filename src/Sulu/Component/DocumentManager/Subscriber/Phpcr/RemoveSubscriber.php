<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Phpcr;

use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remove subscriber.
 */
class RemoveSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DocumentRegistry $documentRegistry,
        private NodeManager $nodeManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::REMOVE => ['handleRemove', 500],
        ];
    }

    /**
     * Remove the given documents node from PHPCR session and optionally
     * remove any references to the node.
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        $node = $this->documentRegistry->getNodeForDocument($document);

        $node->remove();
    }
}
