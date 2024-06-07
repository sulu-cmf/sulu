<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index;

use ONGR\ElasticsearchBundle\Collection\Collection;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageViewObject;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Index\Factory\ExcerptFactory;
use Sulu\Bundle\ArticleBundle\Document\Index\Factory\SeoFactory;
use Sulu\Bundle\ArticleBundle\Document\LocalizationStateViewObject;
use Sulu\Bundle\ArticleBundle\Document\Resolver\WebspaceResolver;
use Sulu\Bundle\ArticleBundle\Event\IndexEvent;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\RouteBundle\PageTree\PageTreeTrait;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides methods to index articles.
 */
class ArticleIndexer implements IndexerInterface
{
    use ArticleViewDocumentIdTrait;
    use PageTreeTrait;
    use StructureTagTrait;

    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $structureMetadataFactory;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * @var DocumentFactoryInterface
     */
    protected $documentFactory;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var ExcerptFactory
     */
    protected $excerptFactory;

    /**
     * @var SeoFactory
     */
    protected $seoFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var DocumentInspector
     */
    protected $inspector;

    /**
     * @var WebspaceResolver
     */
    protected $webspaceResolver;

    /**
     * @var array
     */
    protected $typeConfiguration;

    public function __construct(
        StructureMetadataFactoryInterface $structureMetadataFactory,
        UserManager $userManager,
        ContactRepository $contactRepository,
        DocumentFactoryInterface $documentFactory,
        Manager $manager,
        ExcerptFactory $excerptFactory,
        SeoFactory $seoFactory,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        DocumentManagerInterface $documentManager,
        DocumentInspector $inspector,
        WebspaceResolver $webspaceResolver,
        array $typeConfiguration
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->userManager = $userManager;
        $this->contactRepository = $contactRepository;
        $this->documentFactory = $documentFactory;
        $this->manager = $manager;
        $this->excerptFactory = $excerptFactory;
        $this->seoFactory = $seoFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->documentManager = $documentManager;
        $this->inspector = $inspector;
        $this->webspaceResolver = $webspaceResolver;
        $this->typeConfiguration = $typeConfiguration;
    }

    /**
     * Returns translation for given article type.
     */
    private function getTypeTranslation(string $type): string
    {
        if (!\array_key_exists($type, $this->typeConfiguration)) {
            return \ucfirst($type);
        }

        $typeTranslationKey = $this->typeConfiguration[$type]['translation_key'];

        return $this->translator->trans($typeTranslationKey, [], 'admin');
    }

    protected function dispatchIndexEvent(ArticleDocument $document, ArticleViewDocumentInterface $article): void
    {
        $this->eventDispatcher->dispatch(new IndexEvent($document, $article), IndexEvent::NAME);
    }

