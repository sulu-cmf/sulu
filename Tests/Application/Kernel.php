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
use Sulu\Bundle\HeadlessBundle\SuluHeadlessBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * AppKernel for functional tests.
 */
class Kernel extends SuluTestKernel implements CompilerPassInterface
{
    public function registerBundles(): iterable
    {
        $bundles = parent::registerBundles();
        $bundles[] = new SuluArticleBundle();
        $bundles[] = new ONGRElasticsearchBundle();
        $bundles[] = new SuluHeadlessBundle();

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
        $type = 'default';
        if (\getenv('ARTICLE_TEST_CASE')) {
            $type = \getenv('ARTICLE_TEST_CASE');
        }

        $loader->load(__DIR__ . '/config/config_' . $type . '.yml');
    }

    public function process(ContainerBuilder $container)
    {
        // Make some services which were inlined in optimization
        $container->getDefinition('sulu_article.content.page_tree_data_provider')
            ->setPublic(true);

        $container->getDefinition('sulu_article.elastic_search.article_indexer')
            ->setPublic(true);
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
}
