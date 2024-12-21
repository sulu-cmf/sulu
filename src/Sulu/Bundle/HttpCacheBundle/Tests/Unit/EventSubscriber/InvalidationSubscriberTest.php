<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\HttpCacheBundle\EventSubscriber\InvalidationSubscriber;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Types\ResourceLocator\ResourceLocatorInformation;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class InvalidationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var InvalidationSubscriber
     */
    private $invalidationSubscriber;

    /**
     * @var ObjectProphecy<CacheManager>
     */
    private $cacheManager;

    /**
     * @var ObjectProphecy<StructureManagerInterface>
     */
    private $structureManager;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var ObjectProphecy<ResourceLocatorStrategyInterface>
     */
    private $resourceLocatorStrategy;

    /**
     * @var ObjectProphecy<ResourceLocatorStrategyPoolInterface>
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var ObjectProphecy<TagManagerInterface>
     */
    private $tagManager;

    /**
     * @var string
     */
    private $env = 'prod';

    public function setUp(): void
    {
        $this->cacheManager = $this->prophesize(CacheManager::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->resourceLocatorStrategy = $this->prophesize(ResourceLocatorStrategyInterface::class);
        $this->resourceLocatorStrategyPool = $this->prophesize(ResourceLocatorStrategyPoolInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->tagManager = $this->prophesize(TagManagerInterface::class);

        $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey(Argument::any())
            ->willReturn($this->resourceLocatorStrategy->reveal());

        $this->invalidationSubscriber = new InvalidationSubscriber(
            $this->cacheManager->reveal(),
            $this->structureManager->reveal(),
            $this->documentInspector->reveal(),
            $this->resourceLocatorStrategyPool->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestStack->reveal(),
            $this->tagManager->reveal(),
            $this->env
        );
    }

    public static function provideRequest()
    {
        return [
            [false, 'http'],
            [true, 'http'],
            [true, 'https'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRequest')]
    public function testInvalidateDocumentBeforePublishing(bool $hasRequest, $scheme): void
    {
        $documentUuid = '743389e6-2ac5-4673-9835-3e709a27a03d';

        $request = null;
        if ($hasRequest) {
            $request = $this->prophesize(Request::class);
            $request->getScheme()->willReturn($scheme);
            $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
        }

        $documentLocale = 'en';
        $documentWebspace = 'sulu_io';

        $resourceLocator1 = '/path/to/1';
        $resourceLocator2 = '/path/to/2';

        $url1 = 'sulu.lo/path/to/1';
        $url2 = 'sulu.lo/path/to/2';
        $url3 = 'sulu.lo/other/to/2';

        $document = $this->prophesize(BasePageDocument::class);
        $document->getPublished()->willReturn(true);
        $document->getUuid()->willReturn($documentUuid);
        $document->getWebspaceName()->willReturn($documentWebspace);
        $document->getExtensionsData()->willReturn([]);
        $this->documentInspector->getLocale($document)->willReturn($documentLocale);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->documentInspector->getStructureMetadata($document)->willReturn($structureMetadata);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getAlias()->willReturn('alias');
        $this->documentInspector->getMetadata($document)->willReturn($metadata);

        $structureBridge = $this->prophesize(StructureBridge::class);
        $this->structureManager->wrapStructure('alias', $structureMetadata)->willReturn($structureBridge);
        $structureBridge->setDocument($document)->shouldBeCalled();
        $this->cacheManager->invalidateTag($documentUuid)->shouldBeCalled();

        $this->resourceLocatorStrategy->loadByContentUuid($documentUuid, $documentWebspace, $documentLocale)
            ->willReturn($resourceLocator2);
        $rli = $this->prophesize(ResourceLocatorInformation::class);
        $rli->getResourceLocator()->willReturn($resourceLocator1);
        $this->resourceLocatorStrategy->loadHistoryByContentUuid($documentUuid, $documentWebspace, $documentLocale)
            ->willReturn([$rli]);

        $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocator1,
            $this->env,
            $documentLocale,
            $documentWebspace,
            null,
            $scheme
        )->willReturn([$url1]);

        $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocator2,
            $this->env,
            $documentLocale,
            $documentWebspace,
            null,
            $scheme
        )->willReturn([$url2, $url3]);

        $this->cacheManager->invalidatePath($url1)->shouldBeCalled();
        $this->cacheManager->invalidatePath($url2)->shouldBeCalled();
        $this->cacheManager->invalidatePath($url3)->shouldBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforePublishing($event->reveal());
    }

    public function testInvalidateDocumentBeforePublishingDocumentNotPublished(): void
    {
        $documentUuid = '743389e6-2ac5-7777-9835-3e709a27a03d';

        $document = $this->prophesize(BasePageDocument::class);
        $document->getPublished()->willReturn(false);
        $document->getExtensionsData()->willReturn([]);

        $document->getUuid()->willReturn($documentUuid);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->documentInspector->getStructureMetadata($document)->willReturn($structureMetadata);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getAlias()->willReturn('alias');
        $this->documentInspector->getMetadata($document)->willReturn($metadata);

        $structureBridge = $this->prophesize(StructureBridge::class);
        $this->structureManager->wrapStructure('alias', $structureMetadata)->willReturn($structureBridge);
        $structureBridge->setDocument($document)->shouldBeCalled();

        $this->cacheManager->invalidateTag($documentUuid)->shouldBeCalled();
        $this->cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforePublishing($event->reveal());
    }

    public function testInvalidateDocumentBeforePublishingExcerpt(): void
    {
        $documentUuid = '743389e6-2ac5-7777-9835-3e709a27a03d';

        $document = $this->prophesize(BasePageDocument::class);
        $document->getPublished()->willReturn(false);
        $document->getExtensionsData()->willReturn(['excerpt' => ['tags' => ['Tag1', 'Tag2'], 'categories' => [3, 4]]]);

        $this->tagManager->resolveTagNames(['Tag1', 'Tag2'])->willReturn([1, 2]);

        $this->cacheManager->invalidateReference('tag', 1)->shouldBeCalled();
        $this->cacheManager->invalidateReference('tag', 2)->shouldBeCalled();
        $this->cacheManager->invalidateReference('category', 3)->shouldBeCalled();
        $this->cacheManager->invalidateReference('category', 4)->shouldBeCalled();

        $document->getUuid()->willReturn($documentUuid);

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->documentInspector->getStructureMetadata($document)->willReturn($structureMetadata);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getAlias()->willReturn('alias');
        $this->documentInspector->getMetadata($document)->willReturn($metadata);

        $structureBridge = $this->prophesize(StructureBridge::class);
        $this->structureManager->wrapStructure('alias', $structureMetadata)->willReturn($structureBridge);
        $structureBridge->setDocument($document)->shouldBeCalled();

        $this->cacheManager->invalidateTag($documentUuid)->shouldBeCalled();
        $this->cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforePublishing($event->reveal());
    }

    public function testInvalidateDocumentBeforePublishingWrongDocument(): void
    {
        $document = new \stdClass();
        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $this->cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforePublishing($event->reveal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRequest')]
    public function testInvalidateDocumentBeforeUnpublishing(bool $hasRequest, $scheme): void
    {
        $documentUuid = '743c89e6-2ac5-7777-9835-3e709a27a03d';

        $request = null;
        if ($hasRequest) {
            $request = $this->prophesize(Request::class);
            $request->getScheme()->willReturn($scheme);

            $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
        }

        $documentLocale = 'en';
        $documentWebspace = 'sulu_io';

        $resourceLocator1 = '/path/to/1';
        $resourceLocator2 = '/path/to/2';

        $url1 = 'sulu.lo/path/to/1';
        $url2 = 'sulu.lo/path/to/2';

        $document = $this->prophesize(BasePageDocument::class);
        $document->getPublished()->willReturn(true);
        $document->getUuid()->willReturn($documentUuid);
        $document->getWebspaceName()->willReturn($documentWebspace);
        $this->documentInspector->getLocale($document)->willReturn($documentLocale);

        $event = $this->prophesize(UnpublishEvent::class);
        $event->getDocument()->willReturn($document);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->documentInspector->getStructureMetadata($document)->willReturn($structureMetadata);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getAlias()->willReturn('alias');
        $this->documentInspector->getMetadata($document)->willReturn($metadata);

        $structureBridge = $this->prophesize(StructureBridge::class);
        $this->structureManager->wrapStructure('alias', $structureMetadata)->willReturn($structureBridge);
        $structureBridge->setDocument($document)->shouldBeCalled();
        $this->cacheManager->invalidateTag($documentUuid)->shouldBeCalled();

        $this->resourceLocatorStrategy->loadByContentUuid($documentUuid, $documentWebspace, $documentLocale)
            ->willReturn($resourceLocator2);
        $rli = $this->prophesize(ResourceLocatorInformation::class);
        $rli->getResourceLocator()->willReturn($resourceLocator1);
        $this->resourceLocatorStrategy->loadHistoryByContentUuid($documentUuid, $documentWebspace, $documentLocale)
            ->willReturn([$rli]);

        $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocator1,
            $this->env,
            $documentLocale,
            $documentWebspace,
            null,
            $scheme
        )->willReturn([$url1]);

        $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocator2,
            $this->env,
            $documentLocale,
            $documentWebspace,
            null,
            $scheme
        )->willReturn([$url2]);

        $this->cacheManager->invalidatePath($url1)->shouldBeCalled();
        $this->cacheManager->invalidatePath($url2)->shouldBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforeUnpublishing($event->reveal());
    }

    public function testInvalidateDocumentBeforeUnpublishingDocumentNotPublished(): void
    {
        $documentUuid = '743c89e6-2ac5-7777-1234-3e709a27a03d';

        $document = $this->prophesize(BasePageDocument::class);
        $document->getUuid()->willReturn($documentUuid);
        $document->getPublished()->willReturn(false);

        $event = $this->prophesize(UnpublishEvent::class);
        $event->getDocument()->willReturn($document);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->documentInspector->getStructureMetadata($document)->willReturn($structureMetadata);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getAlias()->willReturn('alias');
        $this->documentInspector->getMetadata($document)->willReturn($metadata);

        $structureBridge = $this->prophesize(StructureBridge::class);
        $this->structureManager->wrapStructure('alias', $structureMetadata)->willReturn($structureBridge);
        $structureBridge->setDocument($document)->shouldBeCalled();

        $this->cacheManager->invalidateTag($documentUuid)->shouldBeCalled();
        $this->cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforeUnpublishing($event->reveal());
    }

    public function testInvalidateDocumentBeforeUnpublishingWrongDocument(): void
    {
        $document = new \stdClass();
        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $this->cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforePublishing($event->reveal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRequest')]
    public function testInvalidateDocumentBeforeRemoving(bool $hasRequest, $scheme): void
    {
        $documentUuid = '743c89e6-2ac5-7777-1234-3e709a27a0bb';

        $request = null;
        if ($hasRequest) {
            $request = $this->prophesize(Request::class);
            $request->getScheme()->willReturn($scheme);
            $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
        }

        $documentLocales = ['en', 'de'];
        $documentWebspace = 'sulu_io';

        $resourceLocatorEn1 = '/path/to/1';
        $resourceLocatorDe1 = '/pfad/zu/1';
        $resourceLocatorEn2 = '/path/to/2';

        $urlEn1 = 'sulu.lo/path/to/1';
        $urlDe1 = 'sulu.lo/other/to/2';
        $urlEn2 = 'sulu.lo/path/to/2';

        $document = $this->prophesize(BasePageDocument::class);
        $document->getPublished()->willReturn(true);
        $document->getUuid()->willReturn($documentUuid);
        $document->getWebspaceName()->willReturn($documentWebspace);
        $this->documentInspector->getPublishedLocales($document)->willReturn($documentLocales);

        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($document);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->documentInspector->getStructureMetadata($document)->willReturn($structureMetadata);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getAlias()->willReturn('alias');
        $this->documentInspector->getMetadata($document)->willReturn($metadata);

        $structureBridge = $this->prophesize(StructureBridge::class);
        $this->structureManager->wrapStructure('alias', $structureMetadata)->willReturn($structureBridge);
        $structureBridge->setDocument($document)->shouldBeCalled();
        $this->cacheManager->invalidateTag($documentUuid)->shouldBeCalled();

        $this->resourceLocatorStrategy->loadByContentUuid($documentUuid, $documentWebspace, $documentLocales[0])
            ->willReturn($resourceLocatorEn1);
        $rli = $this->prophesize(ResourceLocatorInformation::class);
        $rli->getResourceLocator()->willReturn($resourceLocatorEn2);
        $this->resourceLocatorStrategy->loadHistoryByContentUuid($documentUuid, $documentWebspace, $documentLocales[0])
            ->willReturn([$rli]);

        // de resource-locator related
        $this->resourceLocatorStrategy->loadByContentUuid($documentUuid, $documentWebspace, $documentLocales[1])
            ->willReturn($resourceLocatorDe1);
        $this->resourceLocatorStrategy->loadHistoryByContentUuid($documentUuid, $documentWebspace, $documentLocales[1])
            ->willReturn([]);

        // en url related
        $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocatorEn1,
            $this->env,
            $documentLocales[0],
            $documentWebspace,
            null,
            $scheme
        )->willReturn([$urlEn1]);

        $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocatorEn2,
            $this->env,
            $documentLocales[0],
            $documentWebspace,
            null,
            $scheme
        )->willReturn([$urlEn2]);

        // de url related
        $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocatorDe1,
            $this->env,
            $documentLocales[1],
            $documentWebspace,
            null,
            $scheme
        )->willReturn([$urlDe1]);

        $this->cacheManager->invalidatePath($urlEn1)->shouldBeCalled();
        $this->cacheManager->invalidatePath($urlDe1)->shouldBeCalled();
        $this->cacheManager->invalidatePath($urlEn2)->shouldBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforeRemoving($event->reveal());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRequest')]
    public function testInvalidateDocumentBeforeRemovingLocale(bool $hasRequest, $scheme): void
    {
        $documentUuid = '743c89e6-2ac5-7777-1234-3e709a27a0bb';

        $request = null;
        if ($hasRequest) {
            $request = $this->prophesize(Request::class);
            $request->getScheme()->willReturn($scheme);
            $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
        }

        $documentLocales = ['en', 'de'];
        $documentWebspace = 'sulu_io';

        $resourceLocatorEn1 = '/path/to/1';
        $resourceLocatorEn2 = '/path/to/2';

        $urlEn1 = 'sulu.lo/path/to/1';
        $urlEn2 = 'sulu.lo/path/to/2';

        $document = $this->prophesize(BasePageDocument::class);
        $document->getPublished()->willReturn(true);
        $document->getUuid()->willReturn($documentUuid);
        $document->getWebspaceName()->willReturn($documentWebspace);
        $this->documentInspector->getPublishedLocales($document)->willReturn($documentLocales);

        $event = $this->prophesize(RemoveLocaleEvent::class);
        $event->getDocument()->willReturn($document);
        $event->getLocale()->willReturn('en');
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->documentInspector->getStructureMetadata($document)->willReturn($structureMetadata);
        $metadata = $this->prophesize(Metadata::class);
        $metadata->getAlias()->willReturn('alias');
        $this->documentInspector->getMetadata($document)->willReturn($metadata);

        $structureBridge = $this->prophesize(StructureBridge::class);
        $this->structureManager->wrapStructure('alias', $structureMetadata)->willReturn($structureBridge);
        $structureBridge->setDocument($document)->shouldBeCalled();
        $this->cacheManager->invalidateTag($documentUuid)->shouldBeCalled();

        $this->resourceLocatorStrategy->loadByContentUuid($documentUuid, $documentWebspace, $documentLocales[0])
            ->willReturn($resourceLocatorEn1);
        $rli = $this->prophesize(ResourceLocatorInformation::class);
        $rli->getResourceLocator()->willReturn($resourceLocatorEn2);
        $this->resourceLocatorStrategy->loadHistoryByContentUuid($documentUuid, $documentWebspace, $documentLocales[0])
            ->willReturn([$rli]);

        // en url related
        $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocatorEn1,
            $this->env,
            $documentLocales[0],
            $documentWebspace,
            null,
            $scheme
        )->willReturn([$urlEn1]);

        $this->webspaceManager->findUrlsByResourceLocator(
            $resourceLocatorEn2,
            $this->env,
            $documentLocales[0],
            $documentWebspace,
            null,
            $scheme
        )->willReturn([$urlEn2]);

        $this->cacheManager->invalidatePath($urlEn1)->shouldBeCalled();
        $this->cacheManager->invalidatePath($urlEn2)->shouldBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforeRemovingLocale($event->reveal());
    }

    public function testInvalidateDocumentBeforeRemovingWithResourceLocatorNotFoundException(): void
    {
        $event = $this->prophesize(RemoveEvent::class);
        $document = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(ShadowLocaleBehavior::class)
            ->willImplement(UuidBehavior::class)
            ->willImplement(WebspaceBehavior::class);
        $document->getUuid()->willReturn('some-uuid')->shouldBeCalled();
        $document->getWebspaceName()->willReturn('sulu')->shouldBeCalled();
        $this->documentInspector->getPublishedLocales($document->reveal())->willReturn(['de']);
        $event->getDocument()->willReturn($document->reveal());
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $this->resourceLocatorStrategy->loadByContentUuid(Argument::cetera())
            ->willThrow(ResourceLocatorNotFoundException::class)->shouldBeCalled();
        $this->resourceLocatorStrategy->loadHistoryByContentUuid(Argument::cetera())->willReturn([]);

        $this->invalidationSubscriber->invalidateDocumentBeforeRemoving($event->reveal());
    }

    public function testInvalidateDocumentBeforeRemovingWrongDocument(): void
    {
        $document = new \stdClass();
        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($document);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(true);

        $this->cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforeRemoving($event->reveal());
    }

    public function testInvalidateDocumentBeforePublishingNoInvalidation(): void
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(false);

        $this->cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforePublishing($event->reveal());
    }

    public function testInvalidateDocumentBeforeUnpublishingNoInvalidation(): void
    {
        $event = $this->prophesize(UnpublishEvent::class);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(false);

        $this->cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforeUnpublishing($event->reveal());
    }

    public function testInvalidateDocumentBeforeRemovingNoInvalidation(): void
    {
        $event = $this->prophesize(RemoveEvent::class);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(false);

        $this->cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforeRemoving($event->reveal());
    }

    public function testInvalidateDocumentBeforeRemovingLocaleNoInvalidation(): void
    {
        $event = $this->prophesize(RemoveLocaleEvent::class);
        $event->getOption(InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION, true)->willReturn(false);

        $this->cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $this->invalidationSubscriber->invalidateDocumentBeforeRemovingLocale($event->reveal());
    }
}
