<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Infrastructure\Sulu\Content;

use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\Builder;
use Sulu\Component\SmartContent\Configuration\BuilderInterface;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderAliasInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\DatasourceItemInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

class SnippetDataProvider implements DataProviderInterface, DataProviderAliasInterface
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private ContentManagerInterface $contentManager,
        private ReferenceStoreInterface $snippetReferenceStore,
        private bool $showDrafts,
    ) {
    }

    public function getConfiguration(): ProviderConfigurationInterface
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
                    ['column' => 'workflowPublished', 'title' => 'sulu_admin.published'],
                    ['column' => 'authored', 'title' => 'sulu_admin.authored'],
                    ['column' => 'created', 'title' => 'sulu_admin.created'],
                    ['column' => 'title', 'title' => 'sulu_admin.title'],
                    ['column' => 'author', 'title' => 'sulu_admin.author'],
                ]
            );

        return $builder;
    }

    public function getDefaultPropertyParameter(): array
    {
        return [
            'type' => new PropertyParameter('type', ''),
            'ignoreWebspaces' => new PropertyParameter('ignoreWebspaces', false),
        ];
    }

    public function resolveDataItems(array $filters, array $propertyParameter, array $options = [], $limit = null, $page = 1, $pageSize = null)
    {
        /** @var string $locale */
        $locale = $options['locale'];
        [$filters, $sortBy] = $this->resolveFilters($filters, $propertyParameter, $page, $locale);

        $dimensionAttributes = [
            'locale' => $locale,
            'stage' => $this->showDrafts ? DimensionContentInterface::STAGE_DRAFT : DimensionContentInterface::STAGE_LIVE,
        ];

        /** @var string[] $identifiers */
        $identifiers = $this->snippetRepository->findIdentifiersBy(
            filters: \array_merge($dimensionAttributes, $filters),
            sortBy: $sortBy
        );

        $snippets = $this->snippetRepository->findBy(
            filters: \array_merge($dimensionAttributes, ['uuids' => $identifiers]),
            sortBy: $sortBy,
            selects: [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_ADMIN => true]
        );

        $result = [];
        foreach ($snippets as $snippet) {
            $dimensionContent = $this->contentManager->resolve($snippet, $dimensionAttributes);
            $result[] = new ArrayAccessItem($snippet->getId(), [
                'id' => $snippet->getId(),
                'title' => $dimensionContent->getTitle(),
            ], $snippet);
        }
        $hasNextPage = \count($result) > ($pageSize ?? $limit);

        return new DataProviderResult($result, $hasNextPage);
    }

    public function resolveResourceItems(array $filters, array $propertyParameter, array $options = [], $limit = null, $page = 1, $pageSize = null): DataProviderResult
    {
        /** @var string $locale */
        $locale = $options['locale'];
        [$filters, $sortBy] = $this->resolveFilters($filters, $propertyParameter, $page, $locale);

        $dimensionAttributes = [
            'locale' => $locale,
            'stage' => $this->showDrafts ? DimensionContentInterface::STAGE_DRAFT : DimensionContentInterface::STAGE_LIVE,
        ];

        /** @var string[] $identifiers */
        $identifiers = $this->snippetRepository->findIdentifiersBy(
            filters: \array_merge($dimensionAttributes, $filters),
            sortBy: $sortBy
        );

        $snippets = $this->snippetRepository->findBy(
            filters: \array_merge($dimensionAttributes, ['uuids' => $identifiers]),
            sortBy: $sortBy,
            selects: [SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true]
        );

        $result = [];
        foreach ($snippets as $snippet) {
            $dimensionContent = $this->contentManager->resolve($snippet, $dimensionAttributes);
            $result[] = new ArrayAccessItem(
                $snippet->getId(),
                $this->contentManager->normalize($dimensionContent),
                $snippet,
            );

            $this->snippetReferenceStore->add($snippet->getId());
        }
        $hasNextPage = \count($result) > ($pageSize ?? $limit);

        return new DataProviderResult($result, $hasNextPage);
    }

    /**
     * @param array{
     *     categories?: int[],
     *     categoryOperator?: 'or'|'and',
     *     tags?: string[],
     *     tagOperator?: 'or'|'and',
     *     limitResult?: int,
     *     sortBy?: string,
     *     sortMethod?: 'asc'|'desc',
     *     ...
     * } $filters
     * @param array<string, PropertyParameter> $propertyParameter
     *
     * @return array{
     *     array{
     *         locale: string,
     *         categoryIds?: int[],
     *         categoryOperator?: 'AND'|'OR',
     *         tagIds?: int[],
     *         tagOperator?: 'AND'|'OR',
     *         limit?: int,
     *         page: int
     *     },
     *     array<string, 'asc'|'desc'>
     * }
     */
    protected function resolveFilters(
        array $filters, array $propertyParameter, int $page, string $locale): array
    {
        $filter = [
            'locale' => $locale,
        ];
        /** @var array<string, 'asc'|'desc'> $sortBy */
        $sortBy = [];
        if (isset($filters['categories'])) {
            $filter['categoryIds'] = $filters['categories'];
        }
        if (isset($filters['categoryOperator'])) {
            $filter['categoryOperator'] = \strtoupper($filters['categoryOperator']);
        }
        if (isset($filters['tags'])) {
            /** @var int[] $tags */ // TODO check whats correct here sulu defines string, content bundle int
            $tags = $filters['tags'];
            $filter['tagIds'] = $tags;
        }
        if (isset($filters['tagOperator'])) {
            $filter['tagOperator'] = \strtoupper($filters['tagOperator']);
        }
        if (isset($filters['limitResult']) || isset($propertyParameter['max_per_page'])) {
            $filter['limit'] = (int) ($filters['limitResult'] ?? $propertyParameter['max_per_page']->getValue());
        }
        $filter['page'] = $page;

        if (isset($filters['sortBy']) && isset($filters['sortMethod'])) {
            $sortBy[$filters['sortBy']] = $filters['sortMethod'];
        }

        return [$filter, $sortBy];
    }

    public function resolveDatasource($datasource, array $propertyParameter, array $options): ?DatasourceItemInterface
    {
        return null;
    }

    public function getAlias()
    {
        return 'snippet';
    }
}
