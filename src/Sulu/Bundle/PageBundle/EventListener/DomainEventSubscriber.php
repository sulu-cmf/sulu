<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\EventListener;

use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Document\RouteDocument;
use Sulu\Bundle\PageBundle\Domain\Event\PageChildrenReorderedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageCopiedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageCreatedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageDraftRemovedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageModifiedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageMovedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PagePublishedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageRemovedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageRouteRemovedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageTranslationAddedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageTranslationCopiedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageTranslationRemovedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageUnpublishedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageVersionRestoredEvent;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\Content\Document\Subscriber\StructureSubscriber;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\CopyLocaleEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
class DomainEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var array<array<string, mixed>>
     */
    private $eventsToBeDispatchedAfterFlush = [];

    /**
     * @var array<string, bool>
     */
    private $persistEventsWithNewDocument = [];

    /**
     * @var array<string, bool>
     */
    private $persistEventsWithNewLocale = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private $moveEventsWithPreviousParentDocument = [];

    public function __construct(
        private DocumentDomainEventCollectorInterface $domainEventCollector,
        private DocumentManagerInterface $documentManager,
        private PropertyEncoder $propertyEncoder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::CONFIGURE_OPTIONS => 'handleConfigureOptions',
            Events::FLUSH => 'handleFlush',
            Events::PERSIST => [
                ['handlePrePersist', 479], // Priority needs to be lower than AutoNameSubscriber::handlePersist (480)
                ['handlePersist', -10000],
            ],
            Events::REMOVE => [
                ['handleRemove', -10000],
                ['handleRouteRemove', -10000],
            ],
            Events::REMOVE_LOCALE => ['handleRemoveLocale', -10000],
            Events::COPY_LOCALE => ['handleCopyLocale', -10000],
            Events::COPY => ['handleCopy', -10000],
            Events::MOVE => [
                ['handlePreMove', 10000], // Priority needs to be higher than ParentSubscriber::handleMove (0)
                ['handleMove', -10000],
            ],
            Events::PUBLISH => ['handlePublish', -10000],
            Events::UNPUBLISH => ['handleUnpublish', -10000],
            Events::REMOVE_DRAFT => ['handleRemoveDraft', -10000],
            Events::RESTORE => ['handleRestore', -10000],
            Events::REORDER => ['handleReorder', -10000],
        ];
    }

    public function handleConfigureOptions(ConfigureOptionsEvent $event): void
    {
        $options = $event->getOptions();
        $options->setDefaults(
            [
                'omit_modified_domain_event' => false,
            ]
        );
        $options->setAllowedTypes('omit_modified_domain_event', 'bool');
    }

    public function handleFlush(FlushEvent $event): void
    {
        $eventsToBeDispatched = $this->eventsToBeDispatchedAfterFlush;

        if (0 === \count($eventsToBeDispatched)) {
            return;
        }

        $this->eventsToBeDispatchedAfterFlush = [];

        foreach ($eventsToBeDispatched as $eventConfig) {
            $type = $eventConfig['type'] ?? null;
            $options = $eventConfig['options'] ?? [];

            switch ($type) {
                case PageCopiedEvent::class:
                    $pagePath = $options['pagePath'] ?? null;
                    Assert::notNull($pagePath);
                    $locale = $options['locale'] ?? null;
                    Assert::notNull($locale);
                    $sourcePageId = $options['sourcePageId'] ?? null;
                    Assert::notNull($sourcePageId);
                    $sourcePageWebspaceKey = $options['sourcePageWebspaceKey'] ?? null;
                    Assert::notNull($sourcePageWebspaceKey);
                    $sourcePageTitle = $options['sourcePageTitle'] ?? null;
                    Assert::notNull($sourcePageTitle);

                    /** @var PageDocument $document */
                    $document = $this->documentManager->find($pagePath, $locale);

                    $this->domainEventCollector->collect(
                        new PageCopiedEvent(
                            $document,
                            $sourcePageId,
                            $sourcePageWebspaceKey,
                            $sourcePageTitle,
                            $locale
                        )
                    );

                    $this->documentManager->flush();

                    break;
            }
        }
    }

    public function handlePrePersist(PersistEvent $event): void
    {
        if (!$event->hasNode()) {
            return;
        }

        /** @var string|null $locale */
        $locale = $event->getLocale();
        $node = $event->getNode();

        if (null === $locale) {
            return;
        }

        $eventHash = \spl_object_hash($event);

        if ($this->isNewNode($node)) {
            $this->persistEventsWithNewDocument[$eventHash] = true;

            return;
        }

        if ($this->isNewTranslation($node, $locale)) {
            $this->persistEventsWithNewLocale[$eventHash] = true;
        }
    }

    public function handlePersist(PersistEvent $event): void
    {
        if (true === $event->getOption('omit_modified_domain_event')) {
            return;
        }

        /** @var string|null $locale */
        $locale = $event->getLocale();
        $document = $event->getDocument();

        if (null === $locale || !$document instanceof BasePageDocument) {
            return;
        }

        $payload = $this->getPayloadFromPageDocument($document);

        $eventHash = \spl_object_hash($event);

        if (true === ($this->persistEventsWithNewDocument[$eventHash] ?? null)) {
            unset($this->persistEventsWithNewDocument[$eventHash]);

            $this->domainEventCollector->collect(
                new PageCreatedEvent($document, $locale, $payload)
            );

            return;
        }

        if (true === ($this->persistEventsWithNewLocale[$eventHash] ?? null)) {
            unset($this->persistEventsWithNewLocale[$eventHash]);

            $this->domainEventCollector->collect(
                new PageTranslationAddedEvent($document, $locale, $payload)
            );

            return;
        }

        $this->domainEventCollector->collect(
            new PageModifiedEvent($document, $locale, $payload)
        );
    }

    public function handleRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageRemovedEvent(
                $document->getUuid(),
                $document->getWebspaceName(),
                $document->getTitle(),
                $document->getLocale(),
                [
                    'url' => $document->getResourceSegment(),
                ]
            )
        );
    }

    public function handleRouteRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof RouteDocument) {
            return;
        }

        $basePageDocument = $document->getTargetDocument();
        while ($basePageDocument instanceof RouteDocument) {
            $basePageDocument = $basePageDocument->getTargetDocument();
        }

        if (!$basePageDocument instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageRouteRemovedEvent(
                $basePageDocument->getUuid(),
                $basePageDocument->getWebspaceName(),
                $basePageDocument->getTitle(),
                $basePageDocument->getLocale(),
                $document->getPath()
            )
        );
    }

    public function handleRemoveLocale(RemoveLocaleEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageTranslationRemovedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleCopyLocale(CopyLocaleEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $destDocument = $event->getDestDocument();

        if (!$destDocument instanceof BasePageDocument) {
            return;
        }

        $destLocale = $event->getDestLocale();
        $sourceLocale = $event->getLocale();
        $payload = $this->getPayloadFromPageDocument($destDocument);

        $this->domainEventCollector->collect(
            new PageTranslationCopiedEvent(
                $destDocument,
                $destLocale,
                $sourceLocale,
                $payload
            )
        );
    }

    public function handleCopy(CopyEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof PageDocument) {
            return;
        }

        $this->eventsToBeDispatchedAfterFlush[] = [
            'type' => PageCopiedEvent::class,
            'options' => [
                'pagePath' => $event->getCopiedPath(),
                'locale' => $document->getLocale(),
                'sourcePageId' => $document->getUuid(),
                'sourcePageWebspaceKey' => $document->getWebspaceName(),
                'sourcePageTitle' => $document->getTitle(),
            ],
        ];
    }

    public function handlePreMove(MoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof PageDocument) {
            return;
        }

        $eventHash = \spl_object_hash($event);

        /** @var BasePageDocument $parent */
        $parent = $document->getParent();

        $this->moveEventsWithPreviousParentDocument[$eventHash] = [
            'parentId' => $parent->getUuid(),
            'parentWebspaceKey' => $parent->getWebspaceName(),
            'parentTitle' => $parent->getTitle(),
            'parentTitleLocale' => $parent->getLocale(),
        ];
    }

    public function handleMove(MoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof PageDocument) {
            return;
        }

        $eventHash = \spl_object_hash($event);
        $previousParentData = $this->moveEventsWithPreviousParentDocument[$eventHash] ?? [];
        unset($this->moveEventsWithPreviousParentDocument[$eventHash]);

        $this->domainEventCollector->collect(
            new PageMovedEvent(
                $document,
                $previousParentData['parentId'] ?? null,
                $previousParentData['parentWebspaceKey'] ?? null,
                $previousParentData['parentTitle'] ?? null,
                $previousParentData['parentTitleLocale'] ?? null
            )
        );
    }

    public function handlePublish(PublishEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PagePublishedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleUnpublish(UnpublishEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageUnpublishedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleRemoveDraft(RemoveDraftEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageDraftRemovedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleRestore(RestoreEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();
        $version = $event->getVersion();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageVersionRestoredEvent(
                $document,
                $locale,
                $version
            )
        );
    }

    public function handleReorder(ReorderEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof PageDocument) {
            return;
        }

        $parentDocument = $document->getParent();

        if (!$parentDocument instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageChildrenReorderedEvent(
                $parentDocument
            )
        );
    }

    /**
     * @return mixed[]
     */
    private function getPayloadFromPageDocument(BasePageDocument $pageDocument): array
    {
        $data = $pageDocument->getStructure()->toArray();

        /** @var ExtensionContainer|mixed[] $extensionData */
        $extensionData = $pageDocument->getExtensionsData();

        if ($extensionData instanceof ExtensionContainer) {
            $extensionData = $extensionData->toArray();
        }

        $data['ext'] = $extensionData;

        return $data;
    }

    /**
     * @param NodeInterface<mixed> $node
     *
     * @see SecuritySubscriber::handlePersistCreate()
     */
    private function isNewNode(NodeInterface $node): bool
    {
        /** @var \Countable $properties */
        $properties = $node->getProperties(
            $this->propertyEncoder->encode(
                'system_localized',
                StructureSubscriber::STRUCTURE_TYPE_FIELD,
                '*'
            )
        );

        return 0 === \count($properties);
    }

    /**
     * @param NodeInterface<mixed> $node
     */
    private function isNewTranslation(NodeInterface $node, string $locale): bool
    {
        /** @var \Countable $localizedProperties */
        $localizedProperties = $node->getProperties(
            $this->propertyEncoder->localizedContentName('*', $locale)
        );

        return 0 === \count($localizedProperties);
    }
}
