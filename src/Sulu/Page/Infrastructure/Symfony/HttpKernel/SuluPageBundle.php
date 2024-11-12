<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Symfony\HttpKernel;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Preview\ContentObjectProvider;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Page\Application\Mapper\PageContentMapper;
use Sulu\Page\Application\Mapper\PageMapperInterface;
use Sulu\Page\Application\MessageHandler\ApplyWorkflowTransitionPageMessageHandler;
use Sulu\Page\Application\MessageHandler\CopyLocalePageMessageHandler;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Application\MessageHandler\ModifyPageMessageHandler;
use Sulu\Page\Application\MessageHandler\RemovePageMessageHandler;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Doctrine\Repository\PageRepository;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Sulu\Page\UserInterface\Controller\Admin\PageController;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use Symfony\Component\Serializer\SerializerInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SuluPageBundle extends AbstractBundle
{
    use PersistenceBundleTrait;
    use PersistenceExtensionTrait;

    protected string $name = 'SuluNextPageBundle';
    protected string $extensionAlias = 'sulu_next_page';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('page')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Page::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('page_dimension_content')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(PageDimensionContent::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('sulu_admin')) {
            $builder->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            \dirname(__DIR__, 2) . '/Sulu/config/lists',
                        ],
                    ],
                    'resources' => [
                        'pages' => [
                            'routes' => [
                                'list' => 'sulu_page.get_pages',
                                'detail' => 'sulu_page.get_page',
                            ],
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('doctrine')) {
            $builder->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'SuluPage' => [
                                'type' => 'xml',
                                'prefix' => 'Sulu\Page\Domain\Model',
                                'dir' => \dirname(__DIR__, 2) . '/Doctrine/config',
                                'alias' => 'SuluPage',
                            ],
                        ],
                    ],
                ],
            );
        }
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $this->configurePersistence($config['objects'], $builder);

        $services = $container->services();

        // Define autoconfigure interfaces for mappers
        $builder->registerForAutoconfiguration(PageMapperInterface::class)
            ->addTag('sulu_page.page_mapper');

        // Message Handler services
        $services->set(CreatePageMessageHandler::class)
            ->args([
                new Reference(PageRepositoryInterface::class),
                tagged_iterator(PageMapperInterface::TAG_NAME),
            ])
            ->tag('messenger.message_handler');

        $services->set(ModifyPageMessageHandler::class)
            ->args([
                new Reference(PageRepositoryInterface::class),
                tagged_iterator(PageMapperInterface::TAG_NAME),
            ])
            ->tag('messenger.message_handler');

        $services->set(RemovePageMessageHandler::class)
            ->args([
                new Reference(PageRepositoryInterface::class),
            ])
            ->tag('messenger.message_handler');

        $services->set(ApplyWorkflowTransitionPageMessageHandler::class)
            ->args([
                new Reference(PageRepositoryInterface::class),
                new Reference(ContentWorkflowInterface::class),
            ])
            ->tag('messenger.message_handler');

        $services->set(CopyLocalePageMessageHandler::class)
            ->args([
                new Reference(PageRepositoryInterface::class),
                new Reference(ContentCopierInterface::class),
            ])
            ->tag('messenger.message_handler');

        // Mapper service
        $services->set(PageContentMapper::class)
            ->args([
                new Reference(ContentPersisterInterface::class),
            ])
            ->tag(PageMapperInterface::TAG_NAME);

        // Sulu Integration service
        $services->set('sulu_page.page_admin')
            ->class(PageAdmin::class)
            ->args([
                new Reference('sulu_admin.view_builder_factory'),
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('sulu_security.security_checker'),
                new Reference('sulu_activity.activity_list_view_builder_factory'),
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu.admin');

        // Repositories services
        $services->set(PageRepository::class)
            ->args([
                new Reference(EntityManagerInterface::class),
                new Reference(DimensionContentQueryEnhancer::class),
            ]);

        $services->alias(PageRepositoryInterface::class, PageRepository::class);

        // Controllers services
        $services->set(PageController::class)
            ->public()
            ->args([
                new Reference(PageRepositoryInterface::class),
                new Reference('sulu_message_bus'),
                new Reference(SerializerInterface::class),
                // additional services to be removed when no longer needed
                new Reference(ContentManagerInterface::class),
                new Reference('sulu_core.list_builder.field_descriptor_factory'),
                new Reference('sulu_core.doctrine_list_builder_factory'),
                new Reference(RestHelperInterface::class),
                new Reference(EntityManagerInterface::class),
            ])
            ->tag('sulu.context', ['context' => 'admin']);

        // Preview service
        $services->set('sulu_page.page_preview_provider')
            ->class(ContentObjectProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.content_resolver'),
                new Reference('sulu_content.content_data_mapper'),
                '%sulu.model.page.class%',
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu_preview.object_provider', ['provider-key' => 'pages']);
    }

    public function build(ContainerBuilder $container): void
    {
        $this->buildPersistence(
            [
                PageInterface::class => 'sulu.model.page.class',
                PageDimensionContentInterface::class => 'sulu.model.page_dimension_content.class',
            ],
            $container
        );
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
