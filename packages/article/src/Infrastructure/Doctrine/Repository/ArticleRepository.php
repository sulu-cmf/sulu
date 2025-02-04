<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Sulu\Article\Domain\Exception\ArticleNotFoundException;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Webmozart\Assert\Assert;

class ArticleRepository implements ArticleRepositoryInterface
{
    /**
     * TODO it should be possible to extend fields and groups inside the SELECTS.
     */
    private const SELECTS = [
        // GROUPS
        self::GROUP_SELECT_ARTICLE_ADMIN => [
            self::SELECT_ARTICLE_CONTENT => [
                DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true,
            ],
        ],
        self::GROUP_SELECT_ARTICLE_WEBSITE => [
            self::SELECT_ARTICLE_CONTENT => [
                DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
            ],
        ],
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository<ArticleInterface>
     */
    protected $entityRepository;

    /**
     * @var EntityRepository<ArticleDimensionContentInterface>
     */
    protected $entityDimensionContentRepository;

    /**
     * @var DimensionContentQueryEnhancer
     */
    protected $dimensionContentQueryEnhancer;

    /**
     * @var class-string<ArticleInterface>
     */
    protected $articleClassName;

    /**
     * @var class-string<ArticleDimensionContentInterface>
     */
    protected $articleDimensionContentClassName;

    public function __construct(
        EntityManagerInterface $entityManager,
        DimensionContentQueryEnhancer $dimensionContentQueryEnhancer
    ) {
        $this->entityRepository = $entityManager->getRepository(ArticleInterface::class);
        $this->entityDimensionContentRepository = $entityManager->getRepository(ArticleDimensionContentInterface::class);
        $this->entityManager = $entityManager;
        $this->dimensionContentQueryEnhancer = $dimensionContentQueryEnhancer;
        $this->articleClassName = $this->entityRepository->getClassName();
        $this->articleDimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
    }

    public function createNew(?string $uuid = null): ArticleInterface
    {
        $className = $this->articleClassName;

        return new $className($uuid);
    }

    public function getOneBy(array $filters, array $selects = []): ArticleInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $selects);

        try {
            /** @var ArticleInterface $article */
            $article = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new ArticleNotFoundException($filters, 0, $e);
        }

        return $article;
    }

    public function findOneBy(array $filters, array $selects = []): ?ArticleInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $selects);

        try {
            /** @var ArticleInterface $article */
            $article = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }

        return $article;
    }

    public function countBy(array $filters = []): int
    {
        // The countBy method will ignore any page and limit parameters
        // for better developer experience we will strip them away here
        // instead of that the developer need to take that into account
        // in there call of the countBy method.
        unset($filters['page']); // @phpstan-ignore-line
        unset($filters['limit']); // @phpstan-ignore-line

        $queryBuilder = $this->createQueryBuilder($filters);

        $queryBuilder->select('COUNT(DISTINCT article.uuid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return \Generator<ArticleInterface>
     */
    public function findBy(array $filters = [], array $sortBy = [], array $selects = []): \Generator
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy, $selects);

        /** @var iterable<ArticleInterface> $articles */
        $articles = $queryBuilder->getQuery()->getResult();

        foreach ($articles as $article) {
            yield $article;
        }
    }

    public function findIdentifiersBy(array $filters = [], array $sortBy = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy);

        $queryBuilder->select('DISTINCT article.uuid');

        // we need to select the fields which are used in the order by clause

        /** @var OrderBy[] $orderBys */
        $orderBys = $queryBuilder->getDQLPart('orderBy');
        foreach ($orderBys as $orderBy) {
            $queryBuilder->addSelect(\explode(' ', $orderBy->getParts()[0])[0]);
        }

        /** @var iterable<string> $identifiers */
        $identifiers = $queryBuilder->getQuery()->getResult();

        return $identifiers;
    }

    public function add(ArticleInterface $article): void
    {
        $this->entityManager->persist($article);
    }

    public function remove(ArticleInterface $article): void
    {
        $this->entityManager->remove($article);
    }

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string|null,
     *     stage?: string|null,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     loadGhost?: bool,
     *     page?: int,
     *     limit?: int,
     * } $filters
     * @param array{
     *     uuid?: 'asc'|'desc',
     *     title?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     * } $sortBy
     * @param array{
     *     article_admin?: bool,
     *     article_website?: bool,
     *     with-article-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     */
    private function createQueryBuilder(array $filters, array $sortBy = [], array $selects = []): QueryBuilder
    {
        foreach ($selects as $selectGroup => $value) {
            if (!$value) {
                continue;
            }

            if (isset(self::SELECTS[$selectGroup])) {
                $selects = \array_replace_recursive($selects, self::SELECTS[$selectGroup]);
            }
        }

        $queryBuilder = $this->entityRepository->createQueryBuilder('article');

        $uuid = $filters['uuid'] ?? null;
        if (null !== $uuid) {
            Assert::string($uuid); // @phpstan-ignore-line
            $queryBuilder->andWhere('article.uuid = :uuid')
                ->setParameter('uuid', $uuid);
        }

        $uuids = $filters['uuids'] ?? null;
        if (null !== $uuids) {
            Assert::isArray($uuids);
            $queryBuilder->andWhere('article.uuid IN(:uuids)')
                ->setParameter('uuids', $uuids);
        }

        $limit = $filters['limit'] ?? null;
        if (null !== $limit) {
            Assert::integer($limit);
            $queryBuilder->setMaxResults($limit);
        }

        $page = $filters['page'] ?? null;
        if (null !== $page) {
            Assert::notNull($limit);
            Assert::integer($page);
            $offset = (int) ($limit * ($page - 1));
            $queryBuilder->setFirstResult($offset);
        }

        if (
            (\array_key_exists('locale', $filters)       // should also work with locale = null
            && \array_key_exists('stage', $filters))
            || ([] === $filters && [] !== $sortBy)      // if no filters are set, but sortBy is set, we need to set the sorting
        ) {
            $this->dimensionContentQueryEnhancer->addFilters(
                $queryBuilder,
                'article',
                $this->articleDimensionContentClassName,
                $filters,
                $sortBy
            );
        }

        if ([] !== $sortBy) {
            foreach ($sortBy as $field => $order) {
                if ('uuid' === $field) {
                    $queryBuilder->addOrderBy('article.uuid', $order);
                } elseif ('created' === $field) {
                    $queryBuilder->addOrderBy('article.created', $order);
                }
            }
        }

        // selects
        if ($selects[self::SELECT_ARTICLE_CONTENT] ?? null) {
            /** @var array<string, bool> $contentSelects */
            $contentSelects = $selects[self::SELECT_ARTICLE_CONTENT];

            $queryBuilder->leftJoin(
                'article.dimensionContents',
                'dimensionContent'
            );

            $this->dimensionContentQueryEnhancer->addSelects(
                $queryBuilder,
                $this->articleDimensionContentClassName,
                $filters,
                $contentSelects
            );
        }

        return $queryBuilder;
    }
}
