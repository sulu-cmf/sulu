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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\AdminBundle\DependencyInjection\SuluAdminExtension;
use Sulu\Bundle\ContentBundle\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\DimensionContentCollectionFactoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Repository\DimensionContentRepositoryInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine\MetadataLoader;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;
use Sulu\Bundle\ContentBundle\DependencyInjection\SuluContentExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SuluContentExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            $this->getExtension(),
        ];
    }

    protected function getExtension(): SuluContentExtension
    {
        return new SuluContentExtension();
    }

    public function testLoad(): void
    {
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
        $extension = $this->getExtension();
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.debug', true);

        $doctrineExtension = new DoctrineExtension();
        $containerBuilder->registerExtension($doctrineExtension);

        $doctrineExtension = new SuluAdminExtension();
        $containerBuilder->registerExtension($doctrineExtension);
        $extension->prepend($containerBuilder);

        $this->assertSame([
            [
                'forms' => [
                    'directories' => [
                        \dirname(__DIR__, 3) . '/Resources/config/forms',
                    ],
                ],
            ],
        ], $containerBuilder->getExtensionConfig('sulu_admin'));
    }
}
