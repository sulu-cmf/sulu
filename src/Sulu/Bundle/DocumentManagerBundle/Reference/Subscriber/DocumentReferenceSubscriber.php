<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Reference\Subscriber;

use Sulu\Bundle\DocumentManagerBundle\Reference\Provider\DocumentReferenceProviderInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\CopyLocaleEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @final
 *
 * @internal
 */
class DocumentReferenceSubscriber implements EventSubscriberInterface, ResetInterface
{
    /**
     * @var array<string, DocumentReferenceProviderInterface>
     */
    private array $documentReferenceProviders;

    /**
     * @var array<array{
     *     document: UuidBehavior&TitleBehavior&StructureBehavior,
     *     locale: string,
     * }>
     */
    private array $publishDocuments = [];

    /**
     * @var array<array{
     *     document: UuidBehavior&TitleBehavior&StructureBehavior,
     *     locale: string,
     * }>
     */
    private array $unpublishDocuments = [];

    /**
     * @var array<array{
     *     document: UuidBehavior&TitleBehavior&StructureBehavior,
     *     locale: string,
     * }>
     */
    private array $persistDocuments = [];

    /**
     * @var array<array{
     *     document: UuidBehavior&StructureBehavior,
     *     locale: string|null,
     * }>
     */
    private array $removeDocuments = [];

    /**
     * @param iterable<DocumentReferenceProviderInterface> $documentReferenceProviders
     */
    public function __construct(
        iterable $documentReferenceProviders,
        private ReferenceRepositoryInterface $referenceRepository
    ) {
        $this->documentReferenceProviders = $documentReferenceProviders instanceof \Traversable ? \iterator_to_array($documentReferenceProviders) : $documentReferenceProviders;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PUBLISH => 'onPublish',
            Events::UNPUBLISH => 'onUnpublish',
            Events::PERSIST => 'onPersist',
            Events::REMOVE => 'onRemove',
            Events::REMOVE_LOCALE => 'onRemoveLocale',
            Events::CLEAR => 'onClear',
            Events::FLUSH => 'onFlush',
            Events::COPY_LOCALE => 'onCopyLocale',
        ];
    }

    public function onPublish(PublishEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof StructureBehavior
            || !$document instanceof TitleBehavior
            || !$document instanceof UuidBehavior
        ) {
            return;
        }

        if (!$locale) {
            // Ignore documents for permission changes
            // https://github.com/sulu/sulu/blob/2.5.9/src/Sulu/Component/Security/Authorization/AccessControl/PhpcrAccessControlProvider.php#L51

            return;
        }

        $this->publishDocuments[] = [
            'document' => $document,
            'locale' => $locale,
        ];
    }

    public function onUnpublish(UnpublishEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof StructureBehavior
            || !$document instanceof TitleBehavior
            || !$document instanceof UuidBehavior
        ) {
            return;
        }

        $this->unpublishDocuments[] = [
            'document' => $document,
            'locale' => $locale,
        ];
    }

    public function onCopyLocale(CopyLocaleEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getDestLocale();

        if (!$document instanceof StructureBehavior
            || !$document instanceof TitleBehavior
            || !$document instanceof UuidBehavior
        ) {
            return;
        }

        $this->persistDocuments[] = [
            'document' => $document,
            'locale' => $locale,
        ];
    }

    public function onPersist(PersistEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof StructureBehavior
            || !$document instanceof TitleBehavior
            || !$document instanceof UuidBehavior
        ) {
            return;
        }

        if (!$locale) {
            // Ignore documents for permission changes
            // https://github.com/sulu/sulu/blob/2.5.9/src/Sulu/Component/Security/Authorization/AccessControl/PhpcrAccessControlProvider.php#L51

            return;
        }

        $this->persistDocuments[] = [
            'document' => $document,
            'locale' => $locale,
        ];
    }

    public function onRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof StructureBehavior
            || !$document instanceof UuidBehavior
        ) {
            return;
        }

        $this->removeDocuments[] = [
            'document' => $document,
            'locale' => null,
        ];
    }

    public function onRemoveLocale(RemoveLocaleEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof StructureBehavior
            || !$document instanceof UuidBehavior
        ) {
            return;
        }

        $this->removeDocuments[] = [
            'document' => $document,
            'locale' => $event->getLocale(),
        ];
    }

    public function onClear(ClearEvent $event): void
    {
        $this->reset();
    }

    public function onFlush(FlushEvent $event): void
    {
        foreach ($this->persistDocuments as $documentData) {
            $this->getProvider($documentData['document'])?->updateReferences(
                $documentData['document'],
                $documentData['locale'],
                SuluKernel::CONTEXT_ADMIN,
            );
        }

        foreach ($this->unpublishDocuments as $documentData) {
            $this->getProvider($documentData['document'])?->removeReferences(
                $documentData['document'],
                $documentData['locale'],
                SuluKernel::CONTEXT_WEBSITE,
            );
        }

        foreach ($this->publishDocuments as $documentData) {
            $this->getProvider($documentData['document'])?->updateReferences(
                $documentData['document'],
                $documentData['locale'],
                SuluKernel::CONTEXT_WEBSITE,
            );
        }

        $this->referenceRepository->flush();

        foreach ($this->removeDocuments as $documentData) {
            $this->getProvider($documentData['document'])?->removeReferences(
                $documentData['document'],
                $documentData['locale'],
                SuluKernel::CONTEXT_ADMIN,
            );
            $this->getProvider($documentData['document'])?->removeReferences(
                $documentData['document'],
                $documentData['locale'],
                SuluKernel::CONTEXT_WEBSITE,
            );
        }

        $this->referenceRepository->flush();

        $this->reset();
    }

    private function getProvider(StructureBehavior $document): ?DocumentReferenceProviderInterface
    {
        $documentResourcesKey = \defined(\get_class($document) . '::RESOURCE_KEY')
            // @phpstan-ignore-next-line PHPStan does not detect the `defined` call
            ? $document::RESOURCE_KEY
            : '';

        return $this->documentReferenceProviders[$documentResourcesKey] ?? null;
    }

    public function reset(): void
    {
        $this->persistDocuments = [];
        $this->publishDocuments = [];
        $this->unpublishDocuments = [];
        $this->removeDocuments = [];
    }
}
