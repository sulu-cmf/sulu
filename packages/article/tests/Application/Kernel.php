<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Tests\Application;

use Sulu\Article\Infrastructure\Symfony\HttpKernel\SuluArticleBundle;
use Sulu\Bundle\AutomationBundle\SuluAutomationBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\SuluContentBundle;
use Sulu\Content\Tests\Application\ExampleTestBundle\ExampleTestBundle;
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
        $bundles[] = new SuluContentBundle();
        $bundles[] = new SuluArticleBundle();
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
