<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Routing\Defaults;

/**
 * Combines multiple defaults-provider.
 */
class RouteDefaultsProvider implements RouteDefaultsProviderInterface
{
    /**
     * @var array<string, RouteDefaultsProviderInterface>
     */
    private $defaultsProviderMap = [];

    /**
     * @param iterable<RouteDefaultsProviderInterface> $defaultsProvider
     */
    public function __construct(private iterable $defaultsProvider)
    {
    }

    public function getByEntity($entityClass, $id, $locale, $object = null)
    {
        if (!$this->supports($entityClass)) {
            return;
        }

        return $this->getDefaultProvider($entityClass)->getByEntity($entityClass, $id, $locale, $object);
    }

    public function isPublished($entityClass, $id, $locale)
    {
        return $this->getDefaultProvider($entityClass)->isPublished($entityClass, $id, $locale);
    }

    public function supports($entityClass)
    {
        return null !== $this->getDefaultProvider($entityClass);
    }

    /**
     * @param string $entityClass
     */
    private function getDefaultProvider($entityClass)
    {
        if (\array_key_exists($entityClass, $this->defaultsProviderMap)) {
            return $this->defaultsProviderMap[$entityClass];
        }

        foreach ($this->defaultsProvider as $defaultsProvider) {
            if ($defaultsProvider->supports($entityClass)) {
                return $this->defaultsProviderMap[$entityClass] = $defaultsProvider;
            }
        }
    }
}
