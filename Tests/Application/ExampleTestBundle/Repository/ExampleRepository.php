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

namespace Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Exception\ExampleNotFoundException;
use Webmozart\Assert\Assert;

class ExampleRepository
{
    /**
     * Groups are used in controllers and represents serialization / resolver group,
     * this allows that no controller need to be overwritten when something additional should be
     * loaded at that endpoint.
     */
    public const GROUP_SELECT_EXAMPLE_ADMIN = 'example_admin';
    public const GROUP_SELECT_EXAMPLE_WEBSITE = 'example_website';

    /**
     * Withs represents additional selects which can be load to join and select specific sub entities.
     * They are used by groups.
     */
    public const SELECT_EXAMPLE_CONTENT = 'with-example-content';

    public const SELECT_EXAMPLE_TRANSLATION = 'with-example-translation';

    /**
     * TODO it should be possible to extend fields and groups inside the SELECTS.
     */
    private const SELECTS = [
        // GROUPS
        self::GROUP_SELECT_EXAMPLE_ADMIN => [
            self::SELECT_EXAMPLE_TRANSLATION => true,
            self::SELECT_EXAMPLE_CONTENT => [
                DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true,
            ],
        ],
        self::GROUP_SELECT_EXAMPLE_WEBSITE => [
            self::SELECT_EXAMPLE_TRANSLATION => true,
            self::SELECT_EXAMPLE_CONTENT => [
                DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
            ],
        ],
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository<Example>
     */
    private $entityRepository;

    /**
     * @var DimensionContentQueryEnhancer
     */
    private $dimensionContentQueryEnhancer;

    public function __construct(
        EntityManagerInterface $entityManager,
        DimensionContentQueryEnhancer $dimensionContentQueryEnhancer
    ) {
        $this->entityRepository = $entityManager->getRepository(Example::class);
        $this->entityManager = $entityManager;
        $this->dimensionContentQueryEnhancer = $dimensionContentQueryEnhancer;
    }

    /**
     * @param array{
     *     id?: int,
     *     ids?: int[],
     *     locale?: string|null,
     *     stage?: string|null,
     * } $filters
     * @param array{
     *     example_admin?: bool,
     *     example_website?: bool,
     *     with-example-content?: bool|array<string, mixed>,
     * } $selects
     *
     * @throws ExampleNotFoundException
     */
    public function getOneBy(array $filters, array $selects = []): Example
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $selects);

        try {
            /** @var Example $example */
            $example = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new ExampleNotFoundException($filters, 0, $e);
        }

        return $example;
    }

    /**
     * @param array{
     *     id?: int,
     *     ids?: int[],
     *     locale?: string|null,
     *     stage?: string|null,
     * } $filters
     * @param array{
     *     example_admin?: bool,
     *     example_website?: bool,
     *     with-example-content?: array<string, mixed>,
     * } $selects
     */
    public function findOneBy(array $filters, array $selects = []): ?Example
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $selects);

        try {
            /** @var Example $example */
            $example = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }

        return $example;
    }

    /**
     * @param array{
     *     id?: int,
     *     ids?: int[],
     *     locale?: string|null,
     *     stage?: string|null,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     * } $filters
     */
    public function countBy(array $filters = []): int
    {
        // The countBy method will ignore any page and limit parameters
        // for better developer experience we will strip them away here
        // instead of that the developer need to take that into account
        // in there call of the countBy method.
        unset($filters['page']); // @phpstan-ignore-line
        unset($filters['limit']); // @phpstan-ignore-line

        /**
         * @see https://github.com/phpstan/phpstan/issues/5223https://github.com/phpstan/phpstan/issues/5223
         *
         * @var array{
         *     id?: int,
         *     ids?: int[],
         *     locale?: string|null,
         *     stage?: string|null,
         *     categoryIds?: int[],
         *     categoryKeys?: string[],
         *     categoryOperator?: 'AND'|'OR',
         *     tagIds?: int[],
         *     tagNames?: string[],
         *     tagOperator?: 'AND'|'OR',
         *     templateKeys?: string[],
         * } $filters */
        $queryBuilder = $this->createQueryBuilder($filters);

        $queryBuilder->select('COUNT(DISTINCT example.id)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array{
     *     id?: int,
     *     ids?: int[],
     *     locale?: string|null,
     *     stage?: string|null,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     page?: int,
     *     limit?: int,
     * } $filters
     * @param array{
     *     id?: 'asc'|'desc',
     *     title?: 'asc'|'desc',
     * } $sortBy
     * @param array{
     *     example_admin?: bool,
     *     example_website?: bool,
     *     with-example-content?: bool|array<string, mixed>,
     * } $selects
     *
     * @return \Generator<Example>
     */
    public function findBy(array $filters = [], array $sortBy = [], array $selects = []): \Generator
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy, $selects);

        // TODO optimize hydration with toIterable()
        /** @var iterable<Example> $examples */
        $examples = $queryBuilder->getQuery()->getResult();

        foreach ($examples as $example) {
            yield $example;
        }
    }

    public function add(Example $example): void
    {
        $this->entityManager->persist($example);
    }

    public function remove(Example $example): void
    {
        $this->entityManager->remove($example);
    }

    /**
     * @param array{
     *     id?: int,
     *     ids?: int[],
     *     locale?: string|null,
     *     stage?: string|null,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     page?: int,
     *     limit?: int,
     * } $filters
     * @param array{
     *     id?: 'asc'|'desc',
     *     title?: 'asc'|'desc',
     * } $sortBy
     * @param array{
     *     example_admin?: bool,
     *     example_website?: bool,
     *     with-example-content?: bool|array<string, mixed>,
     * } $selects
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

        $queryBuilder = $this->entityRepository->createQueryBuilder('example');

        $id = $filters['id'] ?? null;
        if (null !== $id) {
            Assert::integer($id);
            $queryBuilder->andWhere('example.id = :id')
                ->setParameter('id', $id);
        }

        $ids = $filters['ids'] ?? null;
        if (null !== $ids) {
            Assert::isArray($ids);
            $queryBuilder->andWhere('example.id IN(:ids)')
                ->setParameter('ids', $ids);
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

        if (\array_key_exists('locale', $filters) // should also work with locale = null
            && \array_key_exists('stage', $filters)) {
            $this->dimensionContentQueryEnhancer->addFilters(
                $queryBuilder,
                'example',
                ExampleDimensionContent::class,
                $filters
            );
        }

        if ($selects['with-example-content'] ?? null) {
            $contentSelects = $selects['with-example-content'];

            $queryBuilder->leftJoin(
                'example.dimensionContents',
                'dimensionContent'
            );

            $this->dimensionContentQueryEnhancer->addSelects(
                $queryBuilder,
                ExampleDimensionContent::class,
                $filters,
                $contentSelects
            );
        }

        $locale = $filters['locale'] ?? null;
        if ($selects['with-example-translations'] ?? null) {
            Assert::notNull($locale);

            $queryBuilder->leftJoin(
                'example.translations',
                'translations',
                Join::WITH,
                'translation.locale = :locale'
            )->setParameter('locale', $locale);
        }

        return $queryBuilder;
    }
}
