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

namespace Sulu\Bundle\ArticleBundle\Trash;

use Sulu\Bundle\ArticleBundle\Admin\ArticleAdmin;
use Sulu\Bundle\ArticleBundle\Controller\ArticleController;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleRestoredEvent;
use Sulu\Bundle\ArticleBundle\Domain\Event\ArticleTranslationRestoredEvent;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Webmozart\Assert\Assert;

final class ArticleTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    /**
     * @var TrashItemRepositoryInterface
     */
    private $trashItemRepository;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var DocumentDomainEventCollectorInterface
     */
    private $documentDomainEventCollector;

    public function __construct(
        TrashItemRepositoryInterface $trashItemRepository,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        DocumentDomainEventCollectorInterface $documentDomainEventCollector
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->documentDomainEventCollector = $documentDomainEventCollector;
    }

    /**
     * @param ArticleDocument $article
     */
    public function store(object $article, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($article, ArticleDocument::class);

        $articleTitles = [];
        $data = [
            'parentUuid' => $article->getParent()->getUuid(),
            'locales' => [],
        ];

        $restoreType = isset($options['locale']) ? 'translation' : null;
        $locales = isset($options['locale']) ? [$options['locale']] : $this->documentInspector->getLocales($article);

        /** @var string $locale */
        foreach ($locales as $locale) {
            /** @var ArticleDocument $localizedArticle */
            $localizedArticle = $this->documentManager->find($article->getUuid(), $locale);

            $extensionsData = ($localizedArticle->getExtensionsData() instanceof ExtensionContainer)
                ? $localizedArticle->getExtensionsData()->toArray()
                : $localizedArticle->getExtensionsData();

            // routePath property of structure contains route of target locale in case of a shadow page
            // we want to restore the path of the source locale, therefore we use the value of the document
            $structureData = $localizedArticle->getStructure()->toArray();

            $articleTitles[$locale] = $localizedArticle->getTitle();
            $lastModified = $localizedArticle->getLastModified() ? $localizedArticle->getLastModified()->format('c') : null;

            $data['locales'][] = [
                'title' => $localizedArticle->getTitle(),
                'locale' => $locale,
                'creator' => $localizedArticle->getCreator(),
                'created' => $localizedArticle->getCreated()->format('c'),
                'lastModified' => $lastModified,
                'author' => $localizedArticle->getAuthor(),
                'authored' => $localizedArticle->getAuthored()->format('c'),
                'structureType' => $localizedArticle->getStructureType(),
                'structureData' => $structureData,
                'extensionsData' => $extensionsData,
                'shadowLocaleEnabled' => $localizedArticle->isShadowLocaleEnabled(),
                'shadowLocale' => $localizedArticle->getShadowLocale(),
                'mainWebspace' => $localizedArticle->getMainWebspace(),
                'additionalWebspaces' => $localizedArticle->getAdditionalWebspaces(),
            ];
        }

        return $this->trashItemRepository->create(
            ArticleDocument::RESOURCE_KEY,
            (string) $article->getUuid(),
            $articleTitles,
            $data,
            $restoreType,
            $options,
            ArticleAdmin::SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $uuid = $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();
        $localizedArticle = null;

        // restore shadow locales after concrete locales because shadow locales depend on their target locale
        $sortedLocales = [];
        foreach ($data['locales'] as $localeData) {
            if ($localeData['shadowLocaleEnabled']) {
                $sortedLocales[] = $localeData;
            } else {
                \array_unshift($sortedLocales, $localeData);
            }
        }

        /** @var array{
         *     title: string,
         *     locale: string,
         *     creator: ?int,
         *     created: string,
         *     lastModified: ?string,
         *     author: ?int,
         *     authored: string,
         *     structureType: string,
         *     structureData: mixed,
         *     extensionsData: mixed,
         *     shadowLocaleEnabled: bool,
         *     shadowLocale: ?string,
         *     mainWebspace: ?string,
         *     additionalWebspaces: ?array<string>,
         * } $localeData
         */
        foreach ($sortedLocales as $localeData) {
            $locale = $localeData['locale'];

            try {
                /** @var ArticleDocument $localizedArticle */
                $localizedArticle = $this->documentManager->find($uuid, $locale, ['load_ghost_content' => false]);
            } catch (DocumentNotFoundException $exception) {
                /** @var ArticleDocument $localizedArticle */
                $localizedArticle = $this->documentManager->create(ArticleController::DOCUMENT_TYPE);
                $localizedArticle->setParent($this->documentManager->find($data['parentUuid']));
                $localizedArticle->setUuid($uuid);
            }
            $lastModified = \array_key_exists('lastModified', $localeData) && $localeData['lastModified'] ? new \DateTime($localeData['lastModified']) : null;

            $localizedArticle->setTitle($localeData['title']);
            $localizedArticle->setLocale($locale);
            $localizedArticle->setCreator($localeData['creator']);
            $localizedArticle->setCreated(new \DateTime($localeData['created']));
            $localizedArticle->setLastModified($lastModified);
            $localizedArticle->setAuthor($localeData['author']);
            $localizedArticle->setAuthored(new \DateTime($localeData['authored']));
            $localizedArticle->setStructureType($localeData['structureType']);
            $localizedArticle->getStructure()->bind($localeData['structureData']);
            $localizedArticle->setExtensionsData($localeData['extensionsData']);
            $localizedArticle->setShadowLocaleEnabled($localeData['shadowLocaleEnabled']);
            $localizedArticle->setShadowLocale($localeData['shadowLocale']);
            $localizedArticle->setMainWebspace($localeData['mainWebspace']);
            $localizedArticle->setAdditionalWebspaces($localeData['additionalWebspaces']);

            $this->documentManager->persist($localizedArticle, $locale, ['omit_modified_domain_event' => true]);
        }

        Assert::isInstanceOf($localizedArticle, ArticleDocument::class);
        $event = 'translation' === $trashItem->getRestoreType()
            ? new ArticleTranslationRestoredEvent($localizedArticle, $trashItem->getRestoreOptions()['locale'], $data)
            : new ArticleRestoredEvent($localizedArticle, $data);
        $this->documentDomainEventCollector->collect($event);
        $this->documentManager->flush();

        return $localizedArticle;
    }

    public static function getResourceKey(): string
    {
        return ArticleDocument::RESOURCE_KEY;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            null,
            ArticleAdmin::LIST_VIEW,
        );
    }
}
