<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait CreateArticleTrait
{
    /**
     * @param array{
     *     uuid?: ?string,
     *     locale?: ?string,
     *     stage?: ?string,
     * } $data
     */
    public function createArticle(array $data = []): ArticleInterface
    {
        $articleRepository = static::getContainer()->get('sulu_article.article_repository');
        $article = $articleRepository->createNew($data['uuid'] ?? null);

        $articleRepository->add($article);

        return $article;
    }

    /**
     * @param array{
     *     locale?: ?string,
     *     stage?: ?string,
     *     templateKey?: ?string,
     *     templateData?: mixed[],
     *     excerptCategories?: CategoryInterface[],
     *     excerptTags?: TagInterface[],
     * } $data
     */
    public function createArticleContent(ArticleInterface $article, array $data = []): void
    {
        $locale = $data['locale'] ?? 'en';
        $stage = $data['stage'] ?? DimensionContentInterface::STAGE_DRAFT;

        $unlocalizedDimensionContent = $article->createDimensionContent();
        $unlocalizedDimensionContent->setStage($stage);
        $article->addDimensionContent($unlocalizedDimensionContent);

        $localizedDimensionContent = $article->createDimensionContent();
        $localizedDimensionContent->setLocale($locale);
        $localizedDimensionContent->setStage($stage);

        $templateKey = $data['templateKey'] ?? null;
        if ($templateKey) {
            $localizedDimensionContent->setTemplateKey($templateKey);
        }
        $localizedDimensionContent->setTemplateData($data['templateData'] ?? ['title' => '']);
        $localizedDimensionContent->setExcerptCategories($data['excerptCategories'] ?? []);
        $localizedDimensionContent->setExcerptTags($data['excerptTags'] ?? []);

        $article->addDimensionContent($localizedDimensionContent);
    }

    abstract protected static function getEntityManager(): EntityManagerInterface;

    abstract protected static function getContainer(): ContainerInterface;
}
