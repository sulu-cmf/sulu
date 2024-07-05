<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Orm;

use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Provides basic implementation of orm DataProvider repository.
 *
 * @method abstract createQueryBuilder(string $alias, ?string $indexBy = null): \Doctrine\ORM\QueryBuilder;
 */
trait DataProviderRepositoryTrait
{
    /**
     * @var AccessControlQueryEnhancer|null
     */
    private $accessControlQueryEnhancer = null;

    /**
     * @param array $filters
     * @param int $page
     * @param int $pageSize
     * @param int|null $limit
     * @param string $locale
     * @param array{webspaceKey?: string, locale?: string} $options
     * @param class-string|null $entityClass
     * @param string|null $entityAlias
     * @param int|null $permission
     *
     * @return mixed
     *
     * @see DataProviderRepositoryInterface::findByFilters
     */
    public function findByFilters(
        $filters,
        $page,
        $pageSize,
        $limit,
        $locale,
        $options = [],
        ?UserInterface $user = null,
        $entityClass = null,
        $entityAlias = null,
        $permission = null
    ) {
        $alias = 'entity';
        $queryBuilder = $this->createQueryBuilder($alias)
            ->addSelect($alias)
            ->where($alias . '.id IN (:ids)')
            ->orderBy($alias . '.id', 'ASC');
        $this->appendJoins($queryBuilder, $alias, $locale);

        if (isset($filters['sortBy'])) {
            $sortMethod = $filters['sortMethod'] ?? 'asc';
            $sortBy = false !== \strpos($filters['sortBy'], '.') ? $filters['sortBy'] : $alias . '.' . $filters['sortBy'];

            $this->appendSortBy($sortBy, $sortMethod, $queryBuilder, $alias, $locale);
        }

        $query = $queryBuilder->getQuery();
        $ids = $this->findByFiltersIds(
            $filters,
            $page,
            $pageSize,
            $limit,
            $locale,
            $options,
            $user,
            $entityClass,
            $entityAlias,
            $permission
        );
        $query->setParameter('ids', $ids);

        return $query->getResult();
    }

