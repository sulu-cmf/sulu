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

namespace Sulu\Snippet\Infrastructure\Symfony\HttpKernel;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Content\Infrastructure\Sulu\Search\ContentSearchMetadataProvider;
use Sulu\Snippet\Application\Mapper\SnippetContentMapper;
use Sulu\Snippet\Application\Mapper\SnippetMapperInterface;
use Sulu\Snippet\Application\MessageHandler\ApplyWorkflowTransitionSnippetMessageHandler;
use Sulu\Snippet\Application\MessageHandler\CopyLocaleSnippetMessageHandler;
use Sulu\Snippet\Application\MessageHandler\CreateSnippetMessageHandler;
use Sulu\Snippet\Application\MessageHandler\ModifySnippetMessageHandler;
use Sulu\Snippet\Application\MessageHandler\RemoveSnippetMessageHandler;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Sulu\Snippet\Infrastructure\Doctrine\Repository\SnippetRepository;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;
use Sulu\Snippet\Infrastructure\Sulu\Content\PropertyResolver\SingleSnippetSelectionPropertyResolver;
use Sulu\Snippet\Infrastructure\Sulu\Content\PropertyResolver\SnippetSelectionPropertyResolver;
use Sulu\Snippet\Infrastructure\Sulu\Content\ResourceLoader\SnippetResourceLoader;
use Sulu\Snippet\Infrastructure\Sulu\Content\SingleSnippetSelectionContentType;
use Sulu\Snippet\Infrastructure\Sulu\Content\SnippetDataProvider;
use Sulu\Snippet\Infrastructure\Sulu\Content\SnippetSelectionContentType;
use Sulu\Snippet\UserInterface\Controller\Admin\SnippetController;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @experimental
 *
 * @codeCoverageIgnore
 */
