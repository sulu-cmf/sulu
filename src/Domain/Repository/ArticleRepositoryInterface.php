<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Domain\Repository;

use Sulu\Article\Domain\Exception\ArticleNotFoundException;
use Sulu\Article\Domain\Model\ArticleInterface;

/**
 * Implementation can be found in the following class:.
 *
 * @see Sulu\Article\Infrastructure\Doctrine\Repository\ArticleRepository
 */
interface ArticleRepositoryInterface
{
    /**
     * Groups are used in controllers and represents serialization / resolver group,
     * this allows that no controller need to be overwritten when something additional should be
     * loaded at that endpoint.
     */
    public const GROUP_SELECT_ARTICLE_ADMIN = 'article_admin';
    public const GROUP_SELECT_ARTICLE_WEBSITE = 'article_website';

    /**
     * Withs represents additional selects which can be load to join and select specific sub entities.
     * They are used by groups.
     */
    public const SELECT_ARTICLE_CONTENT = 'with-article-content';

    public function createNew(?string $uuid = null): ArticleInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     load_ghost_content?: bool,
     * } $filters
     * @param array{
     *     article_admin?: bool,
     *     article_website?: bool,
     *     with-article-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     *
     * @throws ArticleNotFoundException
     */
    public function getOneBy(array $filters, array $selects = []): ArticleInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     * } $filters
     * @param array{
     *     article_admin?: bool,
     *     article_website?: bool,
     *     with-article-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     */
    public function findOneBy(array $filters, array $selects = []): ?ArticleInterface;

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
     *     article_admin?: bool,
     *     article_website?: bool,
     *     with-article-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     *
     * @return iterable<ArticleInterface>
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
     * } $filters
     */
    public function countBy(array $filters = []): int;

    public function add(ArticleInterface $article): void;

    public function remove(ArticleInterface $article): void;
}
