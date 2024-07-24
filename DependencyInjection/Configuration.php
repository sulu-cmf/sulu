<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\DependencyInjection;

use Sulu\Bundle\ArticleBundle\Document\ArticlePageViewObject;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Domain\Model\Article;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleDimensionContent;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Initializes configuration tree for article-bundle.
 */
class Configuration implements ConfigurationInterface
{
    public const ARTICLE_STORAGE_PHPCR = 'phpcr';
    public const ARTICLE_STORAGE_EXPERIMENTAL = 'experimental';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_article');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->enumNode('storage')
                    ->values([self::ARTICLE_STORAGE_PHPCR, self::ARTICLE_STORAGE_EXPERIMENTAL])
                    ->defaultValue(self::ARTICLE_STORAGE_PHPCR)
                ->end()
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
                ->scalarNode('index_name')->end()
                ->arrayNode('hosts')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('default_main_webspace')
                    ->useAttributeAsKey('locale')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function($v) {
                            return ['default' => $v];
                        })
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('default_additional_webspaces')
                    ->beforeNormalization()
                        ->ifTrue(function($v) {
                            return \count(\array_filter(\array_keys($v), 'is_string')) <= 0;
                        })
                        ->then(function($v) {
                            return ['default' => $v];
                        })
                    ->end()
                    ->prototype('array')->useAttributeAsKey('locale')->prototype('scalar')->end()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('smart_content')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('default_limit')->defaultValue(100)->end()
                    ->end()
                ->end()
                ->arrayNode('documents')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('article')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('view')->defaultValue(ArticleViewDocument::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('article_page')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('view')->defaultValue(ArticlePageViewObject::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('types')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('translation_key')->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('display_tab_all')->defaultTrue()->info('Display tab \'all\' in list view')->end()
                ->scalarNode('default_author')->defaultTrue()->info('Set default author if none isset')->end()
                ->arrayNode('search_fields')
                    ->prototype('scalar')->end()->defaultValue([
                        'title',
                        'excerpt.title',
                        'excerpt.description',
                        'excerpt.seo.title',
                        'excerpt.seo.description',
                        'excerpt.seo.keywords',
                        'teaser_description',
                    ])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
