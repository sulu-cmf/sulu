<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup\Link;

/**
 * Contains all providers.
 */
class LinkProviderPool implements LinkProviderPoolInterface
{
    /**
     * @var array<string, LinkProviderInterface>
     */
    private $providers;

    /**
     * @param iterable<string, LinkProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = [...$providers];
    }

    public function getProvider($name)
    {
        if (!$this->hasProvider($name)) {
            throw new ProviderNotFoundException($name, \array_keys($this->providers));
        }

        return $this->providers[$name];
    }

    public function hasProvider($name)
    {
        return \array_key_exists($name, $this->providers);
    }

    public function getConfiguration()
    {
        $configuration = [];
        foreach ($this->providers as $name => $provider) {
            $configuration[$name] = $provider->getConfiguration();
        }

        return \array_filter($configuration);
    }
}
