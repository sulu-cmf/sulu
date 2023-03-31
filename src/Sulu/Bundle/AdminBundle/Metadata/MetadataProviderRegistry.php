<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata;

use Sulu\Bundle\AdminBundle\Exception\MetadataProviderNotFoundException;

class MetadataProviderRegistry
{
    private array $metadataProviders = [];

    public function getMetadataProvider(string $type): MetadataProviderInterface
    {
        if (!\array_key_exists($type, $this->metadataProviders)) {
            throw new MetadataProviderNotFoundException($type);
        }

        return $this->metadataProviders[$type];
    }

    public function addMetadataProvider(string $type, MetadataProviderInterface $metadataProvider)
    {
        $this->metadataProviders[$type] = $metadataProvider;
    }
}
