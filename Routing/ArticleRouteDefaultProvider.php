<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Routing;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Behavior\WebspaceBehavior;
use Sulu\Bundle\ArticleBundle\Document\Resolver\WebspaceResolver;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;

/**
 * Provides route-defaults for articles.
 */
class ArticleRouteDefaultProvider implements RouteDefaultsProviderInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var WebspaceResolver
     */
    private $webspaceResolver;

    /**
     * @var RequestAnalyzer
     */
    private $requestAnalyzer;

    public function __construct(
        DocumentManagerInterface $documentManager,
        StructureMetadataFactoryInterface $structureMetadataFactory,
        CacheLifetimeResolverInterface $cacheLifetimeResolver,
        StructureManagerInterface $structureManager,
        WebspaceResolver $webspaceResolver,
        RequestAnalyzer $requestAnalyzer
    ) {
        $this->documentManager = $documentManager;
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->cacheLifetimeResolver = $cacheLifetimeResolver;
        $this->structureManager = $structureManager;
        $this->webspaceResolver = $webspaceResolver;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * @param ArticleDocument $object
     */
    public function getByEntity($entityClass, $id, $locale, $object = null)
    {
        if (!$object) {
            $object = $this->documentManager->find($id, $locale);
        }

        $pageNumber = $object->getPageNumber();
        if ($object instanceof ArticlePageDocument) {
            // the article contains the seo/excerpt data and the controller handles the page-number automatically
            /** @var ArticleDocument $object */
            $object = $object->getParent();
        }

        $metadata = $this->structureMetadataFactory->getStructureMetadata('article', $object->getStructureType());

        // this parameter should not be used
        // but the sulu-collector for the profiler needs it to determine data from request
        /** @var StructureBridge $structure */
        $structure = $this->structureManager->wrapStructure('article', $metadata);
        $structure->setDocument($object);

        return [
            'object' => $object,
            'view' => $metadata->getView(),
            'pageNumber' => $pageNumber,
            'structure' => $structure,
            '_cacheLifetime' => $this->getCacheLifetime($metadata),
            '_controller' => $metadata->getController(),
        ];
    }

    /**
     * If article is not published the document will be of typ unknown-document.
     * Also check the workflow stage if it`s a ArticleDocument.
     *
     * {@inheritdoc}
     */
    public function isPublished($entityClass, $id, $locale)
    {
        $object = $this->documentManager->find(
            $id,
            $locale,
            [
                'load_ghost_content' => false,
            ]
        );

        if (!$object instanceof ArticleInterface) {
            return false;
        }

        if ($object instanceof ShadowLocaleBehavior && $object->isShadowLocaleEnabled()) {
            $object = $this->documentManager->find(
                $id,
                $object->getShadowLocale(),
                [
                    'load_ghost_content' => false,
                ],
            );
        }

        if (!$object instanceof ArticleInterface || WorkflowStage::PUBLISHED !== $object->getWorkflowStage()) {
            return false;
        }

        if (!$object instanceof WebspaceBehavior) {
            return true;
        }

        $webspace = $this->requestAnalyzer->getWebspace();
        if (!$webspace
            || (
                $this->webspaceResolver->resolveMainWebspace($object) !== $webspace->getKey()
                && !\in_array($webspace->getKey(), $this->webspaceResolver->resolveAdditionalWebspaces($object))
            )
        ) {
            return false;
        }

        return true;
    }

    public function supports($entityClass)
    {
        return ArticleDocument::class === $entityClass
            || ArticlePageDocument::class === $entityClass
            || \is_subclass_of($entityClass, ArticleDocument::class)
            || \is_subclass_of($entityClass, ArticlePageDocument::class);
    }

    /**
     * Get cache life time.
     */
    private function getCacheLifetime(StructureMetadata $metadata): ?int
    {
        $cacheLifetime = $metadata->getCacheLifetime();

        if (!$cacheLifetime) {
            return null;
        }

        if (!\is_array($cacheLifetime)
            || !isset($cacheLifetime['type'])
            || !isset($cacheLifetime['value'])
            || !$this->cacheLifetimeResolver->supports($cacheLifetime['type'], $cacheLifetime['value'])
        ) {
            throw new \InvalidArgumentException(\sprintf('Invalid cachelifetime in article route default provider: %s', \var_export($cacheLifetime, true)));
        }

        return $this->cacheLifetimeResolver->resolve($cacheLifetime['type'], $cacheLifetime['value']);
    }
}
