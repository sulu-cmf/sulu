<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Object;

use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;

class PreviewObjectProviderRegistry implements PreviewObjectProviderRegistryInterface
{
    /**
     * @var array<string, PreviewObjectProviderInterface>
     */
    private $previewObjectProviders;

    /**
     * @param iterable<string, PreviewObjectProviderInterface> $previewObjectProviders
     */
    public function __construct(iterable $previewObjectProviders)
    {
        $this->previewObjectProviders = [...$previewObjectProviders];
    }

    public function getPreviewObjectProviders(): array
    {
        return $this->previewObjectProviders;
    }

    public function getPreviewObjectProvider(string $providerKey): PreviewObjectProviderInterface
    {
        if (!$this->hasPreviewObjectProvider($providerKey)) {
            throw new ProviderNotFoundException($providerKey);
        }

        return $this->previewObjectProviders[$providerKey];
    }

    public function hasPreviewObjectProvider(string $providerKey): bool
    {
        return \array_key_exists($providerKey, $this->previewObjectProviders);
    }
}
