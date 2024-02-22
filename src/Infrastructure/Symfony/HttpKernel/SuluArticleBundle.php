<?php

declare(strict_types=1);

namespace Sulu\Article\Infrastructure\Symfony\HttpKernel;

use Sulu\Article\Application\Mapper\ArticleContentMapper;
use Sulu\Article\Application\Mapper\ArticleMapperInterface;
use Sulu\Article\Application\MessageHandler\ApplyWorkflowTransitionArticleMessageHandler;
use Sulu\Article\Application\MessageHandler\CopyLocaleArticleMessageHandler;
use Sulu\Article\Application\MessageHandler\CreateArticleMessageHandler;
use Sulu\Article\Application\MessageHandler\ModifyArticleMessageHandler;
use Sulu\Article\Application\MessageHandler\RemoveArticleMessageHandler;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Infrastructure\Doctrine\Repository\ArticleRepository;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Article\Infrastructure\Sulu\Content\ArticleLinkProvider;
use Sulu\Article\Infrastructure\Sulu\Content\ArticleSitemapProvider;
use Sulu\Article\Infrastructure\Sulu\Content\ArticleTeaserProvider;
use Sulu\Article\UserInterface\Controller\Admin\ArticleController;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Preview\ContentObjectProvider;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Search\ContentSearchMetadataProvider;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\SmartContent\Provider\ContentDataProvider;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\SmartContent\Repository\ContentDataProviderRepository;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
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
final class SuluArticleBundle extends AbstractBundle
{
    use PersistenceExtensionTrait;
    use PersistenceBundleTrait;

    public const ALIAS = 'sulu_next_article';
    public const NAME = 'SuluNextArticleBundle';

    protected string $extensionAlias = self::ALIAS;