    /**
     * Resolves filter and returns id array for second query.
     *
     * @param array $filters array of filters: tags, tagOperator
     * @param int $page
     * @param int $pageSize
     * @param int|null $limit
     * @param string $locale
     * @param mixed[] $options
     * @param class-string $entityClass
     *
     * @return int[]|string[]
     */
    private function findByFiltersIds(
        $filters,
        $page,
        $pageSize,
        $limit,
        $locale,
        $options = [],
        ?UserInterface $user = null,
        ?string $entityClass = null,
        ?string $entityAlias = null,
        ?int $permission = null
    ) {
        $parameter = [];

        $alias = 'entity';
        $queryBuilder = $this->createQueryBuilder($alias)
            ->select($alias . '.id')
            ->distinct()
            ->orderBy($alias . '.id', 'ASC');

        $tagRelation = $this->appendTagsRelation($queryBuilder, $alias);
        $categoryRelation = $this->appendCategoriesRelation($queryBuilder, $alias);

        if (isset($filters['sortBy'])) {
            $sortMethod = $filters['sortMethod'] ?? 'asc';
            $sortBy = false !== \strpos($filters['sortBy'], '.') ? $filters['sortBy'] : $alias . '.' . $filters['sortBy'];

            $this->appendSortBy($sortBy, $sortMethod, $queryBuilder, $alias, $locale);
            $queryBuilder->addSelect($sortBy);
        }

        $parameter = $this->append($queryBuilder, $alias, $locale, $options);

        if (isset($filters['dataSource'])) {
            $includeSubFolders = $this->getBoolean($filters['includeSubFolders'] ?? false);
            $parameter = \array_merge(
                $parameter,
                $this->appendDatasource($filters['dataSource'], $includeSubFolders, $queryBuilder, $alias)
            );
        }

        if (isset($filters['tags']) && !empty($filters['tags'])) {
            $parameter = \array_merge(
                $parameter,
                $this->appendRelation(
                    $queryBuilder,
                    $tagRelation,
                    $filters['tags'],
                    \strtolower($filters['tagOperator']),
                    'adminTags'
                )
            );
        }

        if (isset($filters['types']) && !empty($filters['types'])) {
            $typeRelation = $this->appendTypeRelation($queryBuilder, $alias);
            $parameter = \array_merge(
                $parameter,
                $this->appendRelation(
                    $queryBuilder,
                    $typeRelation,
                    $filters['types'],
                    'or',
                    'typeId'
                )
            );
        }

        if (isset($filters['categories']) && !empty($filters['categories'])) {
            $parameter = \array_merge(
                $parameter,
                $this->appendRelation(
                    $queryBuilder,
                    $categoryRelation,
                    $filters['categories'],
                    \strtolower($filters['categoryOperator']),
                    'adminCategories'
                )
            );
        }

        if (isset($filters['targetGroupId']) && $filters['targetGroupId']) {
            $targetGroupRelation = $this->appendTargetGroupRelation($queryBuilder, $alias);
            $parameter = \array_merge(
                $parameter,
                $this->appendRelation(
                    $queryBuilder,
                    $targetGroupRelation,
                    [$filters['targetGroupId']],
                    'and',
                    'targetGroupId'
                )
            );
        }

        if (isset($filters['websiteTags']) && !empty($filters['websiteTags'])) {
            $parameter = \array_merge(
                $parameter,
                $this->appendRelation(
                    $queryBuilder,
                    $tagRelation,
                    $filters['websiteTags'],
                    \strtolower($filters['websiteTagsOperator']),
                    'websiteTags'
                )
            );
        }

        if (isset($filters['websiteCategories']) && !empty($filters['websiteCategories'])) {
            $parameter = \array_merge(
                $parameter,
                $this->appendRelation(
                    $queryBuilder,
                    $categoryRelation,
                    $filters['websiteCategories'],
                    \strtolower($filters['websiteCategoriesOperator']),
                    'websiteCategories'
                )
            );
        }

        if ($this->accessControlQueryEnhancer && $entityClass && $entityAlias && $permission) {
            $this->accessControlQueryEnhancer->enhance(
                $queryBuilder,
                $user,
                $permission,
                $entityClass,
                $entityAlias
            );
        }

        $query = $queryBuilder->getQuery();
        foreach ($parameter as $name => $value) {
            $query->setParameter($name, $value);
        }

        if (null !== $page && $pageSize > 0) {
            $pageOffset = ($page - 1) * $pageSize;
            $restLimit = $limit - $pageOffset;

            // if limitation is smaller than the page size then use the rest limit else use page size plus 1 to
            // determine has next page
            $maxResults = (null !== $limit && $pageSize > $restLimit ? $restLimit : ($pageSize + 1));

            if ($maxResults <= 0) {
                return [];
            }

            $query->setMaxResults($maxResults);
            $query->setFirstResult($pageOffset);
        } elseif (null !== $limit) {
            $query->setMaxResults($limit);
        }

        return \array_map(
            function($item) {
                /** @var int|string */
                return $item['id'];
            },
            $query->getScalarResult()
        );
    }

