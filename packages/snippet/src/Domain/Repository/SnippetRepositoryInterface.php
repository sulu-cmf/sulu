<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Domain\Repository;

use Sulu\Snippet\Domain\Exception\SnippetNotFoundException;
use Sulu\Snippet\Domain\Model\SnippetInterface;

/**
 * Implementation can be found in the following class:.
 *
 * @see Sulu\Snippet\Infrastructure\Doctrine\Repository\SnippetRepository
 */
interface SnippetRepositoryInterface
{
    /**
     * Groups are used in controllers and represents serialization / resolver group,
     * this allows that no controller need to be overwritten when something additional should be
     * loaded at that endpoint.
     */
    public const GROUP_SELECT_SNIPPET_ADMIN = 'snippet_admin';
    public const GROUP_SELECT_SNIPPET_WEBSITE = 'snippet_website';

    /**
     * Withs represents additional selects which can be load to join and select specific sub entities.
     * They are used by groups.
     */
    public const SELECT_SNIPPET_CONTENT = 'with-snippet-content';

    public function createNew(?string $uuid = null): SnippetInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     load_ghost_content?: bool,
     * } $filters
     * @param array{
     *     snippet_admin?: bool,
     *     snippet_website?: bool,
     *     with-snippet-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     *
     * @throws SnippetNotFoundException
     */
    public function getOneBy(array $filters, array $selects = []): SnippetInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     * } $filters
     * @param array{
     *     snippet_admin?: bool,
     *     snippet_website?: bool,
     *     with-snippet-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     */
    public function findOneBy(array $filters, array $selects = []): ?SnippetInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
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
     *     snippet_admin?: bool,
     *     snippet_website?: bool,
     *     with-snippet-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     *
     * @return iterable<SnippetInterface>
     */
    public function findBy(array $filters = [], array $sortBy = [], array $selects = []): iterable;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
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
     *
     * @return iterable<string>
     */
    public function findIdentifiersBy(array $filters = [], array $sortBy = []): iterable;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     * } $filters
     */
    public function countBy(array $filters = []): int;

    public function add(SnippetInterface $snippet): void;

    public function remove(SnippetInterface $snippet): void;
}
