<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\SingleSignOn;

/**
 * @experimental
 */
interface SingleSignOnAdapterFactoryInterface
{
    /**
     * @param array{
     *     scheme: string,
     *     host: string,
     *     port?: int,
     *     user?: string,
     *     pass?: string,
     *     path?: string,
     *     query: array<string, string>,
     *     fragment?: string,
     * } $dsn
     */
    public function createAdapter(#[\SensitiveParameter] array $dsn, /* TODO check how to handle this */ string $defaultRoleKey): SingleSignOnAdapterInterface;

    /**
     * Returns the expected DSN scheme for this adapter.
     */
    public static function getName(): string;
}
