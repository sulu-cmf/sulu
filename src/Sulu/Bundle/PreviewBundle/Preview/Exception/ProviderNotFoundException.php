<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Exception;

/**
 * Indicates a missing provider.
 */
class ProviderNotFoundException extends PreviewException
{
    private string $providerKey;

    public function __construct(string $providerKey)
    {
        parent::__construct(\sprintf('No provider found for key "%s"', $providerKey), 9900);

        $this->providerKey = $providerKey;
    }

    public function getProviderKey(): string
    {
        return $this->providerKey;
    }
}
