<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Content;

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\PageBundle\Content\Types\SegmentSelect;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\SmartContent\Configuration\Builder;
use Sulu\Component\SmartContent\Configuration\BuilderInterface;
use Sulu\Component\SmartContent\DataProviderAliasInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Introduces articles in smart-content.
 */
class ArticleDataProvider implements DataProviderInterface, DataProviderAliasInterface
{
    /**
     * @var Manager
     */
    protected $searchManager;

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    protected $proxyFactory;

    /**
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    /**
     * @var ArticleResourceItemFactory
     */
    protected $articleResourceItemFactory;

    /**
     * @var string
     */
    protected $articleDocumentClass;

    /**
     * @var int
     */
    protected $defaultLimit;

    /**
     * @var MetadataProviderInterface|null
     */
    private $formMetadataProvider;

    /**
     * @var TokenStorageInterface|null
     */
    private $tokenStorage;

    /**
     * @var bool
     */
    private $hasAudienceTargeting;

    public function __construct(
        Manager $searchManager,
        DocumentManagerInterface $documentManager,
        LazyLoadingValueHolderFactory $proxyFactory,
        ReferenceStoreInterface $referenceStore,
        ArticleResourceItemFactory $articleResourceItemFactory,
        string $articleDocumentClass,
        int $defaultLimit,
        ?MetadataProviderInterface $formMetadataProvider = null,
        ?TokenStorageInterface $tokenStorage = null,
        bool $hasAudienceTargeting = false
    ) {
        $this->searchManager = $searchManager;
        $this->documentManager = $documentManager;
        $this->proxyFactory = $proxyFactory;
        $this->referenceStore = $referenceStore;
        $this->articleResourceItemFactory = $articleResourceItemFactory;
        $this->articleDocumentClass = $articleDocumentClass;
        $this->defaultLimit = $defaultLimit;
        $this->formMetadataProvider = $formMetadataProvider;
        $this->tokenStorage = $tokenStorage;
        $this->hasAudienceTargeting = $hasAudienceTargeting;
    }

    public function getConfiguration()
    {
        return $this->getConfigurationBuilder()->getConfiguration();
    }

    /**
     * Create new configuration-builder.
     */
    protected function getConfigurationBuilder(): BuilderInterface
    {
        $builder = Builder::create()
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->enableSorting(
                [
                    ['column' => 'published', 'title' => 'sulu_admin.published'],
                    ['column' => 'authored', 'title' => 'sulu_admin.authored'],
                    ['column' => 'created', 'title' => 'sulu_admin.created'],
                    ['column' => 'title.raw', 'title' => 'sulu_admin.title'],
                    ['column' => 'author_full_name.raw', 'title' => 'sulu_admin.author'],
                    ['column' => 'last_modified_or_authored', 'title' => 'sulu_article.last_modified_or_authored'],
                ]
            );

        if (\method_exists($builder, 'enableTypes')) {
            $builder->enableTypes($this->getTypes());
        }

        if ($this->hasAudienceTargeting) {
            $builder->enableAudienceTargeting();
        }

        return $builder;
    }

    public function getDefaultPropertyParameter()
    {
        return [
            'type' => new PropertyParameter('type', null),
            'ignoreWebspaces' => new PropertyParameter('ignoreWebspaces', false),
        ];
    }

    public function resolveDataItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        // there are two different kinds of types in the context of the article bundle: template-type and article-type
        // filtering by article-type is possible via the types xml param
        // filtering by template-type is possible via the structureTypes xml param and the admin interface overlay
        // unfortunately, the admin frontend sends the selected types in $filters['types'] to the provider
        // TODO: adjust the naming of the xml params to be consistent consistent, but this will be a bc break
        $filters['structureTypes'] = \array_merge($filters['types'] ?? [], $this->getStructureTypesProperty($propertyParameter));
        $filters['types'] = $this->getTypesProperty($propertyParameter);
        $filters['excluded'] = $this->getExcludedFilter($filters, $propertyParameter);