    protected function createOrUpdateArticle(
        ArticleDocument $document,
        string $locale,
        string $localizationState = LocalizationState::LOCALIZED
    ): ?ArticleViewDocumentInterface {
        $article = $this->findOrCreateViewDocument($document, $locale, $localizationState);
        if (!$article) {
            return null;
        }

        $structureMetadata = $this->structureMetadataFactory->getStructureMetadata(
            'article',
            $document->getStructureType()
        );

        $article->setTitle($document->getTitle());
        $article->setRoutePath($document->getRoutePath());
        $this->setParentPageUuid($document, $article);
        $article->setChanged($document->getChanged());
        $article->setCreated($document->getCreated());
        $article->setLastModified($document->getLastModified());
        $article->setAuthored($document->getAuthored());
        if ($document->getAuthor() && $author = $this->contactRepository->find($document->getAuthor())) {
            $article->setAuthorId($author->getId());

            if ($author instanceof Contact) {
                $article->setAuthorFullName($author->getFullName());
            }
        }
        if ($document->getChanger() && $changer = $this->userManager->getUserById($document->getChanger())) {
            $article->setChangerFullName($changer->getFullName());

            if ($changer instanceof User) {
                $article->setChangerContactId($changer->getContact()->getId());
            }
        }
        if ($document->getCreator() && $creator = $this->userManager->getUserById($document->getCreator())) {
            $article->setCreatorFullName($creator->getFullName());

            if ($creator instanceof User) {
                $article->setCreatorContactId($creator->getContact()->getId());
            }
        }
        $article->setType($this->getType($structureMetadata));
        $article->setStructureType($document->getStructureType());
        $article->setPublished($document->getPublished());
        $article->setPublishedState(WorkflowStage::PUBLISHED === $document->getWorkflowStage());
        $article->setTypeTranslation($this->getTypeTranslation($this->getType($structureMetadata)));
        $article->setLocalizationState(
            new LocalizationStateViewObject(
                $localizationState,
                (LocalizationState::LOCALIZED === $localizationState) ? null : $document->getLocale()
            )
        );

        $extensions = $document->getExtensionsData();

        if ($extensions instanceof ExtensionContainer) {
            $extensions = $extensions->toArray();
        }

        if (\array_key_exists('excerpt', $extensions)) {
            $article->setExcerpt($this->excerptFactory->create($extensions['excerpt'], $document->getLocale()));
        }
        if (\array_key_exists('seo', $extensions)) {
            $article->setSeo($this->seoFactory->create($extensions['seo']));
        }
        if ($structureMetadata->hasPropertyWithTagName('sulu.teaser.description')) {
            $descriptionProperty = $structureMetadata->getPropertyByTagName('sulu.teaser.description');
            $article->setTeaserDescription(
                $document->getStructure()->getProperty($descriptionProperty->getName())->getValue()
            );
        }
        if ($structureMetadata->hasPropertyWithTagName('sulu.teaser.media')) {
            $mediaProperty = $structureMetadata->getPropertyByTagName('sulu.teaser.media');
            $mediaData = $document->getStructure()->getProperty($mediaProperty->getName())->getValue();
            if (null !== $mediaData && \array_key_exists('ids', $mediaData)) {
                $article->setTeaserMediaId(\reset($mediaData['ids']) ?: null);
            }
        }

        $article->setContentFields($this->getContentFields($structureMetadata, $document));
        $article->setContentData(\json_encode($document->getStructure()->toArray()));

        $article->setMainWebspace($this->webspaceResolver->resolveMainWebspace($document));
        $article->setAdditionalWebspaces($this->webspaceResolver->resolveAdditionalWebspaces($document));

        $this->mapPages($document, $article);

        return $article;
    }

    protected function getContentFields(StructureMetadata $structure, ArticleDocument $document)
    {
        $tag = 'sulu.search.field';
        $contentFields = [];
        foreach ($structure->getProperties() as $property) {
            if (\method_exists($property, 'getComponents') && \count($property->getComponents()) > 0) {
                $blocks = $document->getStructure()->getProperty($property->getName())->getValue();
                if (isset($blocks['hotspots'])) {
                    $blocks = $blocks['hotspots'];
                }
                $contentFields = \array_merge($contentFields, $this->getBlockContentFieldsRecursive($blocks, $document, $property, $tag));
            } elseif ($property->hasTag($tag)) {
                $value = $document->getStructure()->getProperty($property->getName())->getValue();
                if (\is_string($value) && '' !== $value) {
                    $contentFields[] = \strip_tags($value);
                }
            }
        }

        return $contentFields;
    }

