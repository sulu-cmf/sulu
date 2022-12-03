<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Application;

use ONGR\ElasticsearchBundle\ONGRElasticsearchBundle;
use Sulu\Bundle\ArticleBundle\SuluArticleBundle;
use Sulu\Bundle\ArticleBundle\Tests\TestExtendBundle\TestExtendBundle;
use Sulu\Bundle\ContentBundle\SuluContentBundle;
use Sulu\Bundle\HeadlessBundle\SuluHeadlessBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Messenger\Infrastructure\Symfony\HttpKernel\SuluMessengerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * AppKernel for functional tests.
 */
class Kernel extends SuluTestKernel implements CompilerPassInterface
{
    /**
     * @var string|null
     */
    private $config = 'phpcr_storage';

    public function __construct(string $environment, bool $debug, string $suluContext = SuluKernel::CONTEXT_ADMIN)
    {
        $environmentParts = \explode('_', $environment, 2);
        $environment = $environmentParts[0];
        $this->config = $environmentParts[1] ?? $this->config;

        parent::__construct($environment, $debug, $suluContext);
    }

    public function registerBundles(): iterable
    {
        $bundles = parent::registerBundles();
        $bundles[] = new SuluArticleBundle();

        if ('phpcr_storage' === $this->config) {
            $bundles[] = new ONGRElasticsearchBundle();
            $bundles[] = new SuluHeadlessBundle();
        }

        if ('experimental_storage' === $this->config) {
            $bundles[] = new SuluContentBundle();
            $bundles[] = new SuluMessengerBundle();
        }

        if ('extend' === \getenv('ARTICLE_TEST_CASE')) {
            $bundles[] = new TestExtendBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        if ('jackrabbit' === \getenv('PHPCR_TRANSPORT')) {
            $loader->load(__DIR__ . '/config/versioning.yml');
        }

        $loader->load(__DIR__ . '/config/config.yml');
        $loader->load(__DIR__ . '/config/config_' . $this->config . '.yml');

        if ('phpcr_storage' === $this->config) {
            $type = 'default';
            if (\getenv('ARTICLE_TEST_CASE')) {
                $type = \getenv('ARTICLE_TEST_CASE');
            }

            $loader->load(__DIR__ . '/config/config_' . $type . '.yml');
        }
    }

    public function process(ContainerBuilder $container)
    {
        if ('phpcr_storage' === $this->config) {
            // Make some services which were inlined in optimization
            $container->getDefinition('sulu_article.content.page_tree_data_provider')
                ->setPublic(true);

            $container->getDefinition('sulu_article.elastic_search.article_indexer')
                ->setPublic(true);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();

        $gedmoReflection = new \ReflectionClass(\Gedmo\Exception::class);
        $parameters['gedmo_directory'] = \dirname($gedmoReflection->getFileName());

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
