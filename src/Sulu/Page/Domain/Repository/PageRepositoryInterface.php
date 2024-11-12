<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Domain\Repository;

use Sulu\Page\Domain\Exception\PageNotFoundException;
use Sulu\Page\Domain\Model\PageInterface;

/**
 * Implementation can be found in the following class:.
 *
 * @see Sulu\Page\Infrastructure\Doctrine\Repository\PageRepository
 */
interface PageRepositoryInterface
{
    /**
     * Groups are used in controllers and represents serialization / resolver group,
     * this allows that no controller need to be overwritten when something additional should be
     * loaded at that endpoint.
     */
    public const GROUP_SELECT_CONTEXT_ADMIN = 'page_admin';
    public const GROUP_SELECT_CONTEXT_WEBSITE = 'page_website';

    /**
     * Withs represents additional selects which can be load to join and select specific sub entities.
     * They are used by groups.
     */
    public const SELECT_PAGE_CONTENT = 'with-page-content';

    public function createNew(?string $uuid = null): PageInterface;

    /**
     * @param array{
     *     article_admin?: bool,
     *     article_website?: bool,
     *     with-article-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     *
     * @throws PageNotFoundException
     */
    public function getOneBy(string $uuid, array $selects = []): PageInterface;

    public function getOneWithContentBy(string $uuid, array $dimensionAttributes, array $selects = []): PageInterface;

    public function add(PageInterface $Page): void;

    public function remove(PageInterface $Page): void;
}