    /**
     * @return string[]
     */
    private function getBlockContentFieldsRecursive(array $blocks, ArticleDocument $document, $blockMetaData, $tag)
    {
        $contentFields = [];
        foreach ($blockMetaData->getComponents() as $component) {
            /** @var PropertyMetadata $componentProperty */
            foreach ($component->getChildren() as $componentProperty) {
                if (\method_exists($componentProperty, 'getComponents') && \count($componentProperty->getComponents()) > 0) {
                    $filteredBlocks = \array_filter($blocks, function($block) use ($component) {
                        return $block['type'] === $component->getName();
                    });

                    foreach ($filteredBlocks as $filteredBlock) {
                        if (isset($filteredBlock['hotspots'])) {
                            $filteredBlock = $filteredBlock['hotspots'];
                        }
                        $contentFields = \array_merge(
                            $contentFields,
                            $this->getBlockContentFieldsRecursive(
                                $filteredBlock[$componentProperty->getName()],
                                $document,
                                $componentProperty,
                                $tag
                            )
                        );
                    }
                }

                if (false === $componentProperty->hasTag($tag)) {
                    continue;
                }

                foreach ($blocks as $block) {
                    if ($block['type'] === $component->getName()) {
                        $blockValue = $block[$componentProperty->getName()];
                        if (\is_string($blockValue) && '' !== $blockValue) {
                            $contentFields[] = \strip_tags($blockValue);
                        }
                    }
                }
            }
        }

        return $contentFields;
    }

    protected function findViewDocument(ArticleDocument $document, string $locale): ?ArticleViewDocumentInterface
    {
        $articleId = $this->getViewDocumentId($document->getUuid(), $locale);
        /** @var ArticleViewDocumentInterface $article */
        $article = $this->manager->find($this->documentFactory->getClass('article'), $articleId);

        return $article;
    }

    /**
     * Returns view-document from index or create a new one.
     */
    protected function findOrCreateViewDocument(
        ArticleDocument $document,
        string $locale,
        string $localizationState
    ): ?ArticleViewDocumentInterface {
        $article = $this->findViewDocument($document, $locale);

        if ($article) {
            // Only index ghosts when the article isn't a ghost himself.
            if (LocalizationState::GHOST === $localizationState
                && LocalizationState::GHOST !== $article->getLocalizationState()->state
            ) {
                return null;
            }

            return $article;
        }

        $article = $this->documentFactory->create('article');
        $article->setId($this->getViewDocumentId($document->getUuid(), $locale));
        $article->setUuid($document->getUuid());
        $article->setLocale($locale);

        return $article;
    }

    /**
     * Maps pages from document to view-document.
     */
    private function mapPages(ArticleDocument $document, ArticleViewDocumentInterface $article): void
    {
        $pages = [];
        /** @var ArticlePageDocument $child */
        foreach ($document->getChildren() as $child) {
            if (!$child instanceof ArticlePageDocument) {
                continue;
            }

            /** @var ArticlePageViewObject $page */
            $page = $this->documentFactory->create('article_page');
            $page->uuid = $child->getUuid();
            $page->pageNumber = $child->getPageNumber();
            $page->title = $child->getPageTitle();
            $page->routePath = $child->getRoutePath();
            $page->contentData = \json_encode($child->getStructure()->toArray());

            $pages[] = $page;
        }

        $article->setPages(new Collection($pages));
    }

    /**
     * Set parent-page-uuid to view-document.
     */
    private function setParentPageUuid(ArticleDocument $document, ArticleViewDocumentInterface $article): void
    {
        $parentPageUuid = $this->getParentPageUuidFromPageTree($document);

        if (!$parentPageUuid) {
            return;
        }

        $article->setParentPageUuid($parentPageUuid);
    }

    protected function removeArticle(string $id): void
    {
        $article = $this->manager->find(
            $this->documentFactory->getClass('article'),
            $id
        );
        if (null === $article) {
            return;
        }

        $this->manager->remove($article);
    }

