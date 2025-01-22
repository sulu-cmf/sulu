<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Search\EventSubscriber;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShadowStructureSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;
    /**
     * @var DocumentInspector
     */
    private $documentInspector;
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        SearchManagerInterface $searchManager
    ) {
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->searchManager = $searchManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::PUBLISH => ['indexPublishedShadowDocuments', -256],
            Events::REMOVE => ['deindexRemovedShadowDocuments', 600],
            Events::UNPUBLISH => ['deindexUnpublishedShadowDocuments', -1024],
            Events::REMOVE_DRAFT => ['indexShadowDocumentsAfterRemoveDraft', -1024],
            Events::REMOVE_LOCALE => ['deindexRemovedLocaleShadowDocuments', -1024],
        ];
    }

    public function indexPublishedShadowDocuments(PublishEvent $event): void
    {
        $this->indexShadowDocuments($event->getDocument());
    }

    public function indexShadowDocumentsAfterRemoveDraft(RemoveDraftEvent $event): void
    {
        $this->indexShadowDocuments($event->getDocument());
    }

    public function deindexRemovedShadowDocuments(RemoveEvent $event): void
    {
        $this->deindexShadowDocuments($event->getDocument());
    }

    public function deindexUnpublishedShadowDocuments(UnpublishEvent $event): void
    {
        $this->deindexShadowDocuments($event->getDocument());
    }

    public function deindexRemovedLocaleShadowDocuments(RemoveLocaleEvent $event): void
    {
        $this->deindexShadowDocuments($event->getDocument());
    }

    /**
     * Index shadow documents in search implementation depending
     * on the publish state.
     *
     * @param object $document
     */
    private function indexShadowDocuments($document): void
    {
        if (!$document instanceof StructureBehavior) {
            return;
        }

        if ($document instanceof SecurityBehavior && !empty($document->getPermissions())) {
            return;
        }

        if (!$document instanceof ShadowLocaleBehavior || !$document instanceof UuidBehavior) {
            return;
        }

        $locales = $this->documentInspector->getShadowLocales($document);
        foreach ($locales as $locale => $shadowLocale) {
            $shadowDocument = $this->documentManager->find($document->getUuid(), $locale);
            $this->searchManager->index($shadowDocument);
        }
    }

    /**
     * Deindex shadow documents in search implementation depending
     * on the publish state.
     *
     * @param object $document
     */
    private function deindexShadowDocuments($document): void
    {
        if (!$document instanceof StructureBehavior) {
            return;
        }

        if (!$document instanceof ShadowLocaleBehavior || !$document instanceof UuidBehavior) {
            return;
        }

        $locales = $this->documentInspector->getShadowLocales($document);
        foreach ($locales as $locale => $shadowLocale) {
            $shadowDocument = $this->documentManager->find($document->getUuid(), $locale);
            $this->searchManager->deindex($shadowDocument);
        }
    }
}