final class SuluSnippetBundle extends AbstractBundle
{
    use PersistenceExtensionTrait;
    use PersistenceBundleTrait;

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode() // @phpstan-ignore-line
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('snippet')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Snippet::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('snippet_content')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(SnippetDimensionContent::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<string, mixed> $config
     *
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $this->configurePersistence($config['objects'], $builder); // @phpstan-ignore-line

        $services = $container->services();

        // Define autoconfigure interfaces for mappers
        $builder->registerForAutoconfiguration(SnippetMapperInterface::class)
            ->addTag('sulu_snippet.snippet_mapper');

        // Message Handler services
        $services->set('sulu_snippet.create_snippet_handler')
            ->class(CreateSnippetMessageHandler::class)
            ->args([
                new Reference('sulu_snippet.snippet_repository'),
                tagged_iterator('sulu_snippet.snippet_mapper'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_snippet.modify_snippet_handler')
            ->class(ModifySnippetMessageHandler::class)
            ->args([
                new Reference('sulu_snippet.snippet_repository'),
                tagged_iterator('sulu_snippet.snippet_mapper'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_snippet.remove_snippet_handler')
            ->class(RemoveSnippetMessageHandler::class)
            ->args([
                new Reference('sulu_snippet.snippet_repository'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_snippet.apply_workflow_transition_snippet_handler')
            ->class(ApplyWorkflowTransitionSnippetMessageHandler::class)
            ->args([
                new Reference('sulu_snippet.snippet_repository'),
                new Reference('sulu_content.content_workflow'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_snippet.copy_locale_snippet_handler')
            ->class(CopyLocaleSnippetMessageHandler::class)
            ->args([
                new Reference('sulu_snippet.snippet_repository'),
                new Reference('sulu_content.content_copier'),
            ])
            ->tag('messenger.message_handler');

        // Mapper service
        $services->set('sulu_snippet.snippet_content_mapper')
            ->class(SnippetContentMapper::class)
            ->args([
                new Reference('sulu_content.content_persister'),
            ])
            ->tag('sulu_snippet.snippet_mapper');

        // Sulu Integration service
        $services->set('sulu_snippet.snippet_admin')
            ->class(SnippetAdmin::class)
            ->args([
                new Reference('sulu_admin.view_builder_factory'),
                new Reference('sulu_content.content_view_builder_factory'),
                new Reference('sulu_security.security_checker'),
                new Reference('sulu.core.localization_manager'),
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu.admin');

        // Repositories services
        $services->set('sulu_snippet.snippet_repository')
            ->class(SnippetRepository::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.dimension_content_query_enhancer'),
            ]);

        $services->alias(SnippetRepositoryInterface::class, 'sulu_snippet.snippet_repository');

        // Controllers services
        $services->set('sulu_snippet.admin_snippet_controller')
            ->class(SnippetController::class)
            ->public()
            ->args([
                new Reference('sulu_snippet.snippet_repository'),
                new Reference('sulu_message_bus'),
                new Reference('serializer'),
                // additional services to be removed when no longer needed
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_core.list_builder.field_descriptor_factory'),
                new Reference('sulu_core.doctrine_list_builder_factory'),
                new Reference('sulu_core.doctrine_rest_helper'),
            ])
            ->tag('sulu.context', ['context' => 'admin']);

        // PropertyResolver services
        $services->set('sulu_snippet.snippet_reference_store')
            ->class(ReferenceStore::class)
            ->tag('sulu_website.reference_store', ['alias' => SnippetInterface::RESOURCE_KEY]);

        $services->set('sulu_snippet.single_snippet_selection_property_resolver')
            ->class(SingleSnippetSelectionPropertyResolver::class)
            ->tag('sulu_content.property_resolver');

        $services->set('sulu_snippet.snippet_selection_property_resolver')
            ->class(SnippetSelectionPropertyResolver::class)
            ->tag('sulu_content.property_resolver');

        // ResourceLoader services
        $services->set('sulu_snippet.snippet_resource_loader')
            ->class(SnippetResourceLoader::class)
            ->args([
                new Reference('sulu_snippet.snippet_repository'),
            ])
            ->tag('sulu_content.resource_loader', ['type' => SnippetResourceLoader::RESOURCE_LOADER_KEY]);

        // Content services
        $services->set('sulu_snippet.snippet_reference_store')
            ->class(ReferenceStore::class)
            ->tag('sulu_website.reference_store', ['alias' => SnippetInterface::RESOURCE_KEY]);

        // TODO remove this
        $services->set('sulu_snippet.content_types.single_snippet_selection')
            ->class(SingleSnippetSelectionContentType::class)
            ->args([
                new Reference('sulu_snippet.snippet_repository'),
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_snippet.snippet_reference_store'),
            ])
            ->tag('sulu.content.type', ['alias' => 'single_snippet_selection']);

        // TODO remove this
        $services->set('sulu_snippet.content_types.snippet_selection')
            ->class(SnippetSelectionContentType::class)
            ->args([
                new Reference('sulu_snippet.snippet_repository'),
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_snippet.snippet_reference_store'),
            ])
            ->tag('sulu.content.type', ['alias' => 'snippet_selection']);

        // Smart Content services
        $services->set('sulu_snippet.snippet_data_provider')
            ->class(SnippetDataProvider::class) // TODO this should not be handled via Content Bundle instead own service which uses the SnippetRepository
            ->args([
                new Reference('sulu_snippet.snippet_repository'),
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_snippet.snippet_reference_store'),
                '%sulu_document_manager.show_drafts%',
            ])
            ->tag('sulu.smart_content.data_provider', ['alias' => SnippetInterface::RESOURCE_KEY]);

        // Search integration
        $services->set('sulu_snippet.snippet_search_metadata_provider')
            ->class(ContentSearchMetadataProvider::class) // TODO this should not be handled via Content Bundle instead own service which uses the SnippetRepository
            ->args([
                new Reference('sulu_content.content_metadata_inspector'),
                new Reference('massive_search.factory_default'),
                new Reference('sulu_page.structure.factory'),
                SnippetInterface::class,
            ])
            ->tag('massive_search.metadata.provider');
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('sulu_admin')) {
            $builder->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            \dirname(__DIR__, 4) . '/config/lists',
                        ],
                    ],
                    'forms' => [
                        'directories' => [
                            // \dirname(__DIR__, 4) . '/config/forms',
                        ],
                    ],
                    'resources' => [
                        'snippets' => [
                            'routes' => [
                                'list' => 'sulu_snippet.get_snippets',
                                'detail' => 'sulu_snippet.get_snippet',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'snippet_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'snippets',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'snippets',
                                        'display_properties' => ['title', 'routePath'],
                                        'icon' => 'su-newspaper',
                                        'label' => 'sulu_snippet.selection_label',
                                        'overlay_title' => 'sulu_snippet.selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                        'single_selection' => [
                            'single_snippet_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'snippets',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'snippets',
                                        'display_properties' => ['title'],
                                        'empty_text' => 'sulu_snippet.no_snippet_selected',
                                        'icon' => 'su-newspaper',
                                        'overlay_title' => 'sulu_snippet.single_selection_overlay_title',
                                    ],
                                ],
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
                            'SuluSnippet' => [
                                'type' => 'xml',
                                'prefix' => 'Sulu\Snippet\Domain\Model',
                                'dir' => \dirname(__DIR__, 4) . '/config/doctrine/Snippet',
                                'alias' => 'SuluSnippet',
                                'is_bundle' => false,
                                'mapping' => true,
                            ],
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('sulu_core')) {
            $builder->prependExtensionConfig(
                'sulu_core',
                [
                    'content' => [
                        'structure' => [
                            'paths' => [
                                SnippetInterface::TEMPLATE_TYPE => [
                                    'path' => '%kernel.project_dir%/config/templates/snippets',
                                    'type' => 'snippet',
                                ],
                            ],
                            'default_type' => [
                                SnippetInterface::TEMPLATE_TYPE => 'default',
                            ],
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('sulu_route')) {
            $builder->prependExtensionConfig(
                'sulu_route',
                [
                    'mappings' => [
                        SnippetInterface::class => [
                            'generator' => 'schema',
                            'options' => [
                                'route_schema' => '/{object["title"]}',
                            ],
                            'resource_key' => SnippetInterface::RESOURCE_KEY,
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('sulu_search')) {
            $suluSearchConfigs = $builder->getExtensionConfig('sulu_search');

            foreach ($suluSearchConfigs as $suluSearchConfig) {
                if (isset($suluSearchConfig['website']['indexes'])) { // @phpstan-ignore-line
                    $builder->prependExtensionConfig(
                        'sulu_search',
                        [
                            'website' => [
                                'indexes' => [
                                    SnippetInterface::RESOURCE_KEY => SnippetInterface::RESOURCE_KEY . '_published',
                                ],
                            ],
                        ],
                    );
                }
            }
        }
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function getPath(): string
    {
        return \dirname(__DIR__, 4); // target the root of the library where config, src, ... is located
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        $this->buildPersistence([
            SnippetInterface::class => 'sulu.model.snippet.class',
            SnippetDimensionContentInterface::class => 'sulu.model.snippet_content.class',
        ], $container);
    }
}
