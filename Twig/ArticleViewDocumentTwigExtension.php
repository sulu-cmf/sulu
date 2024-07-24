<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Twig;

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItem;
use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItemFactory;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Document\Repository\ArticleViewDocumentRepository;
use Sulu\Bundle\ArticleBundle\Exception\ArticleInRequestNotFoundException;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension to retrieve article resource items from the article view document repository.
 */
class ArticleViewDocumentTwigExtension extends AbstractExtension
{
    use StructureTagTrait;

    /**
     * @var ArticleViewDocumentRepository
     */
    protected $articleViewDocumentRepository;

    /**
     * @var ArticleResourceItemFactory
     */
    protected $articleResourceItemFactory;

    /**
     * @var ReferenceStoreInterface
     */
    protected $referenceStore;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $structureMetadataFactory;

    public function __construct(
        ArticleViewDocumentRepository $articleViewDocumentRepository,
        ArticleResourceItemFactory $articleResourceItemFactory,
        ReferenceStoreInterface $referenceStore,
        StructureMetadataFactoryInterface $structureMetadataFactory,
        RequestStack $requestStack
    ) {
        $this->articleViewDocumentRepository = $articleViewDocumentRepository;
        $this->articleResourceItemFactory = $articleResourceItemFactory;
        $this->referenceStore = $referenceStore;
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     *
     * Returns an array of possible function in this extension.
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_article_load_recent', [$this, 'loadRecent']),
            new TwigFunction('sulu_article_load_similar', [$this, 'loadSimilar']),
        ];
    }

    /**
     * Loads recent articles with given parameters.
     *
     * @return ArticleResourceItem[]
     */
    public function loadRecent(
        int $limit = ArticleViewDocumentRepository::DEFAULT_LIMIT,
        ?array $types = null,
        ?string $locale = null,
        bool $ignoreWebspaces = false
    ): array {
        $excludeUuid = null;

        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        $articleDocument = $request->get('object');
        if ($articleDocument instanceof ArticleDocument) {
            $excludeUuid = $articleDocument->getUuid();

            if (!$types) {
                $types = [$this->getArticleType($articleDocument)];
            }
        }

        if (!$locale) {
            $locale = $request->getLocale();
        }

        $webspaceKey = $this->getWebspaceKey($request, $ignoreWebspaces);

        $articleViewDocuments = $this->articleViewDocumentRepository->findRecent(
            $excludeUuid,
            $limit,
            $types,
            $locale,
            $webspaceKey
        );

        return $this->getResourceItems($articleViewDocuments);
    }

    /**
     * Loads similar articles with given parameters.
     *
     * @return ArticleResourceItem[]
     *
     * @throws ArticleInRequestNotFoundException
     */
    public function loadSimilar(
        int $limit = ArticleViewDocumentRepository::DEFAULT_LIMIT,
        ?array $types = null,
        ?string $locale = null,
        bool $ignoreWebspaces = false
    ): array {
        $uuid = null;

        $request = $this->requestStack->getCurrentRequest();

        $articleDocument = $request->get('object');
        if ($articleDocument instanceof ArticleDocument) {
            $uuid = $articleDocument->getUuid();

            if (!$types) {
                $types = [$this->getArticleType($articleDocument)];
            }
        }

        if (!$uuid) {
            throw new ArticleInRequestNotFoundException();
        }

        if (!$locale) {
            $locale = $request->getLocale();
        }

        $webspaceKey = $this->getWebspaceKey($request, $ignoreWebspaces);

        $articleViewDocuments = $this->articleViewDocumentRepository->findSimilar(
            $uuid,
            $limit,
            $types,
            $locale,
            $webspaceKey
        );

        return $this->getResourceItems($articleViewDocuments);
    }

    public function getName(): string
    {
        return 'sulu_article.article_view_document';
    }

    /**
     * @return ArticleResourceItem[]
     */
    private function getResourceItems(DocumentIterator $articleViewDocuments): array
    {
        $articleResourceItems = [];

        /** @var ArticleViewDocument $articleViewDocument */
        foreach ($articleViewDocuments as $articleViewDocument) {
            $this->referenceStore->add($articleViewDocument->getUuid());
            $articleResourceItems[] = $this->articleResourceItemFactory->createResourceItem($articleViewDocument);
        }

        return $articleResourceItems;
    }

    private function getArticleType(ArticleDocument $articleDocument): string
    {
        $structureMetadata = $this->structureMetadataFactory->getStructureMetadata(
            'article',
            $articleDocument->getStructureType()
        );

        return $this->getType($structureMetadata);
    }

    private function getWebspaceKey(Request $request, bool $ignoreWebspaces): ?string
    {
        if ($ignoreWebspaces) {
            return null;
        }

        $sulu = $request->get('_sulu');
        if (!$sulu) {
            return null;
        }

        /** @var Webspace $webspace */
        $webspace = $sulu->getAttribute('webspace');
        if (!$webspace) {
            return null;
        }

        return $webspace->getKey();
    }
}
