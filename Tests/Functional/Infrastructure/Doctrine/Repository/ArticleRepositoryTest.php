<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Infrastructure\Doctrine\Repository;

use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ArticleBundle\Domain\Exception\ArticleNotFoundException;
use Sulu\Bundle\ArticleBundle\Domain\Model\Article;
use Sulu\Bundle\ArticleBundle\Infrastructure\Doctrine\Repository\ArticleRepository;
use Sulu\Bundle\ArticleBundle\Tests\Functional\Traits\CreateArticleTrait;
use Sulu\Bundle\ArticleBundle\Tests\Functional\Traits\CreateCategoryTrait;
use Sulu\Bundle\ArticleBundle\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ArticleRepositoryTest extends SuluTestCase
{
    use CreateArticleTrait;
    use CreateCategoryTrait;
    use CreateTagTrait;

    /**
     * @var ArticleRepository
     */
    private $articleRepository;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test_experimental_storage']);
        $this->articleRepository = static::getContainer()->get('sulu_article.article_repository');
    }

    public function testFindOneByNotExist(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $this->assertNull($this->articleRepository->findOneBy(['uuid' => $uuid]));
    }

    public function testGetOneByNotExist(): void
    {
        $this->expectException(ArticleNotFoundException::class);

        $uuid = Uuid::uuid4()->toString();
        $this->articleRepository->getOneBy(['uuid' => $uuid]);
    }

    public function testFindByNotExist(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $articles = \iterator_to_array($this->articleRepository->findBy(['uuids' => [$uuid]]));
        $this->assertCount(0, $articles);
    }

    public function testAdd(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $article = new Article($uuid);

        $this->articleRepository->add($article);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $article = $this->articleRepository->getOneBy(['uuid' => $uuid]);
        $this->assertSame($uuid, $article->getUuid());
    }

    public function testRemove(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $article = new Article($uuid);

        $this->articleRepository->add($article);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $article = $this->articleRepository->getOneBy(['uuid' => $uuid]);
        $this->articleRepository->remove($article);
        static::getEntityManager()->flush();

        $this->assertNull($this->articleRepository->findOneBy(['uuid' => $uuid]));
    }

    public function testCountBy(): void
    {
        static::purgeDatabase();

        $this->articleRepository->add(new Article());
        $this->articleRepository->add(new Article());
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $this->assertSame(2, $this->articleRepository->countBy());
    }

    public function testFindByUuids(): void
    {
        static::purgeDatabase();

        $uuid = Uuid::uuid4()->toString();
        $uuid2 = Uuid::uuid4()->toString();
        $uuid3 = Uuid::uuid4()->toString();
        $article = new Article($uuid);
        $article2 = new Article($uuid2);
        $article3 = new Article($uuid3);

        $this->articleRepository->add($article);
        $this->articleRepository->add($article2);
        $this->articleRepository->add($article3);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $articles = \iterator_to_array($this->articleRepository->findBy(['uuids' => [$uuid, $uuid3]]));

        $this->assertCount(2, $articles);
    }

    public function testFindByLimitAndPage(): void
    {
        static::purgeDatabase();

        $this->articleRepository->add(new Article());
        $this->articleRepository->add(new Article());
        $this->articleRepository->add(new Article());
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $articles = \iterator_to_array($this->articleRepository->findBy(['limit' => 2, 'page' => 2]));
        $this->assertCount(1, $articles);
    }

    public function testFindByLocaleAndStage(): void
    {
        static::purgeDatabase();

        $article = static::createArticle();
        $article2 = static::createArticle();
        $article3 = static::createArticle();
        static::createArticleContent($article, ['title' => 'Article A']);
        static::createArticleContent($article, ['title' => 'Article A', 'stage' => 'live']);
        static::createArticleContent($article2, ['title' => 'Article B']);
        static::createArticleContent($article3, ['title' => 'Article C']);
        static::createArticleContent($article3, ['title' => 'Article C', 'stage' => 'live']);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $articles = \iterator_to_array($this->articleRepository->findBy(['locale' => 'en', 'stage' => 'live']));
        $this->assertCount(2, $articles);
    }

    public function testCategoryFilters(): void
    {
        static::purgeDatabase();

        $categoryA = static::createCategory(['key' => 'a']);
        $categoryB = static::createCategory(['key' => 'b']);

        $article = static::createArticle();
        $article2 = static::createArticle();
        $article3 = static::createArticle();
        static::createArticleContent($article, ['title' => 'Article A', 'excerptCategories' => [$categoryA]]);
        static::createArticleContent($article2, ['title' => 'Article B']);
        static::createArticleContent($article3, ['title' => 'Article C', 'excerptCategories' => [$categoryA, $categoryB]]);
        static::getEntityManager()->flush();
        $categoryAId = $categoryA->getId();
        $categoryBId = $categoryB->getId();
        static::getEntityManager()->clear();

        $this->assertCount(2, \iterator_to_array($this->articleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryKeys' => ['a', 'b'],
        ])));

        $this->assertSame(2, $this->articleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryKeys' => ['a', 'b'],
        ]));

        $this->assertCount(1, \iterator_to_array($this->articleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryKeys' => ['a', 'b'],
            'categoryOperator' => 'AND',
        ])));

        $this->assertSame(1, $this->articleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryKeys' => ['a', 'b'],
            'categoryOperator' => 'AND',
        ]));

        $this->assertCount(2, \iterator_to_array($this->articleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryIds' => [$categoryAId, $categoryBId],
        ])));

        $this->assertSame(2, $this->articleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryIds' => [$categoryAId, $categoryBId],
        ]));

        $this->assertCount(1, \iterator_to_array($this->articleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryIds' => [$categoryAId, $categoryBId],
            'categoryOperator' => 'AND',
        ])));

        $this->assertSame(1, $this->articleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'categoryIds' => [$categoryAId, $categoryBId],
            'categoryOperator' => 'AND',
        ]));
    }

    public function testTagFilters(): void
    {
        static::purgeDatabase();

        $tagA = static::createTag(['name' => 'a']);
        $tagB = static::createTag(['name' => 'b']);

        $article = static::createArticle();
        $article2 = static::createArticle();
        $article3 = static::createArticle();
        static::createArticleContent($article, ['title' => 'Article A', 'excerptTags' => [$tagA]]);
        static::createArticleContent($article2, ['title' => 'Article B']);
        static::createArticleContent($article3, ['title' => 'Article C', 'excerptTags' => [$tagA, $tagB]]);
        static::getEntityManager()->flush();
        $tagAId = $tagA->getId();
        $tagBId = $tagB->getId();
        static::getEntityManager()->clear();

        $this->assertCount(2, \iterator_to_array($this->articleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagNames' => ['a', 'b'],
        ])));

        $this->assertSame(2, $this->articleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagNames' => ['a', 'b'],
        ]));

        $this->assertCount(1, \iterator_to_array($this->articleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagNames' => ['a', 'b'],
            'tagOperator' => 'AND',
        ])));

        $this->assertSame(1, $this->articleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagNames' => ['a', 'b'],
            'tagOperator' => 'AND',
        ]));

        $this->assertCount(2, \iterator_to_array($this->articleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagIds' => [$tagAId, $tagBId],
        ])));

        $this->assertSame(2, $this->articleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagIds' => [$tagAId, $tagBId],
        ]));

        $this->assertCount(1, \iterator_to_array($this->articleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagIds' => [$tagAId, $tagBId],
            'tagOperator' => 'AND',
        ])));

        $this->assertSame(1, $this->articleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'tagIds' => [$tagAId, $tagBId],
            'tagOperator' => 'AND',
        ]));
    }

    public function testFilterTemplateKeys(): void
    {
        static::purgeDatabase();

        $article = static::createArticle();
        $article2 = static::createArticle();
        $article3 = static::createArticle();
        static::createArticleContent($article, ['title' => 'Article A', 'templateKey' => 'a']);
        static::createArticleContent($article2, ['title' => 'Article B', 'templateKey' => 'b']);
        static::createArticleContent($article3, ['title' => 'Article C', 'templateKey' => 'c']);
        static::getEntityManager()->flush();
        static::getEntityManager()->clear();

        $this->assertCount(2, \iterator_to_array($this->articleRepository->findBy([
            'locale' => 'en',
            'stage' => 'draft',
            'templateKeys' => ['a', 'c'],
        ])));

        $this->assertSame(2, $this->articleRepository->countBy([
            'locale' => 'en',
            'stage' => 'draft',
            'templateKeys' => ['a', 'c'],
        ]));
    }
}
