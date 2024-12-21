<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Application;

use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AppCache extends SuluHttpCache implements KernelInterface
{
    public function __construct(KernelInterface $kernel, $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);
    }

    public function serialize()
    {
        return $this->kernel->serialize();
    }

    public function unserialize($serialized): void
    {
        $this->kernel->unserialize($serialized);
    }

    public function registerBundles(): iterable
    {
        return $this->kernel->registerBundles();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $this->kernel->registerContainerConfiguration($loader);
    }

    public function boot(): void
    {
        $this->kernel->boot();
    }

    public function shutdown(): void
    {
        $this->kernel->shutdown();
    }

    public function getBundles(): array
    {
        return $this->kernel->getBundles();
    }

    public function getBundle($name, $first = true): BundleInterface
    {
        return $this->kernel->getBundle($name, $first);
    }

    public function locateResource($name, $dir = null, $first = true): string
    {
        return $this->kernel->locateResource($name, $dir, $first);
    }

    public function getName()
    {
        return $this->kernel->getName();
    }

    public function getEnvironment(): string
    {
        return $this->kernel->getEnvironment();
    }

    public function isDebug(): bool
    {
        return $this->kernel->isDebug();
    }

    public function getProjectDir(): string
    {
        return $this->kernel->getProjectDir();
    }

    public function getContainer(): ContainerInterface
    {
        return $this->kernel->getContainer();
    }

    public function getStartTime(): float
    {
        return $this->kernel->getStartTime();
    }

    public function getCacheDir(): string
    {
        return $this->kernel->getCacheDir();
    }

    public function getBuildDir(): string
    {
        return $this->kernel->getBuildDir();
    }

    public function getLogDir(): string
    {
        return $this->kernel->getLogDir();
    }

    public function getCharset(): string
    {
        return $this->kernel->getCharset();
    }
}
