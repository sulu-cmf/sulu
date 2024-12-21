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

namespace Sulu\Bundle\SnippetBundle\Trash;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetRestoredEvent;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Webmozart\Assert\Assert;

final class SnippetTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    public function __construct(
        private TrashItemRepositoryInterface $trashItemRepository,
        private DocumentManagerInterface $documentManager,
        private DocumentInspector $documentInspector,
        private DocumentDomainEventCollectorInterface $documentDomainEventCollector,
    ) {
    }

    /**
     * @param SnippetDocument $snippet
     */
    public function store(object $snippet, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($snippet, SnippetDocument::class);

        $snipetTitles = [];
        $data = [
            'locales' => [],
        ];

        /** @var string $locale */
        foreach ($this->documentInspector->getLocales($snippet) as $locale) {
            /** @var SnippetDocument $localizedSnippet */
            $localizedSnippet = $this->documentManager->find($snippet->getUuid(), $locale);

            $extensionsData = ($localizedSnippet->getExtensionsData() instanceof ExtensionContainer)
                ? $data['extensionsData'] = $localizedSnippet->getExtensionsData()->toArray()
                : $data['extensionsData'] = $localizedSnippet->getExtensionsData();

            $snipetTitles[$locale] = $localizedSnippet->getTitle();

            $data['locales'][] = [
                'title' => $localizedSnippet->getTitle(),
                'locale' => $locale,
                'creator' => $localizedSnippet->getCreator(),
                'created' => $localizedSnippet->getCreated()->format('c'),
                'structureType' => $localizedSnippet->getStructureType(),
                'structureData' => $localizedSnippet->getStructure()->toArray(),
                'extensionsData' => $extensionsData,
            ];
        }

        return $this->trashItemRepository->create(
            SnippetDocument::RESOURCE_KEY,
            (string) $snippet->getUuid(),
            $snipetTitles,
            $data,
            null,
            $options,
            SnippetAdmin::SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $uuid = $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();
        $localizedSnippet = null;

        foreach ($data['locales'] as $localeData) {
            $locale = $localeData['locale'];

            try {
                /** @var SnippetDocument $localizedSnippet */
                $localizedSnippet = $this->documentManager->find($uuid, $locale, ['load_ghost_content' => false]);
            } catch (DocumentNotFoundException $exception) {
                /** @var SnippetDocument $localizedSnippet */
                $localizedSnippet = $this->documentManager->create(Structure::TYPE_SNIPPET);

                $localizedSnippetAccessor = new DocumentAccessor($localizedSnippet);
                $localizedSnippetAccessor->set('uuid', $uuid);
            }

            $localizedSnippet->setTitle($localeData['title']);
            $localizedSnippet->setLocale($locale);
            $localizedSnippet->setCreator($localeData['creator']);
            $localizedSnippet->setCreated(new \DateTime($localeData['created']));
            $localizedSnippet->setStructureType((string) $localeData['structureType']);
            $localizedSnippet->getStructure()->bind($localeData['structureData']);
            $localizedSnippet->setExtensionsData($localeData['extensionsData']);

            $this->documentManager->persist($localizedSnippet, $locale, ['omit_modified_domain_event' => true]);
            $this->documentManager->publish($localizedSnippet, $locale);
        }

        Assert::isInstanceOf($localizedSnippet, SnippetDocument::class);
        $this->documentDomainEventCollector->collect(new SnippetRestoredEvent($localizedSnippet, $data));
        $this->documentManager->flush();

        return $localizedSnippet;
    }

    public static function getResourceKey(): string
    {
        return SnippetDocument::RESOURCE_KEY;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            null,
            SnippetAdmin::EDIT_FORM_VIEW,
            ['id' => 'id']
        );
    }
}
