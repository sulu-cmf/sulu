<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Tests\Application;

use Sulu\Bundle\AutomationBundle\SuluAutomationBundle;
use Sulu\Bundle\SnippetBundle\SuluSnippetBundle as DeprecatedSuluSnippetBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\SuluContentBundle;
use Sulu\Content\Tests\Application\ExampleTestBundle\ExampleTestBundle;
use Sulu\Snippet\Infrastructure\Symfony\HttpKernel\SuluSnippetBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Task\TaskBundle\TaskBundle;

/**
 * AppKernel for functional tests.
 */
class Kernel extends SuluTestKernel
{
    /**
     * @var string|null
     */
    private $config = 'default';

    public function __construct(string $environment, bool $debug, string $suluContext = SuluKernel::CONTEXT_ADMIN)
    {
        $environmentParts = \explode('_', $environment, 2);
        $environment = $environmentParts[0];
        $this->config = $environmentParts[1] ?? $this->config;

        parent::__construct($environment, $debug, $suluContext);
    }

    public function registerBundles(): iterable
    {
        $bundles = [...parent::registerBundles()];

        foreach ($bundles as $key => $bundle) {
            // remove old route bundle to avoid conflicts
            if (DeprecatedSuluSnippetBundle::class === $bundle::class) {
                unset($bundles[$key]);
            }
        }

        $bundles[] = new SuluContentBundle();
        $bundles[] = new SuluSnippetBundle();
        $bundles[] = new ExampleTestBundle(); // TODO currently required for test content bundle, everybody should setup database by its own
        $bundles[] = new SuluAutomationBundle(); // TODO currently required for test content bundle, everybody should setup database by its own
        $bundles[] = new TaskBundle(); // TODO currently required for test content bundle, everybody should setup database by its own

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(__DIR__ . '/config/config.yml');

        if (\file_exists(__DIR__ . '/config/config_' . $this->config . '.yml')) {
            $loader->load(__DIR__ . '/config/config_' . $this->config . '.yml');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();

        return $parameters;
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir() . '/' . $this->config;
    }

    public function getCommonCacheDir(): string
    {
        return parent::getCommonCacheDir() . '/' . $this->config;
    }
}
