<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\RouteBundle\PageTree\PageTreeUpdaterInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles relation between documents and pages.
 */
class PageTreeRouteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected DocumentManagerInterface $documentManager,
        protected PropertyEncoder $propertyEncoder,
        protected DocumentInspector $documentInspector,
        protected StructureMetadataFactoryInterface $metadataFactory,
        protected SessionInterface $liveSession,
        protected PageTreeUpdaterInterface $routeUpdater
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // should be called before the live resource-segment will be updated
            Events::PUBLISH => ['handlePublish', 10],
            // should be called after the live resource-segment was be updated
            Events::MOVE => ['handleMove', -1000],
        ];
    }

    /**
     * Update route-paths of documents which are linked to the given page-document.
     */
    public function handlePublish(AbstractMappingEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof PageDocument || !$this->hasChangedResourceSegment($document)) {
            return;
        }

        $this->routeUpdater->update($document);
    }

    /**
     * Update route-paths of documents which are linked to the given page-document.
     */
    public function handleMove(MoveEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof PageDocument) {
            return;
        }

        foreach ($this->documentInspector->getLocales($document) as $locale) {
            /** @var BasePageDocument $localizedDocument */
            $localizedDocument = $this->documentManager->find($document->getUuid(), $locale);

            $this->routeUpdater->update($localizedDocument);
        }
    }

    /**
     * Returns true if the resource-segment was changed in the draft page.
     */
    private function hasChangedResourceSegment(PageDocument $document): bool
    {
        $metadata = $this->metadataFactory->getStructureMetadata('page', $document->getStructureType());

        $urlProperty = $metadata->getPropertyByTagName('sulu.rlp');
        $urlPropertyName = $this->propertyEncoder->localizedContentName(
            $urlProperty->getName(),
            $document->getLocale()
        );

        $liveNode = $this->getLiveNode($document);
        $url = $liveNode->getPropertyValueWithDefault($urlPropertyName, null);

        return $url && $url !== $document->getResourceSegment();
    }

    /**
     * Returns the live node for given document.
     */
    private function getLiveNode(PathBehavior $document): NodeInterface
    {
        return $this->liveSession->getNode($document->getPath());
    }
}
