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
 * @final
 *
 * @internal
 *
 * @experimental
 */
class SingleSignOnAdapterFactory
{
    /**
     * @var array<string, SingleSignOnAdapterFactoryInterface>
     */
    private array $factories = [];

    /**
     * @param iterable<string, SingleSignOnAdapterFactoryInterface> $factories
     */
    public function __construct(
        iterable $factories,
    ) {
        foreach ($factories as $factory) {
            $this->factories[$factory->getName()] = $factory;
        }
    }

    /**
     * @internal
     *
     * @return array{
     *     scheme: string,
     *     host: string,
     *     port?: int,
     *     user?: string,
     *     pass?: string,
     *     path?: string,
     *     query: array<string, string>,
     *     fragment?: string,
     * }
     *
     * Inspired by https://github.com/schranz-search/schranz-search/blob/0.3.1/packages/seal/src/Adapter/AdapterFactory.php
     */
    private function parseDsn(#[\SensitiveParameter] string $dsn): array
    {
        /** @var string|null $adapterName */
        $adapterName = \explode(':', $dsn, 2)[0];

        if (!$adapterName) {
            throw new \InvalidArgumentException(
                'Invalid DSN: "' . $dsn . '".',
            );
        }

        if (!isset($this->factories[$adapterName])) {
            throw new \InvalidArgumentException(
                'Unknown adapter: "' . $adapterName . '" available adapters are "' . \implode('", "', \array_keys($this->factories)) . '".',
            );
        }

        /**
         * @var array{
         *     scheme: string,
         *     host: string,
         *     port?: int,
         *     user?: string,
         *     pass?: string,
         *     path?: string,
         *     query?: string,
         *     fragment?: string,
         * }|false $parsedDsn
         */
        $parsedDsn = \parse_url($dsn);

        // make DSN like scheme://username:lastname parseable
        if (false === $parsedDsn) {
            $query = '';
            if (\str_contains($dsn, '?')) {
                [$dsn, $query] = \explode('?', $dsn);
                $query = '?' . $query;
            }

            if (\str_contains($dsn, ':///')) {
                // make DSN like scheme:///full/path/project/indexes parseable
                $dsn = \str_replace(':///', '://' . $adapterName . '/', $dsn);
            } else {
                $dsn = $dsn . '@' . $adapterName . $query;
            }

            /**
             * @var array{
             *     scheme: string,
             *     host: string,
             *     port?: int,
             *     user?: string,
             *     pass?: string,
             *     path?: string,
             *     query?: string,
             *     fragment?: string,
             * } $parsedDsn
             */
            $parsedDsn = \parse_url($dsn);

            $parsedDsn['host'] = '';
        }

        /** @var array<string, string> $query */
        $query = [];
        if (isset($parsedDsn['query'])) {
            \parse_str($parsedDsn['query'], $query);
        }

        $parsedDsn['query'] = $query;

        /**
         * @var array{
         *     scheme: string,
         *     host: string,
         *     port?: int,
         *     user?: string,
         *     pass?: string,
         *     path?: string,
         *     query: array<string, string>,
         *     fragment?: string,
         * } $parsedDsn
         */
        return $parsedDsn;
    }

    public function createAdapter(#[\SensitiveParameter] string $dsn, string $defaultRoleKey): SingleSignOnAdapterInterface
    {
        $parsedDsn = $this->parseDsn($dsn);

        return $this->factories[$parsedDsn['scheme']]->createAdapter($parsedDsn, $defaultRoleKey);
    }
}
