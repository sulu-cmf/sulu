<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Cache;

use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\WebsiteBundle\Event\CacheClearEvent;
use Sulu\Bundle\WebsiteBundle\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Clear http_cache for website.
 */
class CacheClearer implements CacheClearerInterface
{
    /**
     * @var string
     */
    private $kernelEnvironment;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $varDir;

    /**
     * @var null|CacheManager
     */
    private $cacheManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        Filesystem $filesystem,
        $kernelEnvironment,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        string $varDir,
        ?CacheManager $cacheManager
    ) {
        $this->kernelEnvironment = $kernelEnvironment;
        $this->filesystem = $filesystem;
        $this->varDir = $varDir;
        $this->cacheManager = $cacheManager;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function clear()
    {
        if ($this->cacheManager && $this->cacheManager->supportsInvalidate()) {
            $request = $this->requestStack->getCurrentRequest();
            if (!$request) {
                return;
            }

            $this->cacheManager->invalidateDomain($request->getHost());

            $this->eventDispatcher->dispatch(new CacheClearEvent(), Events::CACHE_CLEAR);

            return;
        }

        $path = \sprintf(
            '%s/cache/common/%s/http_cache',
            $this->varDir,
            $this->kernelEnvironment
        );

        if ($this->filesystem->exists($path)) {
            // rename directory before removing it to prevent new requests from writing into the old directory
            $invalidatedPath = $path . '_invalidated';
            $this->filesystem->rename($path, $invalidatedPath, true);
            $this->filesystem->remove($invalidatedPath);
        }

        $this->eventDispatcher->dispatch(new CacheClearEvent(), Events::CACHE_CLEAR);
    }
}
