<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Trash;

use Sulu\Bundle\ArticleBundle\Controller\ArticleController;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Trash\ArticleTrashItemHandler;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\SuluTrashBundle;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class ArticleTrashItemHandlerTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var ArticleTrashItemHandler
     */
    private $articleTrashItemHandler;

    public function setUp(): void
    {
        static::purgeDatabase();
        static::initPhpcr();

        if (!\class_exists(SuluTrashBundle::class)) {
            $this->markTestSkipped('SuluTrashBundle does not exist in Sulu <2.4');
        }

        $this->documentManager = static::getContainer()->get('sulu_document_manager.document_manager');
        $this->articleTrashItemHandler = static::getContainer()->get('sulu_article.article_trash_item_handler');
    }

    public function testStoreAndRestore(): void
    {
        /** @var ArticleDocument $article1De */
        $article1De = $this->documentManager->create(ArticleController::DOCUMENT_TYPE);
        $article1De->setTitle('test-title-de');
        $article1De->setLocale('de');
        $article1De->setCreator(101);
        $article1De->setCreated(new \DateTime('1999-04-20'));
        $article1De->setAuthor(202);
        $article1De->setAuthored(new \DateTime('2000-04-20'));
        $article1De->setStructureType('default_with_route');
        $article1De->getStructure()->bind([
            'routePath' => 'german-route-path',
            'article' => 'german article content',
        ]);
        $article1De->setExtensionsData([
            'excerpt' => [
                'title' => 'excerpt-title-de',
            ],
            'seo' => [
                'title' => 'seo-title-de',
            ],
        ]);
        $article1De->setMainWebspace('sulu_io');
        $article1De->setAdditionalWebspaces(['test', 'test-2']);
        $this->documentManager->persist($article1De, 'de');

        /** @var ArticleDocument $article1En */
        $article1En = $this->documentManager->find($article1De->getUuid(), 'en', ['load_ghost_content' => false]);
        $article1En->setTitle('test-title-en');
        $article1En->setLocale('en');
        $article1En->setCreator(303);
        $article1En->setCreated(new \DateTime('1999-04-22'));
        $article1En->setAuthor(404);
        $article1En->setAuthored(new \DateTime('2000-04-22'));
        $article1En->setStructureType('default_with_route');
        $article1En->getStructure()->bind([
            'routePath' => 'english-route-path',
            'article' => 'english article content',
        ]);
        $article1En->setExtensionsData([
            'excerpt' => [
                'title' => 'excerpt-title-en',
            ],
            'seo' => [
                'title' => 'seo-title-en',
            ],
        ]);
        $article1En->setMainWebspace('sulu_io');
        $article1En->setAdditionalWebspaces(['test', 'test-2']);
        $this->documentManager->persist($article1En, 'en');

        /** @var ArticleDocument $article2De */
        $article2De = $this->documentManager->create(ArticleController::DOCUMENT_TYPE);
        $article2De->setTitle('second-article');
        $article2De->setLocale('de');
        $article2De->setStructureType('default');
        $this->documentManager->persist($article2De, 'de');

        $this->documentManager->flush();
        $originalArticleUuid = $article1De->getUuid();

        $trashItem = $this->articleTrashItemHandler->store($article1De);
        $this->documentManager->remove($article1De);
        $this->documentManager->flush();
        $this->documentManager->clear();

        static::assertSame($originalArticleUuid, $trashItem->getResourceId());
        static::assertSame('test-title-de', $trashItem->getResourceTitle());
        static::assertSame('test-title-en', $trashItem->getResourceTitle('en'));
        static::assertSame('test-title-de', $trashItem->getResourceTitle('de'));

        /** @var ArticleDocument $restoredArticle */
        $restoredArticle = $this->articleTrashItemHandler->restore($trashItem);
        static::assertSame($originalArticleUuid, $restoredArticle->getUuid());

        /** @var ArticleDocument $restoredArticleDe */
        $restoredArticleDe = $this->documentManager->find($originalArticleUuid, 'de');
        static::assertSame($originalArticleUuid, $restoredArticleDe->getUuid());
        static::assertSame('test-title-de', $restoredArticleDe->getTitle());
        static::assertSame('german-route-path', $restoredArticleDe->getRoutePath());
        static::assertSame('de', $restoredArticleDe->getLocale());
        static::assertSame(101, $restoredArticleDe->getCreator());
        static::assertSame('1999-04-20T00:00:00+00:00', $restoredArticleDe->getCreated()->format('c'));
        static::assertSame(202, $restoredArticleDe->getAuthor());
        static::assertSame('2000-04-20T00:00:00+00:00', $restoredArticleDe->getAuthored()->format('c'));
        static::assertSame('default_with_route', $restoredArticleDe->getStructureType());
        static::assertSame('german article content', $restoredArticleDe->getStructure()->toArray()['article']);
        static::assertSame('excerpt-title-de', $restoredArticleDe->getExtensionsData()['excerpt']['title']);
        static::assertSame('seo-title-de', $restoredArticleDe->getExtensionsData()['seo']['title']);
        static::assertSame('sulu_io', $restoredArticleDe->getMainWebspace());
        static::assertSame(['test', 'test-2'], $restoredArticleDe->getAdditionalWebspaces());

        /** @var ArticleDocument $restoredArticleEn */
        $restoredArticleEn = $this->documentManager->find($originalArticleUuid, 'en');
        static::assertSame($originalArticleUuid, $restoredArticleEn->getUuid());
        static::assertSame('test-title-en', $restoredArticleEn->getTitle());
        static::assertSame('english-route-path', $restoredArticleEn->getRoutePath());
        static::assertSame('en', $restoredArticleEn->getLocale());
        static::assertSame(303, $restoredArticleEn->getCreator());
        static::assertSame('1999-04-20T00:00:00+00:00', $restoredArticleEn->getCreated()->format('c'));
        static::assertSame(404, $restoredArticleEn->getAuthor());
        static::assertSame('2000-04-22T00:00:00+00:00', $restoredArticleEn->getAuthored()->format('c'));
        static::assertSame('default_with_route', $restoredArticleEn->getStructureType());
        static::assertSame('english article content', $restoredArticleEn->getStructure()->toArray()['article']);
        static::assertSame('excerpt-title-en', $restoredArticleEn->getExtensionsData()['excerpt']['title']);
        static::assertSame('seo-title-en', $restoredArticleEn->getExtensionsData()['seo']['title']);
        static::assertSame('sulu_io', $restoredArticleEn->getMainWebspace());
        static::assertSame(['test', 'test-2'], $restoredArticleEn->getAdditionalWebspaces());
    }

    public function testStoreAndRestoreShadowArticle(): void
    {
        /** @var ArticleDocument $articleDe */
        $articleDe = $this->documentManager->create(ArticleController::DOCUMENT_TYPE);
        $articleDe->setTitle('target-locale-title');
        $articleDe->setLocale('de');
        $articleDe->setStructureType('default_with_route');
        $articleDe->getStructure()->bind([
            'routePath' => 'target-locale-route-path',
            'article' => 'target locale article content',
        ]);
        $this->documentManager->persist($articleDe, 'de');

        /** @var ArticleDocument $articleEn */
        $articleEn = $this->documentManager->find($articleDe->getUuid(), 'en', ['load_ghost_content' => false]);
        $articleEn->setTitle('source-locale-title');
        $articleEn->setLocale('en');
        $articleEn->setStructureType('default_with_route');
        $articleEn->getStructure()->bind([
            'routePath' => 'source-locale-route-path',
            'article' => 'source locale article content',
        ]);
        $articleEn->setShadowLocaleEnabled(true);
        $articleEn->setShadowLocale('de');
        $this->documentManager->persist($articleEn, 'en');

        $this->documentManager->flush();
        $originalArticleUuid = $articleDe->getUuid();

        $trashItem = $this->articleTrashItemHandler->store($articleDe);
        $this->documentManager->remove($articleDe);
        $this->documentManager->flush();
        $this->documentManager->clear();

        /** @var ArticleDocument $restoredArticle */
        $restoredArticle = $this->articleTrashItemHandler->restore($trashItem);
        static::assertSame($originalArticleUuid, $restoredArticle->getUuid());

        /** @var ArticleDocument $restoredArticleDe */
        $restoredArticleDe = $this->documentManager->find($restoredArticle->getUuid(), 'de');
        static::assertSame('target-locale-title', $restoredArticleDe->getTitle());
        static::assertSame('de', $restoredArticleDe->getLocale());
        static::assertSame('target-locale-route-path', $restoredArticleDe->getRoutePath());
        static::assertSame('target locale article content', $restoredArticleDe->getStructure()->toArray()['article']);
        static::assertSame('de', $restoredArticleDe->getOriginalLocale());
        static::assertNull($restoredArticleDe->getShadowLocale());

        /** @var ArticleDocument $restoredArticleEn */
        $restoredArticleEn = $this->documentManager->find($restoredArticle->getUuid(), 'en');
        static::assertSame('target-locale-title', $restoredArticleEn->getTitle());
        static::assertSame('de', $restoredArticleEn->getLocale());
        static::assertSame('target-locale-route-path', $restoredArticleEn->getRoutePath());
        static::assertSame('target locale article content', $restoredArticleEn->getStructure()->toArray()['article']);
        static::assertSame('en', $restoredArticleEn->getOriginalLocale());
        static::assertSame('de', $restoredArticleEn->getShadowLocale());
    }

    public function testStoreAndRestorePageTreeRoute(): void
    {
        /** @var PageDocument $page */
        $page = $this->documentManager->create('page');
        $page->setTitle('test parent page');
        $page->setStructureType('default');
        $page->setResourceSegment('/test-parent-page');

        $this->documentManager->persist($page, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($page, 'de');
        $this->documentManager->flush();

        /** @var ArticleDocument $article1De */
        $article1De = $this->documentManager->create(ArticleController::DOCUMENT_TYPE);
        $article1De->setParent($this->documentManager->find('/cmf/sulu_io/contents', 'de'));
        $article1De->setTitle('test-title-de');
        $article1De->setLocale('de');
        $article1De->setStructureType('default_with_page_tree_route');
        $article1De->setRoutePath($page->getStructure()->getProperty('url')->getValue() . '/test-title-de');
        $article1De->getStructure()->bind([
            'routePath' => [
                'page' => [
                    'uuid' => $page->getUuid(),
                    'path' => $page->getStructure()->getProperty('url')->getValue(),
                ],
                'path' => $page->getStructure()->getProperty('url')->getValue() . '/test-title-de',
                'suffix' => '/test-title-de',
            ],
        ]);
        $article1De->setMainWebspace('sulu_io');
        $this->documentManager->persist($article1De, 'de');

        $this->documentManager->flush();
        $originalArticleUuid = $article1De->getUuid();

        $trashItem = $this->articleTrashItemHandler->store($article1De);
        $this->documentManager->remove($article1De);
        $this->documentManager->flush();
        $this->documentManager->clear();

        static::assertSame($originalArticleUuid, $trashItem->getResourceId());
        static::assertSame('test-title-de', $trashItem->getResourceTitle());

        /** @var ArticleDocument $restoredArticle */
        $restoredArticle = $this->articleTrashItemHandler->restore($trashItem);
        static::assertSame($originalArticleUuid, $restoredArticle->getUuid());

        /** @var ArticleDocument $restoredArticleDe */
        $restoredArticleDe = $this->documentManager->find($originalArticleUuid, 'de');
        static::assertSame($originalArticleUuid, $restoredArticleDe->getUuid());
        static::assertSame('test-title-de', $restoredArticleDe->getTitle());
        static::assertSame('/test-parent-page/test-title-de', $restoredArticleDe->getRoutePath());
        static::assertSame('de', $restoredArticleDe->getLocale());
        static::assertSame('default_with_page_tree_route', $restoredArticleDe->getStructureType());
        static::assertSame('sulu_io', $restoredArticleDe->getMainWebspace());
    }

    public function testStoreAndRestorePageTreeRouteNoValidParent(): void
    {
        /** @var ArticleDocument $article1De */
        $article1De = $this->documentManager->create(ArticleController::DOCUMENT_TYPE);
        $article1De->setParent($this->documentManager->find('/cmf/sulu_io/contents', 'de'));
        $article1De->setTitle('test-title-de');
        $article1De->setLocale('de');
        $article1De->setStructureType('default_with_page_tree_route');
        $article1De->getStructure()->bind([
            'routePath' => [
                'page' => [
                    'uuid' => 'not-existing-uuid',
                    'path' => '/not-existing-path',
                ],
                'path' => '/not-existing-path/test-title-de',
                'suffix' => '/test-title-de',
            ],
        ]);
        $article1De->setMainWebspace('sulu_io');
        $this->documentManager->persist($article1De, 'de');

        $this->documentManager->flush();
        $originalArticleUuid = $article1De->getUuid();

        $trashItem = $this->articleTrashItemHandler->store($article1De);
        $this->documentManager->remove($article1De);
        $this->documentManager->flush();
        $this->documentManager->clear();

        static::assertSame($originalArticleUuid, $trashItem->getResourceId());
        static::assertSame('test-title-de', $trashItem->getResourceTitle());

        /** @var ArticleDocument $restoredArticle */
        $restoredArticle = $this->articleTrashItemHandler->restore($trashItem);
        static::assertSame($originalArticleUuid, $restoredArticle->getUuid());

        /** @var ArticleDocument $restoredArticleDe */
        $restoredArticleDe = $this->documentManager->find($originalArticleUuid, 'de');
        static::assertSame($originalArticleUuid, $restoredArticleDe->getUuid());
        static::assertSame('test-title-de', $restoredArticleDe->getTitle());
        static::assertSame('/not-existing-path/test-title-de', $restoredArticleDe->getRoutePath());
        static::assertSame('de', $restoredArticleDe->getLocale());
        static::assertSame('default_with_page_tree_route', $restoredArticleDe->getStructureType());
        static::assertSame('sulu_io', $restoredArticleDe->getMainWebspace());
    }
}
