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

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class Kernel extends SuluTestKernel
{
    /**
     * @var string
     */
    private $appContext;

    /**
     * @param string $environment
     * @param bool $debug
     * @param string $suluContext
     */
    public function __construct($environment, $debug, $suluContext = self::CONTEXT_ADMIN)
    {
        $envParts = \explode('_', $environment, 2);
        $this->appContext = $envParts[1] ?? '';

        parent::__construct($envParts[0], $debug, $suluContext);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $context = $this->getContext();
        $loader->load(__DIR__ . '/config/config_' . $context . '.yml');
        if ('' !== $this->appContext) {
            $loader->load(__DIR__ . '/config/config_' . $this->appContext . '.yml');

            if ('with_security' === $this->appContext
                && \version_compare(Kernel::VERSION, '6.0.0', '<')
            ) {
                $loader->load(__DIR__ . '/config/config_with_security-5-4.yml');
            }
        }
    }

    public function registerBundles(): iterable
    {
        $bundles = [...parent::registerBundles()];

        if ('with_security' === $this->appContext) {
            $bundles[] = new SecurityBundle();
        }

        return $bundles;
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir() . \ltrim('/' . $this->appContext);
    }

    public function getCommonCacheDir(): string
    {
        return parent::getCommonCacheDir() . \ltrim('/' . $this->appContext);
    }
}