        /** @var string|null $locale */
        $locale = $options['locale'];
        $webspaceKey = $this->getWebspaceKey($propertyParameter, $options);
        $queryResult = $this->getSearchResult($filters, $limit, $page, $pageSize, $locale, $webspaceKey);

        $result = [];
        /** @var ArticleViewDocumentInterface $document */
        foreach ($queryResult as $document) {
            $result[] = new ArticleDataItem($document->getUuid(), $document->getTitle(), $document);
        }

        return new DataProviderResult($result, $this->hasNextPage($queryResult, $limit, $page, $pageSize));
    }

    public function resolveResourceItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        // there are two different kinds of types in the context of the article bundle: template-type and article-type
        // filtering by article-type is possible via the types xml param
        // filtering by template-type is possible via the structureTypes xml param and the admin interface overlay
        // unfortunately, the admin frontend sends the selected types in $filters['types'] to the provider
        // TODO: adjust the naming of the xml params to be consistent consistent, but this will be a bc break
        $filters['structureTypes'] = \array_merge($filters['types'] ?? [], $this->getStructureTypesProperty($propertyParameter));
        $filters['types'] = $this->getTypesProperty($propertyParameter);
        $filters['excluded'] = $this->getExcludedFilter($filters, $propertyParameter);

        /** @var string|null $locale */
        $locale = $options['locale'];
        $webspaceKey = $this->getWebspaceKey($propertyParameter, $options);
        $queryResult = $this->getSearchResult($filters, $limit, $page, $pageSize, $locale, $webspaceKey);

        $result = [];
        /** @var ArticleViewDocumentInterface $document */
        foreach ($queryResult as $document) {
            $this->referenceStore->add($document->getUuid());
            $result[] = $this->articleResourceItemFactory->createResourceItem($document);
        }

        return new DataProviderResult($result, $this->hasNextPage($queryResult, $limit, $page, $pageSize));
    }

    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        return;
    }

    private function getWebspaceKey(array $propertyParameter, array $options): ?string
    {
        if (\array_key_exists('ignoreWebspaces', $propertyParameter)) {
            $value = $propertyParameter['ignoreWebspaces']->getValue();

            if (true === $value) {
                return null;
            }
        }

        if (\array_key_exists('webspaceKey', $options)) {
            return $options['webspaceKey'];
        }

        return null;
    }

    /**
     * Returns flag "hasNextPage".
     * It combines the limit/query-count with the page and page-size.
     */
    private function hasNextPage(\Countable $queryResult, ?int $limit, int $page, ?int $pageSize): bool
    {
        $count = $queryResult->count();

        if (null === $pageSize || $pageSize > $this->defaultLimit) {
            $pageSize = $this->defaultLimit;
        }

        $offset = ($page - 1) * $pageSize;
        if ($limit && $offset + $pageSize > $limit) {
            return false;
        }

        return $count > ($page * $pageSize);
    }

    /**
     * Creates search for filters and returns search-result.
     */
    private function getSearchResult(array $filters, ?int $limit, int $page, ?int $pageSize, ?string $locale, ?string $webspaceKey): \Countable
    {
        $repository = $this->searchManager->getRepository($this->articleDocumentClass);
        $search = $this->createSearch($repository->createSearch(), $filters, $locale);
        if (!$search) {
            return new \ArrayIterator([]);
        }

        $this->addPagination($search, $pageSize, $page, $limit);

        if (\array_key_exists('sortBy', $filters)) {
            $sortMethod = \array_key_exists('sortMethod', $filters) ? $filters['sortMethod'] : 'asc';
            $search->addSort(new FieldSort($filters['sortBy'], $sortMethod));
        }

        if ($webspaceKey) {
            $webspaceQuery = new BoolQuery();

            // check for mainWebspace
            $webspaceQuery->add(new TermQuery('main_webspace', $webspaceKey), BoolQuery::SHOULD);

            // check for additionalWebspaces
            $webspaceQuery->add(new TermQuery('additional_webspaces', $webspaceKey), BoolQuery::SHOULD);

            $search->addQuery($webspaceQuery);
        }

        $segmentKey = $filters['segmentKey'] ?? null;
        if ($segmentKey && $webspaceKey) {
            $matchingSegmentQuery = new TermQuery(
                'excerpt.segments.assignment_key',
                $webspaceKey . SegmentSelect::SEPARATOR . $segmentKey
            );

            $noSegmentQuery = new BoolQuery();
            $noSegmentQuery->add(new TermQuery('excerpt.segments.webspace_key', $webspaceKey), BoolQuery::MUST_NOT);

            $segmentQuery = new BoolQuery();
            $segmentQuery->add($matchingSegmentQuery, BoolQuery::SHOULD);
            $segmentQuery->add($noSegmentQuery, BoolQuery::SHOULD);

            $search->addQuery($segmentQuery);
        }

        $targetGroup = $filters['targetGroupId'] ?? null;

        if ($targetGroup) {
            $targetGroupQuery = new BoolQuery();
            $targetGroupQuery->add(new TermQuery('excerpt.audience_targeting_groups', $targetGroup), BoolQuery::MUST);
            $search->addQuery($targetGroupQuery);
        }

        return $repository->findDocuments($search);
    }

    /**
     * Initialize search with neccesary queries.
     */
    protected function createSearch(Search $search, array $filters, string $locale): Search
    {
        if (0 < \count($filters['excluded'])) {
            foreach ($filters['excluded'] as $uuid) {
                $search->addQuery(new TermQuery('uuid', $uuid), BoolQuery::MUST_NOT);
            }
        }

        $query = new BoolQuery();

        $queriesCount = 0;
        $operator = $this->getFilter($filters, 'tagOperator', 'or');
        $this->addBoolQuery('tags', $filters, 'excerpt.tags.id', $operator, $query, $queriesCount);
        $operator = $this->getFilter($filters, 'websiteTagsOperator', 'or');
        $this->addBoolQuery('websiteTags', $filters, 'excerpt.tags.id', $operator, $query, $queriesCount);

        $operator = $this->getFilter($filters, 'categoryOperator', 'or');
        $this->addBoolQuery('categories', $filters, 'excerpt.categories.id', $operator, $query, $queriesCount);
        $operator = $this->getFilter($filters, 'websiteCategoriesOperator', 'or');
        $this->addBoolQuery('websiteCategories', $filters, 'excerpt.categories.id', $operator, $query, $queriesCount);

        if (null !== $locale) {
            $search->addQuery(new TermQuery('locale', $locale));
        }

        if (\array_key_exists('types', $filters) && $filters['types']) {
            $typesQuery = new BoolQuery();
            foreach ($filters['types'] as $typeFilter) {
                $typesQuery->add(new TermQuery('type', $typeFilter), BoolQuery::SHOULD);
            }
            $search->addQuery($typesQuery);
        }

        if (\array_key_exists('structureTypes', $filters) && $filters['structureTypes']) {
            $strTypesQuery = new BoolQuery();
            foreach ($filters['structureTypes'] as $filter) {
                $strTypesQuery->add(new TermQuery('structure_type', $filter), BoolQuery::SHOULD);
            }
            $search->addQuery($strTypesQuery);
        }

        if (0 === $queriesCount) {
            $search->addQuery(new MatchAllQuery(), BoolQuery::MUST);
        } else {
            $search->addQuery($query, BoolQuery::MUST);
        }

        return $search;
    }

    /**
     * Returns array with all types defined in property parameter.
     */
    private function getTypesProperty(array $propertyParameter): array
    {
        $filterTypes = [];

        if (\array_key_exists('types', $propertyParameter)
            && !empty($value = $propertyParameter['types']->getValue())
            && \is_string($value)) {
            $types = \explode(',', $value);

            foreach ($types as $type) {
                $filterTypes[] = $type;
            }
        }

        return $filterTypes;
    }

    /**
     * Returns array with all structure types (template keys) defined in property parameter.
     */
    private function getStructureTypesProperty(array $propertyParameter): array
    {
        $filterStrTypes = [];

        if (\array_key_exists('structureTypes', $propertyParameter)
            && null !== ($types = \explode(',', $propertyParameter['structureTypes']->getValue()))
        ) {
            foreach ($types as $type) {
                $filterStrTypes[] = $type;
            }
        }

        return $filterStrTypes;
    }

    /**
     * Returns excluded articles.
     *
     * @param PropertyParameter[] $propertyParameter
     */
    private function getExcludedFilter(array $filters, array $propertyParameter): array
    {
        $excluded = \array_key_exists('excluded', $filters) ? $filters['excluded'] : [];
        if (\array_key_exists('exclude_duplicates', $propertyParameter)
            && $propertyParameter['exclude_duplicates']->getValue()
        ) {
            $excluded = \array_merge($excluded, $this->referenceStore->getAll());
        }

        return $excluded;
    }

    /**
     * Add the pagination to given query.
     */
    private function addPagination(Search $search, ?int $pageSize, int $page, ?int $limit): void
    {
        if (null === $pageSize || $pageSize > $this->defaultLimit) {
            $pageSize = $this->defaultLimit;
        }

        $offset = ($page - 1) * $pageSize;

        if ($limit && $offset + $pageSize > $limit) {
            $pageSize = $limit - $offset;
        }

        if ($pageSize < 0) {
            $pageSize = 0;
        }

        $search->setFrom($offset);
        $search->setSize($pageSize);
    }

    /**
     * Add a boolean-query if filter exists.
     */
    private function addBoolQuery(
        string $filterName,
        array $filters,
        string $field,
        string $operator,
        BoolQuery $query,
        int &$queriesCount
    ): void {
        if (0 !== \count($tags = $this->getFilter($filters, $filterName, []))) {
            ++$queriesCount;
            $query->add($this->getBoolQuery($field, $tags, $operator));
        }
    }

    /**
     * Returns boolean query for given fields and values.
     */
    private function getBoolQuery(string $field, array $values, string $operator): BoolQuery
    {
        $type = ('or' === \strtolower($operator) ? BoolQuery::SHOULD : BoolQuery::MUST);

        $query = new BoolQuery();
        foreach ($values as $value) {
            $query->add(new TermQuery($field, $value), $type);
        }

        return $query;
    }

    /**
     * Returns filter value.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    private function getFilter(array $filters, string $name, $default = null)
    {
        if ($this->hasFilter($filters, $name)) {
            return $filters[$name];
        }

        return $default;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getTypes(): array
    {
        $types = [];
        if ($this->tokenStorage && null !== $this->tokenStorage->getToken() && $this->formMetadataProvider) {
            $user = $this->tokenStorage->getToken()->getUser();

            if (!$user instanceof UserInterface) {
                return $types;
            }

            /** @var TypedFormMetadata $metadata */
            $metadata = $this->formMetadataProvider->getMetadata('article', $user->getLocale(), []);

            foreach ($metadata->getForms() as $form) {
                $types[] = ['type' => $form->getName(), 'title' => $form->getTitle()];
            }
        }

        return $types;
    }

    /**
     * Returns true if filter-value exists.
     */
    private function hasFilter(array $filters, string $name): bool
    {
        return \array_key_exists($name, $filters) && null !== $filters[$name];
    }

    /**
     * Returns Proxy document for uuid.
     */
    private function getResource(string $uuid, string $locale): object
    {
        return $this->proxyFactory->createProxy(
            ArticleDocument::class,
            function(
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($uuid, $locale) {
                $initializer = null;
                $wrappedObject = $this->documentManager->find($uuid, $locale);

                return true;
            }
        );
    }

    public function getAlias()
    {
        return 'article';
    }
}
