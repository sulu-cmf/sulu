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
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeHelperInterface;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class aggregates some basic repository operations.
 *
 * NOTE: If any of these methods need to become more complicated, and
 *       the changes cannot be done by implementing ANOTHER subscriber, then
 *       the individual operations should be broken out into individual subscribers.
 */
class GeneralSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DocumentRegistry $documentRegistry,
        private NodeManager $nodeManager,
        private NodeHelperInterface $nodeHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::MOVE => ['handleMove', 400],
            Events::COPY => ['handleCopy', 400],
            Events::CLEAR => ['handleClear', 500],
            Events::FLUSH => ['handleFlush', 500],
        ];
    }

    public function handleMove(MoveEvent $event)
    {
        $document = $event->getDocument();
        $node = $this->documentRegistry->getNodeForDocument($document);
        $this->nodeHelper->move($node, $event->getDestId(), $event->getDestName());
    }

    public function handleCopy(CopyEvent $event)
    {
        $document = $event->getDocument();
        $node = $this->documentRegistry->getNodeForDocument($document);
        $newPath = $this->nodeHelper->copy($node, $event->getDestId(), $event->getDestName());
        $event->setCopiedNode($this->nodeManager->find($newPath));
    }

    public function handleClear(ClearEvent $event)
    {
        $this->nodeManager->clear();
    }

    public function handleFlush(FlushEvent $event)
    {
        $this->nodeManager->save();
    }
}
