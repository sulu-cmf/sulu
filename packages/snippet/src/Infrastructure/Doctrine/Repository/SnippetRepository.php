<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Snippet\Domain\Exception\SnippetNotFoundException;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Webmozart\Assert\Assert;

class SnippetRepository implements SnippetRepositoryInterface
{
    /**
     * TODO it should be possible to extend fields and groups inside the SELECTS.
     */
    private const SELECTS = [
        // GROUPS
        self::GROUP_SELECT_SNIPPET_ADMIN => [
            self::SELECT_SNIPPET_CONTENT => [
                DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true,
            ],
        ],
        self::GROUP_SELECT_SNIPPET_WEBSITE => [
            self::SELECT_SNIPPET_CONTENT => [
                DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
            ],
        ],
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository<SnippetInterface>
     */
    protected $entityRepository;

    /**
     * @var EntityRepository<SnippetDimensionContentInterface>
     */
    protected $entityDimensionContentRepository;

    /**
     * @var DimensionContentQueryEnhancer
     */
    protected $dimensionContentQueryEnhancer;

    /**
     * @var class-string<SnippetInterface>
     */
    protected $snippetClassName;

    /**
     * @var class-string<SnippetDimensionContentInterface>
     */
    protected $snippetDimensionContentClassName;

    public function __construct(
        EntityManagerInterface $entityManager,
        DimensionContentQueryEnhancer $dimensionContentQueryEnhancer
    ) {
        $this->entityRepository = $entityManager->getRepository(SnippetInterface::class);
        $this->entityDimensionContentRepository = $entityManager->getRepository(SnippetDimensionContentInterface::class);
        $this->entityManager = $entityManager;
        $this->dimensionContentQueryEnhancer = $dimensionContentQueryEnhancer;
        $this->snippetClassName = $this->entityRepository->getClassName();
        $this->snippetDimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
    }

    public function createNew(?string $uuid = null): SnippetInterface
    {
        $className = $this->snippetClassName;

        return new $className($uuid);
    }

    public function getOneBy(array $filters, array $selects = []): SnippetInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $selects);

        try {
            /** @var SnippetInterface $snippet */
            $snippet = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new SnippetNotFoundException($filters, 0, $e);
        }

        return $snippet;
    }

    public function findOneBy(array $filters, array $selects = []): ?SnippetInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $selects);

        try {
            /** @var SnippetInterface $snippet */
            $snippet = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }

        return $snippet;
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

        $queryBuilder->select('COUNT(DISTINCT snippet.uuid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return \Generator<SnippetInterface>
     */
    public function findBy(array $filters = [], array $sortBy = [], array $selects = []): \Generator
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy, $selects);

        /** @var iterable<SnippetInterface> $snippets */
        $snippets = $queryBuilder->getQuery()->getResult();

        foreach ($snippets as $snippet) {
            yield $snippet;
        }
    }

    public function findIdentifiersBy(array $filters = [], array $sortBy = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy);

        $queryBuilder->select('DISTINCT snippet.uuid');

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

    public function add(SnippetInterface $snippet): void
    {
        $this->entityManager->persist($snippet);
    }

    public function remove(SnippetInterface $snippet): void
    {
        $this->entityManager->remove($snippet);
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
     *     snippet_admin?: bool,
     *     snippet_website?: bool,
     *     with-snippet-content?: bool|array<string, mixed>,
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

        $queryBuilder = $this->entityRepository->createQueryBuilder('snippet');

        $uuid = $filters['uuid'] ?? null;
        if (null !== $uuid) {
            Assert::string($uuid); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('snippet.uuid = :uuid')
                ->setParameter('uuid', $uuid);
        }

        $uuids = $filters['uuids'] ?? null;
        if (null !== $uuids) {
            Assert::isArray($uuids); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('snippet.uuid IN(:uuids)')
                ->setParameter('uuids', $uuids);
        }

        $limit = $filters['limit'] ?? null;
        if (null !== $limit) {
            Assert::integer($limit); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->setMaxResults($limit);
        }

        $page = $filters['page'] ?? null;
        if (null !== $page) {
            Assert::integer($page); // @phpstan-ignore staticMethod.alreadyNarrowedType
            Assert::notNull($limit);
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
                'snippet',
                $this->snippetDimensionContentClassName,
                $filters,
                $sortBy
            );
        }

        if ([] !== $sortBy) {
            foreach ($sortBy as $field => $order) {
                if ('uuid' === $field) {
                    $queryBuilder->addOrderBy('snippet.uuid', $order);
                } elseif ('created' === $field) {
                    $queryBuilder->addOrderBy('snippet.created', $order);
                }
            }
        }

        // selects
        if ($selects[self::SELECT_SNIPPET_CONTENT] ?? null) {
            /** @var array<string, bool> $contentSelects */
            $contentSelects = $selects[self::SELECT_SNIPPET_CONTENT];

            $queryBuilder->leftJoin(
                'snippet.dimensionContents',
                'dimensionContent'
            );

            $this->dimensionContentQueryEnhancer->addSelects(
                $queryBuilder,
                $this->snippetDimensionContentClassName,
                $filters,
                $contentSelects
            );
        }

        return $queryBuilder;
    }
}
