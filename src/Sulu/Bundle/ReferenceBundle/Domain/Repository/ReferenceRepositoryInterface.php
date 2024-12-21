<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Domain\Repository;

use Sulu\Bundle\ReferenceBundle\Domain\Exception\ReferenceNotFoundException;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;

/**
 * @phpstan-type ReferenceFilters array{
 *     id?: int,
 *     resourceId?: string,
 *     resourceKey?: string,
 *     referenceResourceKey?: string,
 *     referenceResourceId?: string,
 *     referenceLocale?: string,
 *     referenceContext?: string,
 *     changedOlderThan?: \DateTimeInterface,
 *     limit?: int,
 *     offset?: int,
 * }
 * @phpstan-type ReferenceSortBys array<string, 'asc'|'desc'>
 * @phpstan-type ReferenceFields string[]
 */
interface ReferenceRepositoryInterface
{
    /**
     * @param array<string, string> $referenceRouterAttributes
     */
    public function create(
        string $resourceKey,
        string $resourceId,
        string $referenceResourceKey,
        string $referenceResourceId,
        string $referenceLocale,
        string $referenceTitle,
        string $referenceContext,
        string $referenceProperty,
        array $referenceRouterAttributes = []
    ): ReferenceInterface;

    /**
     * @param ReferenceFilters $filters
     *
     * @throws ReferenceNotFoundException
     */
    public function getOneBy(array $filters): ReferenceInterface;

    /**
     * @param ReferenceFilters $filters
     */
    public function findOneBy(array $filters): ?ReferenceInterface;

    /**
     * @param ReferenceFilters $filters
     * @param ReferenceSortBys $sortBys
     * @param ReferenceFields $fields
     *
     * @return iterable<array{
     *     referenceTitle?: string,
     *     referenceResourceKey?: string,
     *     referenceResourceId?: string,
     *     referenceRouterAttributes?: array<string, string>,
     *     referenceContext?: string,
     *     referenceProperty?: string,
     * }>
     */
    public function findFlatBy(array $filters = [], array $sortBys = [], array $fields = [], bool $distinct = false): iterable;

    /**
     * @param ReferenceFilters $filters
     * @param ReferenceFields $distinctFields
     */
    public function count(array $filters = [], array $distinctFields = []): int;

    public function add(ReferenceInterface $reference): void;

    public function remove(ReferenceInterface $reference): void;

    /**
     * @param ReferenceFilters $filters
     */
    public function removeBy(array $filters): void;

    /**
     * @internal
     */
    public function flush(): void;
}
