<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Symfony\HttpKernel;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\DependencyInjection\SuluAdminExtension;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Factory\DimensionContentCollectionFactoryInterface;
use Sulu\Content\Domain\Repository\DimensionContentRepositoryInterface;
use Sulu\Content\Infrastructure\Doctrine\MetadataLoader;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\SuluContentBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Loader\DefinitionFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SuluContentBundleTest extends AbstractExtensionTestCase
{
    protected function getContentBundle(): SuluContentBundle
    {
        return new SuluContentBundle();
    }

    protected function getContainerExtensions(): array
    {
        $extension = $this->getContentBundle()->getContainerExtension();
        $this->assertNotNull($extension);

        return [
            $extension,
        ];
    }

    public function testCompilerPass(): void
    {
        $bundle = $this->getContentBundle();
        $containerBuilder = new ContainerBuilder();

        $passConfig = $containerBuilder->getCompiler()->getPassConfig();
        $beforeCount = \count($passConfig->getPasses());

        $bundle->build($containerBuilder);
        $passConfig = $containerBuilder->getCompiler()->getPassConfig();

        $this->assertSame(
            1,
            \count($passConfig->getPasses()) - $beforeCount
        );
    }

    public function testIsConfigurationEmpty(): void
    {
        $contentBundle = $this->getContentBundle();
        $treeBuilder = new TreeBuilder('sulu_content');
        $fileLocator = new FileLocator(__DIR__ . '/config');
        $definitionLoader = new DefinitionFileLoader(
            $treeBuilder,
            $fileLocator,
        );

        $definitionConfigurator = new DefinitionConfigurator(
            $treeBuilder,
            $definitionLoader,
            __DIR__ . '/config',
            '',
        );

        $contentBundle->configure($definitionConfigurator);

        $tree = $treeBuilder->buildTree();

        $this->assertSame([
            'sulu_content' => [],
        ], [
            $tree->getName() => $tree->finalize([]),
        ]);
    }

    public function testLoad(): void
    {
        $this->container->setParameter('kernel.environment', 'test');
        $this->container->setParameter('kernel.build_dir', \dirname(__DIR__, 4) . '/Application/var/cache/builddir');

        $this->load();

        $this->assertContainerBuilderHasService('sulu_content.metadata_loader', MetadataLoader::class);

        // Main services aliases
        $this->assertContainerBuilderHasAlias(ContentManagerInterface::class, 'sulu_content.content_manager');
        $this->assertContainerBuilderHasAlias(ContentAggregatorInterface::class, 'sulu_content.content_aggregator');
        $this->assertContainerBuilderHasAlias(ContentPersisterInterface::class, 'sulu_content.content_persister');
        $this->assertContainerBuilderHasAlias(ContentNormalizerInterface::class, 'sulu_content.content_normalizer');
        $this->assertContainerBuilderHasAlias(ContentCopierInterface::class, 'sulu_content.content_copier');
        $this->assertContainerBuilderHasAlias(ContentWorkflowInterface::class, 'sulu_content.content_workflow');

        // Additional services aliases
        $this->assertContainerBuilderHasAlias(ContentViewBuilderFactoryInterface::class, 'sulu_content.content_view_builder_factory');
        $this->assertContainerBuilderHasAlias(DimensionContentCollectionFactoryInterface::class, 'sulu_content.dimension_content_collection_factory');
        $this->assertContainerBuilderHasAlias(DimensionContentRepositoryInterface::class, 'sulu_content.dimension_content_repository');
    }

    public function testPrepend(): void
    {
        $extension = $this->getContentBundle();

        $containerBuilder = new ContainerBuilder();
        $instanceof = [];
        $containerConfigurator = new ContainerConfigurator(
            $containerBuilder,
            new PhpFileLoader($containerBuilder, new FileLocator(__DIR__ . '/config'), 'test'),
            $instanceof,
            __DIR__ . '/config',
            '',
            'test',
        );
        $containerBuilder->setParameter('kernel.debug', true);

        $doctrineExtension = new DoctrineExtension();
        $containerBuilder->registerExtension($doctrineExtension);

        $doctrineExtension = new SuluAdminExtension();
        $containerBuilder->registerExtension($doctrineExtension);
        $extension->prependExtension($containerConfigurator, $containerBuilder);

        $this->assertSame([
            [
                'forms' => [
                    'directories' => [
                        \dirname(__DIR__, 5) . '/config/forms',
                    ]
                ],
            ],
        ], $containerBuilder->getExtensionConfig('sulu_admin'));
    }
}
