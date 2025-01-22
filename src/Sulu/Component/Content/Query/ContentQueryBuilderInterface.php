<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Query;

interface ContentQueryBuilderInterface
{
    /**
     * Build query.
     *
     * @param string $webspaceKey
     * @param string[] $locales
     *
     * @return array{
     *     0: string,
     *     1: array<string, array<array<string, string>>>
     * }
     */
    public function build($webspaceKey, $locales);

    /**
     * @return void
     */
    public function init(array $options);

    /**
     * Returns if unpublished pages are loaded.
     *
     * @return bool
     */
    public function getPublished();
}
