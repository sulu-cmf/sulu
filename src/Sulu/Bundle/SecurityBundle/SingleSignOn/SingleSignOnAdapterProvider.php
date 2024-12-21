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

use Psr\Container\ContainerInterface;

/**
 * @final
 *
 * @internal
 *
 * @experimental
 */
class SingleSignOnAdapterProvider
{
    public function __construct(
        private ContainerInterface $adapters,
    ) {
    }

    public function getAdapterByDomain(string $domain): ?SingleSignOnAdapterInterface
    {
        if (!$this->adapters->has($domain)) {
            return null;
        }

        /** @var SingleSignOnAdapterInterface */
        return $this->adapters->get($domain);
    }
}