    protected $name = self::NAME;

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('article')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Article::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('article_content')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(ArticleDimensionContent::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $this->configurePersistence($config['objects'], $builder);

        $services = $container->services();

        // Define autoconfigure interfaces for mappers
        $builder->registerForAutoconfiguration(ArticleMapperInterface::class)
            ->addTag('sulu_article.article_mapper');

        // Message Bus
        $services->alias('sulu_article.message_bus', 'sulu_message_bus');

        // Message Handler services
        $services->set('sulu_article.create_article_handler')
            ->class(CreateArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                tagged_iterator('sulu_article.article_mapper'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.modify_article_handler')
            ->class(ModifyArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                tagged_iterator('sulu_article.article_mapper'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.remove_article_handler')
            ->class(RemoveArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.apply_workflow_transition_article_handler')
            ->class(ApplyWorkflowTransitionArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_workflow'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.copy_locale_article_handler')
            ->class(CopyLocaleArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_copier'),
            ])
            ->tag('messenger.message_handler');

        // Mapper service
        $services->set('sulu_article.article_content_mapper')
            ->class(ArticleContentMapper::class)
            ->args([
                new Reference('sulu_content.content_persister'),
            ])
            ->tag('sulu_article.article_mapper');

        // Sulu Integration service
        $services->set('sulu_article.article_admin')
            ->class(ArticleAdmin::class)
            ->args([
                new Reference('sulu_admin.view_builder_factory'),
                new Reference('sulu_content.content_view_builder_factory'),
                new Reference('sulu_security.security_checker'),
                new Reference('sulu.core.localization_manager'),
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu.admin');

        // Repositories services
        $services->set('sulu_article.article_repository')
            ->class(ArticleRepository::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.dimension_content_query_enhancer'),
            ]);

        $services->alias(ArticleRepositoryInterface::class, 'sulu_article.article_repository');

        // Controllers services
        $services->set('sulu_article.admin_article_controller')
            ->class(ArticleController::class)
            ->public()
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_article.message_bus'),
                new Reference('serializer'),
                // additional services to be removed when no longer needed
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_core.list_builder.field_descriptor_factory'),
                new Reference('sulu_core.doctrine_list_builder_factory'),
                new Reference('sulu_core.doctrine_rest_helper'),
            ])
            ->tag('sulu.context', ['context' => 'admin']);

        // Preview service
        $services->set('sulu_article.article_preview_provider')
            ->class(ContentObjectProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.content_resolver'),
                new Reference('sulu_content.content_data_mapper'),
                '%sulu.model.article.class%',
                ArticleAdmin::SECURITY_CONTEXT,
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu_preview.object_provider', ['provider-key' => 'articles']);

        // Content services
        $services->set('sulu_article.article_sitemap_provider')
            ->class(ArticleSitemapProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_core.webspace.webspace_manager'),
                '%kernel.environment%',
                ArticleInterface::class,
                '%sulu.model.route.class%',
                ArticleInterface::RESOURCE_KEY,
            ])
            ->tag('sulu.sitemap.provider');

        $services->set('sulu_article.article_teaser_provider')
            ->class(ArticleTeaserProvider::class)
            ->args([
                new Reference('sulu_content.content_manager'), // TODO teaser provider should not build on manager
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.content_metadata_inspector'),
                new Reference('sulu_page.structure.factory'),
                new Reference('translator'),
                '%sulu_document_manager.show_drafts%',
            ])
            ->tag('sulu.teaser.provider', ['alias' => ArticleInterface::RESOURCE_KEY]);

        $services->set('sulu_article.article_link_provider')
            ->class(ArticleLinkProvider::class)
            ->args([
                new Reference('sulu_content.content_manager'), // TODO link provider should not build on manager
                new Reference('sulu_page.structure.factory'),
                new Reference('doctrine.orm.entity_manager'),
            ])
            ->tag('sulu.link.provider', ['alias' => ArticleInterface::RESOURCE_KEY]);

        // Smart Content services
        $services->set('sulu_article.article_data_provider_repository')
            ->class(ContentDataProviderRepository::class) // TODO this should not be handled via Content Bundle instead own service which uses the ArticleRepository
            ->args([
                new Reference('sulu_content.content_manager'),
                new Reference('doctrine.orm.entity_manager'),
                '%sulu_document_manager.show_drafts%',
                ArticleInterface::class,
            ]);

        $services->set('sulu_article.article_reference_store')
            ->class(ReferenceStore::class)
            ->tag('sulu_website.reference_store', ['alias' => ArticleInterface::RESOURCE_KEY]);

        $services->set('sulu_article.article_data_provider')
            ->class(ContentDataProvider::class) // TODO this should not be handled via Content Bundle instead own service which uses the ArticleRepository
            ->args([
                new Reference('sulu_article.article_data_provider_repository'),
                new Reference('sulu_core.array_serializer'),
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_article.article_reference_store'),
            ])
            ->tag('sulu.smart_content.data_provider', ['alias' => ArticleInterface::RESOURCE_KEY]);

        // Search integration
        $services->set('sulu_article.article_search_metadata_provider')
            ->class(ContentSearchMetadataProvider::class) // TODO this should not be handled via Content Bundle instead own service which uses the ArticleRepository
            ->args([
                new Reference('sulu_content.content_metadata_inspector'),
                new Reference('massive_search.factory_default'),
                new Reference('sulu_page.structure.factory'),
                ArticleInterface::class,
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
                        'articles' => [
                            'routes' => [
                                'list' => 'sulu_article.get_articles',
                                'detail' => 'sulu_article.get_article',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'article_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'articles',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'articles',
                                        'display_properties' => ['title', 'routePath'],
                                        'icon' => 'su-newspaper',
                                        'label' => 'sulu_article.selection_label',
                                        'overlay_title' => 'sulu_article.selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                        'single_selection' => [
                            'single_article_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'articles',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'articles',
                                        'display_properties' => ['title'],
                                        'empty_text' => 'sulu_article.no_article_selected',
                                        'icon' => 'su-newspaper',
                                        'overlay_title' => 'sulu_article.single_selection_overlay_title',
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
                            'SuluArticle' => [
                                'type' => 'xml',
                                'prefix' => 'Sulu\Article\Domain\Model',
                                'dir' => \dirname(__DIR__, 4) . '/config/doctrine/Article',
                                'alias' => 'SuluArticle',
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
                                ArticleInterface::TEMPLATE_TYPE => [
                                    'path' => '%kernel.project_dir%/config/templates/articles',
                                    'type' => 'article',
                                ],
                            ],
                            'default_type' => [
                                ArticleInterface::TEMPLATE_TYPE => 'default',
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
                        ArticleInterface::class => [
                            'generator' => 'schema',
                            'options' => [
                                'route_schema' => '/{object["title"]}',
                            ],
                            'resource_key' => ArticleInterface::RESOURCE_KEY,
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
                                    ArticleInterface::RESOURCE_KEY => ArticleInterface::RESOURCE_KEY . '_published',
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
            ArticleInterface::class => 'sulu.model.article.class',
            ArticleDimensionContentInterface::class => 'sulu.model.article_content.class',
        ], $container);
    }
}
