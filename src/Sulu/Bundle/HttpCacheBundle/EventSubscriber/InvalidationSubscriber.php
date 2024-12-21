<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\EventSubscriber;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Listens on document-manager events and invalidates cached urls to prevent outdated
 * information in the cache.
 *
 * TODO: the url-gathering for changed documents should not be implemented in this class.
 */
class InvalidationSubscriber implements EventSubscriberInterface
{
    public const HTTP_CACHE_INVALIDATION_OPTION = 'http_cache_invalidation';

    /**
     * @param string $environment - kernel envionment, dev, prod, etc
     */
    public function __construct(
        private ?CacheManager $cacheManager,
        private StructureManagerInterface $structureManager,
        private DocumentInspector $documentInspector,
        private ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool,
        private WebspaceManagerInterface $webspaceManager,
        private RequestStack $requestStack,
        private TagManagerInterface $tagManager,
        private ?string $environment
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::CONFIGURE_OPTIONS => 'configureOptions',
            Events::PUBLISH => ['invalidateDocumentBeforePublishing', 1024],
            Events::UNPUBLISH => ['invalidateDocumentBeforeUnpublishing', 1024],
            Events::REMOVE => ['invalidateDocumentBeforeRemoving', 1024],
            Events::REMOVE_LOCALE => ['invalidateDocumentBeforeRemovingLocale', 1024],
        ];
    }

    /**
     * Invalidates the assigned structure and all urls in the locale of the document of an already published document
     * when it gets republished (eg on content change).
     * This method is executed before the actual publishing of the document to avoid purging new urls.
     */
    public function invalidateDocumentBeforePublishing(PublishEvent $event)
    {
        if (false === $event->getOption(static::HTTP_CACHE_INVALIDATION_OPTION, true)) {
            return;
        }

        $document = $event->getDocument();

        if ($document instanceof StructureBehavior) {
            $this->invalidateDocumentStructure($document);
        }

        if ($document instanceof ResourceSegmentBehavior
            && $document instanceof WorkflowStageBehavior
            && $document->getPublished()
        ) {
            $this->invalidateDocumentUrls($document, $this->documentInspector->getLocale($document));
        }

        if ($document instanceof ExtensionBehavior) {
            $this->invalidateDocumentExcerpt($document);
        }
    }

    /**
     * Invalidates the assigned structure and all urls in the locale of the document when a document gets unpublished.
     * This method is executed before the actual unpublishing of the document because the document must still
     * be published to gather the urls of the document.
     */
    public function invalidateDocumentBeforeUnpublishing(UnpublishEvent $event)
    {
        if (false === $event->getOption(static::HTTP_CACHE_INVALIDATION_OPTION, true)) {
            return;
        }

        $document = $event->getDocument();

        if ($document instanceof StructureBehavior) {
            $this->invalidateDocumentStructure($document);
        }

        if ($document instanceof ResourceSegmentBehavior
            && $document instanceof WorkflowStageBehavior
            && $document->getPublished()
        ) {
            $this->invalidateDocumentUrls($document, $this->documentInspector->getLocale($document));
        }
    }

    /**
     * Invalidates the assigned structure and all urls in all locales of the document when a document gets removed.
     * This method is executed before the actual removing of the document because the document must still
     * exist to gather the urls of the document.
     */
    public function invalidateDocumentBeforeRemoving(RemoveEvent $event)
    {
        if (false === $event->getOption(static::HTTP_CACHE_INVALIDATION_OPTION, true)) {
            return;
        }

        $document = $event->getDocument();

        if ($document instanceof StructureBehavior) {
            $this->invalidateDocumentStructure($document);
        }

        if ($document instanceof ResourceSegmentBehavior) {
            foreach ($this->documentInspector->getPublishedLocales($document) as $locale) {
                $this->invalidateDocumentUrls($document, $locale);
            }
        }
    }

    /**
     * Invalidates the assigned structure and all urls in all locales of the document when a document gets removed.
     * This method is executed before the actual removing of the document because the document must still
     * exist to gather the urls of the document.
     */
    public function invalidateDocumentBeforeRemovingLocale(RemoveLocaleEvent $event)
    {
        if (false === $event->getOption(static::HTTP_CACHE_INVALIDATION_OPTION, true)) {
            return;
        }

        $document = $event->getDocument();

        if ($document instanceof StructureBehavior) {
            $this->invalidateDocumentStructure($document);
        }

        if ($document instanceof ResourceSegmentBehavior) {
            $this->invalidateDocumentUrls($document, $event->getLocale());
        }
    }

    /**
     * Invalidates the structure of the given document.
     *
     * @param StructureBehavior $document
     */
    private function invalidateDocumentStructure($document)
    {
        if (!$this->cacheManager) {
            return;
        }

        $structureBridge = $this->structureManager->wrapStructure(
            $this->documentInspector->getMetadata($document)->getAlias(),
            $this->documentInspector->getStructureMetadata($document)
        );
        $structureBridge->setDocument($document);

        $this->cacheManager->invalidateTag($document->getUuid());
    }

    /**
     * Invalidates all urls which are assigned to the given document in the given locale.
     *
     * @param ResourceSegmentBehavior $document
     * @param string $locale
     */
    private function invalidateDocumentUrls($document, $locale)
    {
        if (!$this->cacheManager) {
            return;
        }

        foreach ($this->getLocaleUrls($document, $locale) as $url) {
            $this->cacheManager->invalidatePath($url);
        }
    }

    /**
     * Invalidates all tags and categories from excerpt extension.
     */
    private function invalidateDocumentExcerpt(ExtensionBehavior $document)
    {
        if (!$this->cacheManager) {
            return;
        }

        $extensionData = $document->getExtensionsData();
        if (!isset($extensionData['excerpt'])) {
            return;
        }

        $excerpt = $extensionData['excerpt'];

        if (isset($excerpt['tags'])) {
            foreach ($this->tagManager->resolveTagNames($excerpt['tags']) as $tag) {
                $this->cacheManager->invalidateReference('tag', $tag);
            }
        }

        if (isset($excerpt['categories'])) {
            foreach ($excerpt['categories'] as $category) {
                $this->cacheManager->invalidateReference('category', $category);
            }
        }
    }

    /**
     * Returns all urls of the given locale which are associated with the given document.
     * The returned array contains all current urls and all history urls.
     *
     * @param string $locale
     *
     * @return array Urls of the given locale which are associated with the given document
     */
    private function getLocaleUrls(ResourceSegmentBehavior $document, $locale)
    {
        $uuid = ($document instanceof UuidBehavior) ? $document->getUuid() : null;
        $webspace = ($document instanceof WebspaceBehavior) ? $document->getWebspaceName() : null;
        if (!$locale || !$uuid || !$webspace) {
            return [];
        }

        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspace);

        // get current resource-locator and history resource-locators
        $resourceLocators = [];

        try {
            $resourceLocators[] = $resourceLocatorStrategy->loadByContentUuid($uuid, $webspace, $locale);
        } catch (ResourceLocatorNotFoundException $e) {
            // if no resource locator exists there is also no url to purge from the cache
        }

        $historyResourceLocators = $resourceLocatorStrategy->loadHistoryByContentUuid($uuid, $webspace, $locale);
        foreach ($historyResourceLocators as $historyResourceLocator) {
            $resourceLocators[] = $historyResourceLocator->getResourceLocator();
        }

        // get urls for resource-locators
        $urls = [];
        foreach ($resourceLocators as $resourceLocator) {
            $urls = \array_merge($urls, $this->findUrlsByResourceLocator($resourceLocator, $locale, $webspace));
        }

        return $urls;
    }

    /**
     * Returns array of resource-locators with "http" and "https".
     *
     * @param string $resourceLocator
     * @param string $locale
     * @param string $webspace
     *
     * @return string[]
     */
    private function findUrlsByResourceLocator($resourceLocator, $locale, $webspace)
    {
        $scheme = 'http';
        if ($request = $this->requestStack->getCurrentRequest()) {
            $scheme = $request->getScheme();
        }

        return $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocator,
            $this->environment,
            $locale,
            $webspace,
            null,
            $scheme
        );
    }

    public function configureOptions(ConfigureOptionsEvent $event): void
    {
        $optionsResolver = $event->getOptions();

        $optionsResolver->setDefault(static::HTTP_CACHE_INVALIDATION_OPTION, true);
    }
}
