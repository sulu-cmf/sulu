<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Util\SortUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Indexes article and generate route on persist and removes it from index and routing on delete.
 */
class ArticleSubscriber implements EventSubscriberInterface
{
    public const PAGES_PROPERTY = 'suluPages';

    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var IndexerInterface
     */
    private $liveIndexer;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var array
     */
    private $documents = [];

    /**
     * @var array
     */
    private $liveDocuments = [];

    /**
     * @var array
     */
    private $children = [];

    public function __construct(
        IndexerInterface $indexer,
        IndexerInterface $liveIndexer,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        PropertyEncoder $propertyEncoder
    ) {
        $this->indexer = $indexer;
        $this->liveIndexer = $liveIndexer;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->propertyEncoder = $propertyEncoder;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => [
                ['hydratePageData', -2000],
            ],
            Events::PERSIST => [
                ['handleScheduleIndex', -500],
                ['handleChildrenPersist', 0],
                ['persistPageData', -2000],
            ],
            Events::REMOVE => [
                ['handleRemove', -500],
                ['handleRemoveLive', -500],
                ['handleRemovePage', -500],
            ],
            Events::PUBLISH => [
                ['handleScheduleIndexLive', 0],
                ['handleScheduleIndex', 0],
                ['synchronizeChildren', 0],
                ['publishChildren', 0],
                ['persistPageData', -2000],
            ],
            Events::REORDER => [['persistPageDataOnReorder', -2000]],
            Events::UNPUBLISH => 'handleUnpublish',
            Events::REMOVE_DRAFT => [['handleScheduleIndex', -1024], ['removeDraftChildren', 0]],
            Events::FLUSH => [['handleFlush', -2048], ['handleFlushLive', -2048]],
            Events::COPY => ['handleCopy'],
            Events::METADATA_LOAD => ['handleMetadataLoad'],
            Events::REMOVE_LOCALE => [['handleRemoveLocale', -500], ['handleRemoveLocaleLive', -500]],
        ];
    }

    /**
     * Schedule article document for index.
     */
    public function handleScheduleIndex(AbstractMappingEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            if (!$document instanceof ArticlePageDocument) {
                return;
            }

            $document = $document->getParent();
        }

        $this->documents[$document->getUuid() . '_' . $document->getLocale()] = [
            'uuid' => $document->getUuid(),
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Schedule article document for live index.
     */
    public function handleScheduleIndexLive(AbstractMappingEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            if (!$document instanceof ArticlePageDocument) {
                return;
            }

            $document = $document->getParent();
        }

        $this->liveDocuments[$document->getUuid() . '_' . $document->getLocale()] = [
            'uuid' => $document->getUuid(),
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Syncs children between live and draft.
     */
    public function synchronizeChildren(PublishEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $liveNode = $event->getNode();
        $draftNode = $this->documentInspector->getNode($document);

        $liveChildren = $this->getChildren($liveNode);
        $draftChildren = $this->getChildren($draftNode);
        $removedChildrenIds = \array_diff(\array_keys($liveChildren), \array_keys($draftChildren));

        foreach ($removedChildrenIds as $removedChildrenId) {
            $liveChildren[$removedChildrenId]->remove();
        }
    }

    /**
     * Returns children of given node.
     *
     * @return NodeInterface[]
     */
    private function getChildren(NodeInterface $node): array
    {
        $result = [];
        foreach ($node->getNodes() as $child) {
            $result[$child->getIdentifier()] = $child;
        }

        return $result;
    }

    /**
     * Publish pages when article will be published.
     */
    public function publishChildren(PublishEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $children = \iterator_to_array($document->getChildren());
        foreach ($children as $child) {
            if (LocalizationState::GHOST !== $this->documentInspector->getLocalizationState($child)) {
                $this->documentManager->publish($child, $event->getLocale());
            }
        }
    }

    /**
     * Persist page-data for reordering children.
     */
    public function persistPageDataOnReorder(ReorderEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $document = $document->getParent();
        $node = $this->documentInspector->getNode($document);

        $this->setPageData($document, $node, $document->getLocale());

        $document->setWorkflowStage(WorkflowStage::TEST);
        $this->documentManager->persist($document, $this->documentInspector->getLocale($document));

        $this->documents[$document->getUuid() . '_' . $document->getLocale()] = [
            'uuid' => $document->getUuid(),
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Persist page-data.
     *
     * @param PersistEvent|PublishEvent $event
     */
    public function persistPageData($event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->setPageData($document, $event->getNode(), $event->getLocale());
    }

    /**
     * Set page-data for given document on given node.
     */
    private function setPageData(ArticleDocument $document, NodeInterface $node, string $locale): void
    {
        $pages = [
            [
                'uuid' => $document->getUuid(),
                'title' => $document->getPageTitle() ?: $document->getTitle(),
                'routePath' => $document->getRoutePath(),
                'pageNumber' => $document->getPageNumber(),
            ],
        ];

        foreach ($document->getChildren() as $child) {
            if ($child instanceof ArticlePageDocument
                && LocalizationState::GHOST !== $this->documentInspector->getLocalizationState($child)
            ) {
                $pages[] = [
                    'uuid' => $child->getUuid(),
                    'title' => $child->getPageTitle(),
                    'routePath' => $child->getRoutePath(),
                    'pageNumber' => $child->getPageNumber(),
                ];
            }
        }

        $pages = SortUtils::multisort($pages, '[pageNumber]');

        $document->setPages($pages);
        $node->setProperty(
            $this->propertyEncoder->localizedSystemName(self::PAGES_PROPERTY, $locale),
            \json_encode($pages)
        );
    }

    /**
     * Hydrate page-data.
     */
    public function hydratePageData(HydrateEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $pages = $event->getNode()->getPropertyValueWithDefault(
            $this->propertyEncoder->localizedSystemName(self::PAGES_PROPERTY, $document->getOriginalLocale()),
            \json_encode([])
        );
        $pages = \json_decode($pages, true);

        if (LocalizationState::SHADOW === $this->documentInspector->getLocalizationState($document)) {
            $pages = $this->loadPageDataForShadow($event->getNode(), $document, $pages);
        }

        $document->setPages($pages);
    }

    /**
     * Load `routePath` from current locale into `pageData`.
     */
    private function loadPageDataForShadow(NodeInterface $node, ArticleDocument $document, array $originalPages): array
    {
        $pages = $node->getPropertyValueWithDefault(
            $this->propertyEncoder->localizedSystemName(self::PAGES_PROPERTY, $document->getLocale()),
            \json_encode([])
        );
        $pages = \json_decode($pages, true);

        for ($i = 0; $i < \count($originalPages); ++$i) {
            $pages[$i]['routePath'] = $originalPages[$i]['routePath'];
        }

        return $pages;
    }

    /**
     * Remove draft from children.
     */
    public function removeDraftChildren(RemoveDraftEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        foreach ($document->getChildren() as $child) {
            if (LocalizationState::GHOST === $this->documentInspector->getLocalizationState($child)) {
                continue;
            }

            try {
                $this->documentManager->removeDraft($child, $event->getLocale());
            } catch (PathNotFoundException $exception) {
                // child is not available in live workspace
                $node = $this->documentInspector->getNode($child);
                $node->remove();
            }
        }
    }

    /**
     * Index all scheduled article documents with default indexer.
     */
    public function handleFlush(FlushEvent $event): void
    {
        if (\count($this->documents) < 1) {
            return;
        }

        foreach ($this->documents as $documentData) {
            /** @var ArticleDocument|null $document */
            $document = $this->documentManager->find($documentData['uuid'], $documentData['locale']);
            $this->documentManager->refresh($document);

            $this->indexer->index($document);
        }
        $this->indexer->flush();
        $this->documents = [];
    }

    /**
     * Index all scheduled article documents with live indexer.
     */
    public function handleFlushLive(FlushEvent $event): void
    {
        if (\count($this->liveDocuments) < 1) {
            return;
        }

        foreach ($this->liveDocuments as $documentData) {
            /** @var ArticleDocument|null $document */
            $document = $this->documentManager->find($documentData['uuid'], $documentData['locale']);
            $this->documentManager->refresh($document);

            $this->liveIndexer->index($document);
        }
        $this->liveIndexer->flush();
        $this->liveDocuments = [];
    }

    /**
     * Removes document from live index and unpublish document in default index.
     */
    public function handleUnpublish(UnpublishEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->liveIndexer->remove($document, $event->getLocale());
        $this->liveIndexer->flush();

        $this->indexer->setUnpublished($document->getUuid(), $event->getLocale());
        $this->indexer->flush();
    }

    /**
     * Reindex article if a page was removed.
     */
    public function handleRemovePage(RemoveEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticlePageDocument) {
            return;
        }

        $document = $document->getParent();
        $this->documents[$document->getUuid() . '_' . $document->getLocale()] = [
            'uuid' => $document->getUuid(),
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Removes article-document.
     */
    public function handleRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->indexer->remove($document);
        $this->indexer->flush();
    }

    /**
     * Removes localized article-document.
     */
    public function handleRemoveLocale(RemoveLocaleEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $concreteLocales = $this->documentInspector->getConcreteLocales($document);
        $ghostLocale = $document->getOriginalLocale();
        if (!\in_array($ghostLocale, $concreteLocales, true)) {
            $ghostLocale = $concreteLocales[0] ?? null;
        }

        if (null !== $ghostLocale) {
            /** @var ArticleDocument $document */
            $document = $this->documentManager->find($document->getUuid(), $ghostLocale);
        }

        $this->indexer->replaceWithGhostData($document, $event->getLocale());
        $this->indexer->flush();
    }

    /**
     * Removes article-document.
     *
     * @param RemoveEvent|UnpublishEvent $event
     */
    public function handleRemoveLive($event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->liveIndexer->remove($document);
        $this->liveIndexer->flush();
    }

    /**
     * Removes localized article-document from live index.
     */
    public function handleRemoveLocaleLive(RemoveLocaleEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $this->liveIndexer->remove($document, $event->getLocale());
        $this->liveIndexer->flush();
    }

    /**
     * Schedule document to index.
     */
    public function handleCopy(CopyEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        $uuid = $event->getCopiedNode()->getIdentifier();
        $this->documents[$uuid . '_' . $document->getLocale()] = [
            'uuid' => $uuid,
            'locale' => $document->getLocale(),
        ];
    }

    /**
     * Schedule all children.
     */
    public function handleChildrenPersist(PersistEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleDocument) {
            return;
        }

        foreach ($document->getChildren() as $childDocument) {
            if (!$childDocument instanceof ArticlePageDocument) {
                continue;
            }

            $localizationState = $this->documentInspector->getLocalizationState($childDocument);

            if (LocalizationState::GHOST === $localizationState) {
                continue;
            }

            $changed = false;

            if ($document->getStructureType() !== $childDocument->getStructureType()) {
                $childDocument->setStructureType($document->getStructureType());
                $changed = true;
            }

            if ($document->getShadowLocale() !== $childDocument->getShadowLocale()) {
                $childDocument->setShadowLocale($document->getShadowLocale());
                $changed = true;
            }

            if ($document->isShadowLocaleEnabled() !== $childDocument->isShadowLocaleEnabled()) {
                $childDocument->setShadowLocaleEnabled($document->isShadowLocaleEnabled());
                $changed = true;
            }

            if ($changed) {
                $this->documentManager->persist(
                    $childDocument,
                    $childDocument->getLocale(),
                    [
                        'clear_missing_content' => false,
                        'auto_name' => false,
                        'auto_rename' => false,
                    ]
                );
            }
        }
    }

    /**
     * Extend metadata for article-page.
     */
    public function handleMetadataLoad(MetadataLoadEvent $event): void
    {
        if (ArticleDocument::class !== $event->getMetadata()->getClass()) {
            return;
        }

        $event->getMetadata()->addFieldMapping(
            'pageTitle',
            [
                'encoding' => 'system_localized',
                'property' => 'suluPageTitle',
            ]
        );
    }
}