    public function remove(ArticleDocument $document/*, ?string $locale = null*/): void
    {
        $locale = \func_num_args() >= 2 ? \func_get_arg(1) : null;

        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));
        $search = $repository->createSearch()
            ->addQuery(new TermQuery('uuid', $document->getUuid()))
            ->setSize(1000);

        if ($locale) {
            $search->addQuery(new TermQuery('locale', $locale));
        }

        foreach ($repository->findDocuments($search) as $viewDocument) {
            $this->manager->remove($viewDocument);
        }
    }

    /**
     * @deprecated
     * @see ArticleIndexer::replaceWithGhostData
     */
    public function removeLocale(ArticleDocument $document, string $locale): void
    {
        @\trigger_error('Calling ArticleIndexer::removeLocale() is deprecated and will be removed in future. Use ArticleIndexer::replaceWithGhostData() instead.', \E_USER_DEPRECATED);

        $this->replaceWithGhostData($document, $locale);
    }

    public function replaceWithGhostData(ArticleDocument $document, string $locale): void
    {
        // overwrite removed locale with properties from original locale
        $article = $this->createOrUpdateArticle($document, $locale);
        $article->setLocalizationState(new LocalizationStateViewObject(LocalizationState::GHOST, $document->getOriginalLocale()));

        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));
        $search = $repository->createSearch();
        $search->addQuery(new TermQuery('localization_state.state', 'ghost'), BoolQuery::MUST);
        $search->addQuery(new TermQuery('localization_state.locale', $locale), BoolQuery::MUST);

        /** @var array<array{locale: string}> $searchResult */
        $searchResult = $repository->findArray($search);
        foreach ($searchResult as $result) {
            $this->replaceWithGhostData($document, $result['locale']);
        }

        $this->manager->persist($article);
    }

    public function flush(): void
    {
        $this->manager->commit();
    }

    public function clear(): void
    {
        $pageSize = 500;
        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));
        $search = $repository->createSearch()
            ->addQuery(new MatchAllQuery())
            ->setSize($pageSize);

        do {
            $result = $repository->findDocuments($search);
            foreach ($result as $document) {
                $this->manager->remove($document);
            }

            $this->manager->commit();
        } while (0 !== $result->count());

        $this->manager->clearCache();
        $this->manager->flush();
    }

    public function setUnpublished(string $uuid, string $locale): ?ArticleViewDocumentInterface
    {
        $articleId = $this->getViewDocumentId($uuid, $locale);
        /** @var ArticleViewDocumentInterface|null $article */
        $article = $this->manager->find($this->documentFactory->getClass('article'), $articleId);
        if (!$article) {
            return null;
        }

        $article->setPublished(null);
        $article->setPublishedState(false);

        $this->manager->persist($article);

        return $article;
    }

    public function index(ArticleDocument $document): void
    {
        if ($document->isShadowLocaleEnabled()) {
            $this->indexShadow($document);

            return;
        }

        $article = $this->createOrUpdateArticle($document, $document->getLocale());

        $this->dispatchIndexEvent($document, $article);
        $this->manager->persist($article);

        $this->updateShadows($document);
    }

    protected function indexShadow(ArticleDocument $document): void
    {
        /** @var ArticleDocument $shadowDocument */
        $shadowDocument = $this->documentManager->find(
            $document->getUuid(),
            $document->getOriginalLocale(),
            [
                'rehydrate' => true,
            ]
        );

        $article = $this->createOrUpdateArticle($shadowDocument, $document->getOriginalLocale(), LocalizationState::SHADOW);
        $this->dispatchIndexEvent($shadowDocument, $article);
        $this->manager->persist($article);
    }

    protected function updateShadows(ArticleDocument $document): void
    {
        if ($document->isShadowLocaleEnabled()) {
            return;
        }

        foreach (\array_keys($this->inspector->getShadowLocales($document)) as $shadowLocale) {
            try {
                /** @var ArticleDocument $shadowDocument */
                $shadowDocument = $this->documentManager->find($document->getUuid(), $shadowLocale);

                // update shadow only if original document exists
                if (!$this->findViewDocument($shadowDocument, $document->getLocale())) {
                    continue;
                }

                $this->indexShadow($shadowDocument);
            } catch (DocumentManagerException $documentManagerException) {
                // @ignoreException
                // do nothing
            }
        }
    }

    public function dropIndex(): void
    {
        if (!$this->manager->indexExists()) {
            return;
        }

        $this->manager->dropIndex();
    }

    public function createIndex(): void
    {
        if ($this->manager->indexExists()) {
            return;
        }

        $this->manager->createIndex();
    }

    protected function getDocumentInspector()
    {
        return $this->inspector;
    }
}
