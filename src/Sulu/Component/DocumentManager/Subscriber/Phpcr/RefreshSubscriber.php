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

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\RefreshEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RefreshSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private DocumentRegistry $documentRegistry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::REFRESH => 'refreshDocument',
            Events::REMOVE_DRAFT => ['refreshDocumentForDeleteDraft', -512],
        ];
    }

    /**
     * Refreshes the document when the DocumentManager method for it is called.
     */
    public function refreshDocument(RefreshEvent $event)
    {
        $document = $event->getDocument();
        $node = $this->documentRegistry->getNodeForDocument($document);
        $locale = $this->documentRegistry->getLocaleForDocument($document);

        // revert/reload the node to the persisted state
        $node->revert();

        $this->rehydrateDocument($document, $node, $locale);
    }

    /**
     * Refreshes the document after a draft have been removed.
     */
    public function refreshDocumentForDeleteDraft(RemoveDraftEvent $event)
    {
        $this->rehydrateDocument($event->getDocument(), $event->getNode(), $event->getLocale());
    }

    /**
     * Rehydrates the given document from the given node for the given locale.
     *
     * @param object $document
     * @param string $locale
     */
    private function rehydrateDocument($document, NodeInterface $node, $locale)
    {
        $hydrateEvent = new HydrateEvent($node, $locale, ['rehydrate' => true]);
        $hydrateEvent->setDocument($document);
        $this->eventDispatcher->dispatch($hydrateEvent, Events::HYDRATE);
    }
}
