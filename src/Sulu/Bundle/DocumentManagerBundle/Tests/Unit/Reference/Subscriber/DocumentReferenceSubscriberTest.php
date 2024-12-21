<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Reference\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Reference\Provider\DocumentReferenceProviderInterface;
use Sulu\Bundle\DocumentManagerBundle\Reference\Subscriber\DocumentReferenceSubscriber;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Event\CopyLocaleEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\HttpKernel\SuluKernel;

class DocumentReferenceSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var DocumentReferenceProviderInterface|ObjectProphecy<DocumentReferenceProviderInterface>
     */
    private DocumentReferenceProviderInterface|ObjectProphecy $documentReferenceProvider;

    /**
     * @var array<string, DocumentReferenceProviderInterface>
     */
    private array $documentReferenceProviders;

    /**
     * @var ReferenceRepositoryInterface|ObjectProphecy<ReferenceRepositoryInterface>
     */
    private ReferenceRepositoryInterface|ObjectProphecy $referenceRepository;
    private DocumentReferenceSubscriber $documentReferenceSubscriber;

    protected function setUp(): void
    {
        $this->documentReferenceProvider = $this->prophesize(DocumentReferenceProviderInterface::class);
        $this->documentReferenceProviders = [
            PageDocument::RESOURCE_KEY => $this->documentReferenceProvider->reveal(),
        ];
        $this->referenceRepository = $this->prophesize(ReferenceRepositoryInterface::class);

        $this->documentReferenceSubscriber = new DocumentReferenceSubscriber(
            $this->documentReferenceProviders,
            $this->referenceRepository->reveal(),
        );
    }

    public function testOnPublish(): void
    {
        $document = new PageDocument();

        $event = new PublishEvent($document, 'en');
        $this->documentReferenceSubscriber->onPublish($event);

        // get publishDocuments from subscriber with reflection
        $reflection = new \ReflectionClass($this->documentReferenceSubscriber);
        $property = $reflection->getProperty('publishDocuments');
        $property->setAccessible(true); // TODO remove when minimum php version is 8.1
        /** @var array<array{document: UuidBehavior&TitleBehavior&StructureBehavior, locale: string}> $publishDocuments */
        $publishDocuments = $property->getValue($this->documentReferenceSubscriber);

        self::assertCount(1, $publishDocuments);
        self::assertSame($document, $publishDocuments[0]['document']);
        self::assertSame('en', $publishDocuments[0]['locale']);
    }

    public function testOnUnpublish(): void
    {
        $document = new PageDocument();

        $event = new UnpublishEvent($document, 'en');
        $this->documentReferenceSubscriber->onUnpublish($event);

        // get unpublishDocuments from subscriber with reflection
        $reflection = new \ReflectionClass($this->documentReferenceSubscriber);
        $property = $reflection->getProperty('unpublishDocuments');
        $property->setAccessible(true); // TODO remove when minimum php version is 8.1
        /** @var array<array{document: UuidBehavior&TitleBehavior&StructureBehavior, locale: string}> $unpublishDocuments */
        $unpublishDocuments = $property->getValue($this->documentReferenceSubscriber);

        self::assertCount(1, $unpublishDocuments);
        self::assertSame($document, $unpublishDocuments[0]['document']);
        self::assertSame('en', $unpublishDocuments[0]['locale']);
    }

    public function testOnPersist(): void
    {
        $document = new PageDocument();

        $event = new PersistEvent($document, 'en');
        $this->documentReferenceSubscriber->onPersist($event);

        // get persistDocuments from subscriber with reflection
        $reflection = new \ReflectionClass($this->documentReferenceSubscriber);
        $property = $reflection->getProperty('persistDocuments');
        $property->setAccessible(true); // TODO remove when minimum php version is 8.1
        /** @var array<array{document: UuidBehavior&TitleBehavior&StructureBehavior, locale: string}> $persistDocuments */
        $persistDocuments = $property->getValue($this->documentReferenceSubscriber);

        self::assertCount(1, $persistDocuments);
        self::assertSame($document, $persistDocuments[0]['document']);
        self::assertSame('en', $persistDocuments[0]['locale']);
    }

    public function testOnRemove(): void
    {
        $document = new PageDocument();

        $event = new RemoveEvent($document);
        $this->documentReferenceSubscriber->onRemove($event);

        // get removeDocuments from subscriber with reflection
        $reflection = new \ReflectionClass($this->documentReferenceSubscriber);
        $property = $reflection->getProperty('removeDocuments');
        $property->setAccessible(true); // TODO remove when minimum php version is 8.1
        /** @var array<array{document: UuidBehavior&TitleBehavior&StructureBehavior}> $removeDocuments */
        $removeDocuments = $property->getValue($this->documentReferenceSubscriber);

        self::assertCount(1, $removeDocuments);
        self::assertSame($document, $removeDocuments[0]['document']);
    }

    public function testOnRemoveLocale(): void
    {
        $document = new PageDocument();

        $event = new RemoveLocaleEvent($document, 'en');
        $this->documentReferenceSubscriber->onRemoveLocale($event);

        // get removeDocuments from subscriber with reflection
        $reflection = new \ReflectionClass($this->documentReferenceSubscriber);
        $property = $reflection->getProperty('removeDocuments');
        $property->setAccessible(true); // TODO remove when minimum php version is 8.1
        /** @var array<array{document: UuidBehavior&TitleBehavior&StructureBehavior, locale: string}> $removeDocuments */
        $removeDocuments = $property->getValue($this->documentReferenceSubscriber);

        self::assertCount(1, $removeDocuments);
        self::assertSame($document, $removeDocuments[0]['document']);
        self::assertSame('en', $removeDocuments[0]['locale']);
    }

    public function testOnCopyLocale(): void
    {
        $document = new PageDocument();

        $event = new CopyLocaleEvent($document, 'en', 'de');
        $this->documentReferenceSubscriber->onCopyLocale($event);

        // get persistDocuments from subscriber with reflection
        $reflection = new \ReflectionClass($this->documentReferenceSubscriber);
        $property = $reflection->getProperty('persistDocuments');
        $property->setAccessible(true); // TODO remove when minimum php version is 8.1
        /** @var array<array{document: UuidBehavior&TitleBehavior&StructureBehavior, locale: string}> $persistDocuments */
        $persistDocuments = $property->getValue($this->documentReferenceSubscriber);

        self::assertCount(1, $persistDocuments);
        self::assertSame($document, $persistDocuments[0]['document']);
        self::assertSame('de', $persistDocuments[0]['locale']);
    }

    public function testOnFlush(): void
    {
        $document = new PageDocument();

        $persistEvent = new PersistEvent($document, 'en');
        $this->documentReferenceSubscriber->onPersist($persistEvent);

        $unpublishEvent = new UnpublishEvent($document, 'en');
        $this->documentReferenceSubscriber->onUnpublish($unpublishEvent);

        $publishEvent = new PublishEvent($document, 'en');
        $this->documentReferenceSubscriber->onPublish($publishEvent);

        $removeEvent = new RemoveEvent($document);
        $this->documentReferenceSubscriber->onRemove($removeEvent);

        $this->referenceRepository->flush()->shouldBeCalledTimes(2);

        // persistDocuments
        $this->documentReferenceProvider->updateReferences(
            $document,
            'en',
            SuluKernel::CONTEXT_ADMIN,
        )->shouldBeCalledTimes(1);

        // publishDocuments
        $this->documentReferenceProvider->updateReferences(
            $document,
            'en',
            SuluKernel::CONTEXT_WEBSITE,
        )->shouldBeCalledTimes(1);

        // unpublishDocuments
        $this->documentReferenceProvider->removeReferences(
            $document,
            'en',
            SuluKernel::CONTEXT_WEBSITE
        )->shouldBeCalledTimes(1);

        // removeDocuments
        $this->documentReferenceProvider->removeReferences(
            $document,
            null,
            SuluKernel::CONTEXT_ADMIN
        )->shouldBeCalledTimes(1);

        // removeDocuments
        $this->documentReferenceProvider->removeReferences(
            $document,
            null,
            SuluKernel::CONTEXT_WEBSITE
        )->shouldBeCalledTimes(1);

        $flushEvent = new FlushEvent();
        $this->documentReferenceSubscriber->onFlush($flushEvent);

        $reflection = new \ReflectionClass($this->documentReferenceSubscriber);
        $persistDocumentsProperty = $reflection->getProperty('persistDocuments');
        $persistDocumentsProperty->setAccessible(true); // TODO remove when minimum php version is 8.1
        $unpublishDocumentsProperty = $reflection->getProperty('unpublishDocuments');
        $unpublishDocumentsProperty->setAccessible(true); // TODO remove when minimum php version is 8.1
        $publishDocumentsProperty = $reflection->getProperty('publishDocuments');
        $publishDocumentsProperty->setAccessible(true); // TODO remove when minimum php version is 8.1
        $removeDocumentsProperty = $reflection->getProperty('removeDocuments');
        $removeDocumentsProperty->setAccessible(true); // TODO remove when minimum php version is 8.1

        /** @var array<array{document: UuidBehavior&TitleBehavior&StructureBehavior, locale: string}> $persistDocuments */
        $persistDocuments = $persistDocumentsProperty->getValue($this->documentReferenceSubscriber);
        /** @var array<array{document: UuidBehavior&TitleBehavior&StructureBehavior, locale: string}> $unpublishDocuments */
        $unpublishDocuments = $unpublishDocumentsProperty->getValue($this->documentReferenceSubscriber);
        /** @var array<array{document: UuidBehavior&TitleBehavior&StructureBehavior, locale: string}> $publishDocuments */
        $publishDocuments = $publishDocumentsProperty->getValue($this->documentReferenceSubscriber);
        /** @var array<array{document: UuidBehavior&TitleBehavior&StructureBehavior, locale: string}> $removeDocuments */
        $removeDocuments = $removeDocumentsProperty->getValue($this->documentReferenceSubscriber);

        self::assertCount(0, $persistDocuments);
        self::assertCount(0, $unpublishDocuments);
        self::assertCount(0, $publishDocuments);
        self::assertCount(0, $removeDocuments);
    }
}
