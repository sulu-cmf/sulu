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

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ArticleLinkProvider implements LinkProviderInterface
{
    public function __construct(
        private readonly ContentManagerInterface $contentManager,
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ReferenceStoreInterface $articleReferenceStore,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getConfiguration(): LinkConfiguration
    {
        return LinkConfigurationBuilder::create()
            ->setTitle($this->translator->trans('sulu_article.articles', [], 'admin'))
            ->setResourceKey(ArticleInterface::RESOURCE_KEY)
            ->setListAdapter('table')
            ->setDisplayProperties(['id'])
            ->setOverlayTitle($this->translator->trans('sulu_article.selection_overlay_title', [], 'admin'))
            ->setEmptyText($this->translator->trans('sulu_article.no_article_selected', [], 'admin'))
            ->setIcon('su-document')
            ->getLinkConfiguration();
    }

    public function preload(array $hrefs, $locale, $published = true)
    {
        $dimensionAttributes = [
            'locale' => $locale,
            'stage' => $published ? DimensionContentInterface::STAGE_LIVE : DimensionContentInterface::STAGE_DRAFT,
        ];

        $articles = $this->articleRepository->findBy(
            filters: [...$dimensionAttributes, 'uuids' => $hrefs],
            selects: [ArticleRepositoryInterface::GROUP_SELECT_ARTICLE_WEBSITE => true]
        );

        $result = [];
        foreach ($articles as $article) {
            $dimensionContent = $this->contentManager->resolve($article, $dimensionAttributes);
            $this->articleReferenceStore->add($article->getId());

            /** @var string|null $url */
            $url = $dimensionContent->getTemplateData()['url'] ?? null;
            if (null === $url) {
                // TODO what to do when there is no url?
                continue;
            }

            $result[] = new LinkItem(
                $article->getUuid(),
                (string) $dimensionContent->getTitle(),
                $url,
                $published
            );
        }

        return $result;
    }
}