    /**
     * Returns boolean for string.
     *
     * @param string|bool $value
     *
     * @return bool
     */
    private function getBoolean($value)
    {
        if (true === $value || 'true' === $value) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Append tags to query builder with given operator.
     *
     * @param string $relation
     * @param int[] $values
     * @param string $operator "and" or "or"
     * @param string $alias
     *
     * @return array<string, int|string|int[]|string[]> parameter for the query
     */
    private function appendRelation(QueryBuilder $queryBuilder, $relation, $values, $operator, $alias)
    {
        return match ($operator) {
            'or' => $this->appendRelationOr($queryBuilder, $relation, $values, $alias),
            'and' => $this->appendRelationAnd($queryBuilder, $relation, $values, $alias),
            default => [],
        };
    }

    /**
     * Append tags to query builder with "or" operator.
     *
     * @param string $relation
     * @param int[] $values
     * @param string $alias
     *
     * @return array<string, int|string|int[]|string[]> parameter for the query
     */
    private function appendRelationOr(QueryBuilder $queryBuilder, $relation, $values, $alias)
    {
        $queryBuilder->leftJoin($relation, $alias)
            ->andWhere($alias . '.id IN (:' . $alias . ')');

        return [$alias => $values];
    }

    /**
     * Append tags to query builder with "and" operator.
     *
     * @param string $relation
     * @param int[] $values
     * @param string $alias
     *
     * @return array<string, int|string|int[]|string[]> parameter for the query
     */
    private function appendRelationAnd(QueryBuilder $queryBuilder, $relation, $values, $alias)
    {
        $parameter = [];
        $expr = $queryBuilder->expr()->andX();

        $length = \count($values);
        for ($i = 0; $i < $length; ++$i) {
            $queryBuilder->leftJoin($relation, $alias . $i);

            $expr->add($queryBuilder->expr()->eq($alias . $i . '.id', ':' . $alias . $i));

            $parameter[$alias . $i] = $values[$i];
        }
        $queryBuilder->andWhere($expr);

        return $parameter;
    }

    /**
     * Append joins to query builder for "findByFilters" function.
     *
     * @param string $alias
     * @param string $locale
     *
     * @return void
     */
    abstract protected function appendJoins(QueryBuilder $queryBuilder, $alias, $locale);

    /**
     * Append additional condition to query builder for "findByFilters" function.
     *
     * @param string $alias
     * @param string $locale
     * @param mixed[] $options
     *
     * @return array<string, int|string|int[]|string[]> parameters for query
     */
    protected function append(QueryBuilder $queryBuilder, $alias, $locale, $options = [])
    {
        // empty implementation can be overwritten by repository
        return [];
    }

    /**
     * Extension point to append relations to tag relation if it is not direct linked.
     *
     * @param string $alias
     *
     * @return string field path to tag relation
     */
    protected function appendTagsRelation(QueryBuilder $queryBuilder, $alias)
    {
        return $alias . '.tags';
    }

    /**
     * Extension point to append relations to category relation if it is not direct linked.
     *
     * @param string $alias
     *
     * @return string field path to category relation
     */
    protected function appendCategoriesRelation(QueryBuilder $queryBuilder, $alias)
    {
        return $alias . '.categories';
    }

    /**
     * Extension point to append relations to target group relation if it is not direct linked.
     *
     * @param string $alias
     *
     * @return string
     */
    protected function appendTargetGroupRelation(QueryBuilder $queryBuilder, $alias)
    {
        return $alias . '.targetGroups';
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    protected function appendTypeRelation(QueryBuilder $queryBuilder, $alias)
    {
        return $alias . '.type';
    }

    /**
     * Extension point to append datasource.
     *
     * @param int|string $datasource
     * @param bool $includeSubFolders
     * @param string $alias
     *
     * @return array<string, int|string|int[]|string[]> parameters for query
     */
    protected function appendDatasource($datasource, $includeSubFolders, QueryBuilder $queryBuilder, $alias)
    {
        // empty implementation can be overwritten by repository
        return [];
    }

    /**
     * Extension point to append order.
     *
     * @param string $sortBy
     * @param string $sortMethod
     * @param string $alias
     * @param string $locale
     *
     * @return void
     */
    protected function appendSortBy($sortBy, $sortMethod, QueryBuilder $queryBuilder, $alias, $locale)
    {
        if (!\in_array(\explode('.', $sortBy)[0], $queryBuilder->getAllAliases())) {
            $this->appendSortByJoins($queryBuilder, $alias, $locale);
        }

        $queryBuilder->orderBy($sortBy, $sortMethod);
    }

    /**
     * Append joins to query builder for "findByFilters" function specially for sort-by.
     *
     * @param string $alias
     * @param string $locale
     *
     * @return void
     */
    protected function appendSortByJoins(QueryBuilder $queryBuilder, $alias, $locale)
    {
    }
}
