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

namespace Sulu\Bundle\PageBundle\Trash;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Domain\Event\PageRestoredEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageTranslationRestoredEvent;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Webmozart\Assert\Assert;

final class PageTrashItemHandler implements
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
     * @param PageDocument $page
     */
    public function store(object $page, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($page, BasePageDocument::class);

        $pageTitles = [];
        $data = [
            'locales' => [],
        ];

        if ($page instanceof PageDocument) {
            /** @var BasePageDocument $parent */
            $parent = $page->getParent();
            $data['parentUuid'] = $parent->getUuid();
        }

        $restoreType = isset($options['locale']) ? 'translation' : null;
        $locales = isset($options['locale']) ? [$options['locale']] : $this->documentInspector->getLocales($page);

        /** @var string $locale */
        foreach ($locales as $locale) {
            /** @var BasePageDocument $localizedPage */
            $localizedPage = $this->documentManager->find($page->getUuid(), $locale);
            /** @var BasePageDocument|null $redirectTarget */
            $redirectTarget = $localizedPage->getRedirectTarget();

            $extensionsData = ($localizedPage->getExtensionsData() instanceof ExtensionContainer)
                ? $localizedPage->getExtensionsData()->toArray()
                : $localizedPage->getExtensionsData();

            $pageTitles[$locale] = $localizedPage->getTitle();
            $lastModified = $localizedPage->getLastModified() ? $localizedPage->getLastModified()->format('c') : null;

            $data['locales'][] = [
                'title' => $localizedPage->getTitle(),
                'locale' => $locale,
                'creator' => $localizedPage->getCreator(),
                'created' => $localizedPage->getCreated()->format('c'),
                'author' => $localizedPage->getAuthor(),
                'lastModified' => $lastModified,
                'authored' => $localizedPage->getAuthored()->format('c'),
                'structureType' => $localizedPage->getStructureType(),
                'structureData' => $localizedPage->getStructure()->toArray(),
                'extensionsData' => $extensionsData,
                'permissions' => $localizedPage->getPermissions(),
                'navigationContexts' => $localizedPage->getNavigationContexts(),
                'shadowLocaleEnabled' => $localizedPage->isShadowLocaleEnabled(),
                'shadowLocale' => $localizedPage->getShadowLocale(),
                'redirectType' => $localizedPage->getRedirectType(),
                'redirectExternal' => $localizedPage->getRedirectExternal(),
                'redirectTargetUuid' => $redirectTarget ? $redirectTarget->getUUid() : null,
                'resourceSegment' => $localizedPage->getResourceSegment(),
                'suluOrder' => $page->getSuluOrder(),
            ];
        }

        return $this->trashItemRepository->create(
            BasePageDocument::RESOURCE_KEY,
            (string) $page->getUuid(),
            $pageTitles,
            $data,
            $restoreType,
            $options,
            PageAdmin::getPageSecurityContext($page->getWebspaceName()),
            SecurityBehavior::class,
            (string) $page->getUuid(),
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $uuid = $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();
        $parentUuid = $restoreFormData['parentUuid'];
        $localizedPage = null;

        // restore shadow locales after concrete locales because shadow locales depend on their target locale
        $sortedLocales = [];
        /** @var array<string, string|int|bool> $localeData */
        foreach ($data['locales'] as $localeData) {
            if ($localeData['shadowLocaleEnabled']) {
                $sortedLocales[] = $localeData;
            } else {
                \array_unshift($sortedLocales, $localeData);
            }
        }

        /** @var array{
         *     title: string,
         *     resourceSegment: string,
         *     suluOrder: int,
         *     locale: string,
         *     creator: ?int,
         *     created: string,
         *     lastModified: ?string,
         *     author: ?int,
         *     authored: string,
         *     structureType: string,
         *     structureData: mixed,
         *     extensionsData: mixed,
         *     permissions: ?mixed,
         *     navigationContexts: array<string>,
         *     shadowLocaleEnabled: bool,
         *     shadowLocale: ?string,
         *     redirectType: int,
         *     redirectExternal: ?string,
         *     redirectTargetUuid: ?string,
         * } $localeData
         */
        foreach ($sortedLocales as $localeData) {
            $locale = $localeData['locale'];

            try {
                /** @var PageDocument $localizedPage */
                $localizedPage = $this->documentManager->find($uuid, $locale, ['load_ghost_content' => false]);
            } catch (DocumentNotFoundException $exception) {
                /** @var PageDocument $localizedPage */
                $localizedPage = $this->documentManager->create(Structure::TYPE_PAGE);
                $localizedPage->setParent($this->documentManager->find($parentUuid));

                $localizedPageAccessor = new DocumentAccessor($localizedPage);
                $localizedPageAccessor->set('uuid', $uuid);
            }

            $lastModified = $localeData['lastModified'] ? new \DateTime($localeData['lastModified']) : null;

            $localizedPage->setTitle($localeData['title']);
            $localizedPage->setResourceSegment($localeData['resourceSegment']);
            $localizedPage->setSuluOrder($localeData['suluOrder']);
            $localizedPage->setLocale($locale);
            $localizedPage->setCreator($localeData['creator']);
            $localizedPage->setCreated(new \DateTime($localeData['created']));
            $localizedPage->setLastModified($lastModified);
            $localizedPage->setAuthor($localeData['author']);
            $localizedPage->setAuthored(new \DateTime($localeData['authored']));
            $localizedPage->setStructureType($localeData['structureType']);
            $localizedPage->getStructure()->bind($localeData['structureData']);
            $localizedPage->setExtensionsData($localeData['extensionsData']);
            $localizedPage->setPermissions($localeData['permissions'] ?: []);
            $localizedPage->setNavigationContexts($localeData['navigationContexts']);
            $localizedPage->setShadowLocaleEnabled($localeData['shadowLocaleEnabled']);
            $localizedPage->setShadowLocale($localeData['shadowLocale']);
            $localizedPage->setRedirectType($localeData['redirectType']);
            $localizedPage->setRedirectExternal($localeData['redirectExternal']);

            if ($localeData['redirectTargetUuid']) {
                $localizedPage->setRedirectTarget($this->documentManager->find($localeData['redirectTargetUuid']));
            }

            $this->documentManager->persist($localizedPage, $locale, ['omit_modified_domain_event' => true]);
        }

        Assert::isInstanceOf($localizedPage, PageDocument::class);
        $event = 'translation' === $trashItem->getRestoreType()
            ? new PageTranslationRestoredEvent($localizedPage, $trashItem->getRestoreOptions()['locale'], $data)
            : new PageRestoredEvent($localizedPage, $data);
        $this->documentDomainEventCollector->collect($event);
        $this->documentManager->flush();

        return $localizedPage;
    }

    public static function getResourceKey(): string
    {
        return BasePageDocument::RESOURCE_KEY;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            'restore_page',
            PageAdmin::EDIT_FORM_VIEW,
            ['id' => 'id', 'webspace' => 'webspace'],
            ['defaultPage'],
        );
    }
}
