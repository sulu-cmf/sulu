<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Document\Index;

use ONGR\ElasticsearchBundle\Service\Manager;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\ArticleIndexer;
use Sulu\Bundle\MediaBundle\Content\Types\ImageMapContentType;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ArticleIndexerTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;

    /**
     * @var string
     */
    private $locale = 'en';

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var Manager
     */
    private $liveManager;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var ArticleIndexer
     */
    private $liveIndexer;

    /**
     * @var ArticleIndexer
     */
    private $indexer;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();

        $this->initPhpcr();
        $this->purgeDatabase();

        $this->liveManager = $this->getContainer()->get('es.manager.live');
        $this->manager = $this->getContainer()->get('es.manager.default');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->liveIndexer = $this->getContainer()->get('sulu_article.elastic_search.article_live_indexer');
        $this->liveIndexer->clear();
        $this->indexer = $this->getContainer()->get('sulu_article.elastic_search.article_indexer');
        $this->indexer->clear();
    }

    public function testRemove()
    {
        $article = $this->createArticle(
            [
                'article' => 'Test content',
            ],
            'Test Article',
            'default_with_route'
        );

        $secondLocale = 'de';

        // now add second locale
        $this->updateArticle(
            $article['id'],
            $secondLocale,
            [
                'id' => $article['id'],
                'article' => 'Test Inhalt',
            ],
            'Test Artikel Deutsch',
            'default_with_route'
        );

        /** @var ArticleDocument $articleDocument */
        $articleDocument = $this->documentManager->find($article['id']);
        $this->liveIndexer->remove($articleDocument);
        $this->liveIndexer->flush();

        self::assertNull($this->findLiveViewDocument($articleDocument->getUuid(), 'de'));
        self::assertNull($this->findLiveViewDocument($articleDocument->getUuid(), 'en'));
    }

    public function testReplaceWithGhostData()
    {
        $article = $this->createArticle(
            [
                'article' => 'Test content',
            ],
            'Test Article',
            'default_with_route'
        );

        $otherArticle = $this->createArticle(
            [
                'article' => 'Other content',
            ],
            'Other Article',
            'default_with_route'
        );

        $articleDocumentUuid = $article['id'];

        $documentDE = $this->findViewDocument($articleDocumentUuid, 'de');
        $documentEN = $this->findViewDocument($articleDocumentUuid, 'en');
        $documentFR = $this->findViewDocument($articleDocumentUuid, 'fr');

        $this->assertSame('Test Article', $documentEN->getTitle());
        $this->assertSame('localized', $documentEN->getLocalizationState()->state);
        $this->assertNull($documentEN->getLocalizationState()->locale);

        $this->assertSame('Test Article', $documentDE->getTitle());
        $this->assertSame('ghost', $documentDE->getLocalizationState()->state);
        $this->assertSame('en', $documentDE->getLocalizationState()->locale);

        $this->assertSame('Test Article', $documentFR->getTitle());
        $this->assertSame('ghost', $documentFR->getLocalizationState()->state);
        $this->assertSame('en', $documentFR->getLocalizationState()->locale);

        $secondLocale = 'de';

        // now add second locale
        $this->updateArticle(
            $article['id'],
            $secondLocale,
            [
                'id' => $article['id'],
                'article' => 'Test Inhalt',
            ],
            'Test Artikel Deutsch',
            'default_with_route'
        );
        $this->updateArticle(
            $otherArticle['id'],
            $secondLocale,
            [
                'id' => $otherArticle['id'],
                'article' => 'Anderer Inhalt',
            ],
            'Anderer Artikel Deutsch',
            'default_with_route'
        );

        /** @var ArticleDocument $articleDocument */
        $articleDocument = $this->documentManager->find($article['id']);
        /** @var ArticleDocument $otherDocument */
        $otherDocument = $this->documentManager->find($otherArticle['id']);
        static::setPrivateProperty($otherDocument, 'originalLocale', 'de'); // to reproduce https://github.com/sulu/SuluArticleBundle/pull/677 correctly

        $this->indexer->replaceWithGhostData($articleDocument, 'de');
        $this->indexer->replaceWithGhostData($otherDocument, 'en');

        $this->indexer->flush();

        $documentEN = $this->findViewDocument($articleDocument->getUuid(), 'en');
        $documentDE = $this->findViewDocument($articleDocument->getUuid(), 'de');
        $documentFR = $this->findViewDocument($articleDocument->getUuid(), 'fr');

        $this->assertSame('localized', $documentEN->getLocalizationState()->state);
        $this->assertNull($documentEN->getLocalizationState()->locale);

        $this->assertSame('ghost', $documentDE->getLocalizationState()->state);
        $this->assertSame('en', $documentDE->getLocalizationState()->locale);

        $this->assertSame('ghost', $documentFR->getLocalizationState()->state);
        $this->assertSame('en', $documentFR->getLocalizationState()->locale);

        /** @var ArticleDocument $otherDocument */
        $otherDocumentEN = $this->findViewDocument($otherDocument->getUuid(), 'en');
        $otherDocumentDE = $this->findViewDocument($otherDocument->getUuid(), 'de');
        $otherDocumentFR = $this->findViewDocument($otherDocument->getUuid(), 'fr');

        $this->assertSame('localized', $otherDocumentDE->getLocalizationState()->state);
        $this->assertNull($otherDocumentDE->getLocalizationState()->locale);

        $this->assertSame('ghost', $otherDocumentEN->getLocalizationState()->state);
        $this->assertSame('de', $otherDocumentEN->getLocalizationState()->locale);

        $this->assertSame('ghost', $otherDocumentFR->getLocalizationState()->state);
        $this->assertSame('de', $otherDocumentFR->getLocalizationState()->locale);
    }

    public function testReplaceWithGhostDataUpdateExistingGhosts()
    {
        $article = $this->createArticle(
            [
                'article' => 'Test content',
            ],
            'Test Article English',
            'default_with_route'
        );

        $this->updateArticle(
            $article['id'],
            'de',
            [
                'id' => $article['id'],
                'article' => 'Test Inhalt',
            ],
            'Test Artikel Deutsch',
            'default_with_route'
        );

        $this->updateArticle(
            $article['id'],
            'fr',
            [
                'id' => $article['id'],
                'article' => 'Test Inhalt',
            ],
            'Test Artikel French',
            'default_with_route'
        );

        $documentEN = $this->findViewDocument($article['id'], 'en');
        $this->assertSame(LocalizationState::LOCALIZED, $documentEN->getLocalizationState()->state);
        $documentDE = $this->findViewDocument($article['id'], 'de');
        $this->assertSame(LocalizationState::LOCALIZED, $documentDE->getLocalizationState()->state);
        $documentFR = $this->findViewDocument($article['id'], 'fr');
        $this->assertSame(LocalizationState::LOCALIZED, $documentFR->getLocalizationState()->state);

        /** @var ArticleDocument $articleDocument */
        $articleDocument = $this->documentManager->find($article['id'], 'en');
        $this->indexer->replaceWithGhostData($articleDocument, 'fr');
        $this->indexer->flush();

        $documentEN = $this->findViewDocument($article['id'], 'en');
        $this->assertSame(LocalizationState::LOCALIZED, $documentEN->getLocalizationState()->state);
        $documentDE = $this->findViewDocument($article['id'], 'de');
        $this->assertSame(LocalizationState::LOCALIZED, $documentDE->getLocalizationState()->state);
        $documentFR = $this->findViewDocument($article['id'], 'fr');
        $this->assertSame(LocalizationState::GHOST, $documentFR->getLocalizationState()->state);
        $this->assertSame('en', $documentFR->getLocalizationState()->locale);

        /** @var ArticleDocument $articleDocument */
        $articleDocument = $this->documentManager->find($article['id'], 'de');
        $this->indexer->replaceWithGhostData($articleDocument, 'en');
        $this->indexer->flush();

        $documentEN = $this->findViewDocument($article['id'], 'en');
        $this->assertSame(LocalizationState::GHOST, $documentEN->getLocalizationState()->state);
        $this->assertSame('de', $documentEN->getLocalizationState()->locale);
        $documentDE = $this->findViewDocument($article['id'], 'de');
        $this->assertSame(LocalizationState::LOCALIZED, $documentDE->getLocalizationState()->state);
        $documentFR = $this->findViewDocument($article['id'], 'fr');
        $this->assertSame(LocalizationState::GHOST, $documentFR->getLocalizationState()->state);
        $this->assertSame('de', $documentFR->getLocalizationState()->locale);
    }

    public function testIndexDefaultWithRoute()
    {
        $article = $this->createArticle(
            [
                'article' => 'Test content',
            ],
            'Test Article',
            'default_with_route'
        );

        $this->liveIndexer = $this->getContainer()->get('sulu_article.elastic_search.article_live_indexer');

        $document = $this->documentManager->find($article['id'], $this->locale);
        $this->liveIndexer->index($document);

        $viewDocument = $this->findLiveViewDocument($article['id']);
        $this->assertEquals($document->getUuid(), $viewDocument->getUuid());
        $this->assertEquals('/articles/test-article', $viewDocument->getRoutePath());
        $this->assertInstanceOf('\DateTime', $viewDocument->getPublished());
        $this->assertTrue($viewDocument->getPublishedState());
        $this->assertEquals('localized', $viewDocument->getLocalizationState()->state);
        $this->assertNull($viewDocument->getLocalizationState()->locale);
    }

    public function testIndexShadow()
    {
        $article = $this->createArticle(
            [
                'article' => 'Test content',
            ],
            'Test Article',
            'default_with_route'
        );

        $secondLocale = 'de';

        // now add second locale
        $this->updateArticle(
            $article['id'],
            $secondLocale,
            [
                'id' => $article['id'],
                'article' => 'Test Inhalt',
            ],
            'Test Artikel Deutsch',
            'default_with_route'
        );

        // now transform second locale to shadow
        $this->updateArticle(
            $article['id'],
            $secondLocale,
            [
                'id' => $article['id'],
                'shadowOn' => true,
                'shadowBaseLanguage' => 'en',
            ],
            null,
            null
        );

        $viewDocument = $this->findLiveViewDocument($article['id'], $secondLocale);

        $this->assertEquals($article['id'], $viewDocument->getUuid());
        $this->assertEquals('/articles/test-artikel-deutsch', $viewDocument->getRoutePath());
        $this->assertInstanceOf('\DateTime', $viewDocument->getPublished());
        $this->assertTrue($viewDocument->getPublishedState());
        $this->assertEquals('shadow', $viewDocument->getLocalizationState()->state);
        $this->assertEquals($this->locale, $viewDocument->getLocalizationState()->locale);
        $this->assertEquals($secondLocale, $viewDocument->getLocale());
        $this->assertEquals('Test Article', $viewDocument->getTitle());

        $contentData = \json_decode($viewDocument->getContentData(), true);
        $this->assertEquals($contentData['article'], 'Test content');

        // now update the source locale
        // the shadow should be update also
        $this->updateArticle(
            $article['id'],
            $this->locale,
            [
                'id' => $article['id'],
                'article' => 'Test content - CHANGED!',
            ],
            'Test Article - CHANGED!',
            'default_with_route'
        );

        $viewDocument = $this->findLiveViewDocument($article['id'], $secondLocale);
        $this->assertEquals('Test Article - CHANGED!', $viewDocument->getTitle());

        $contentData = \json_decode($viewDocument->getContentData(), true);
        $this->assertEquals($contentData['article'], 'Test content - CHANGED!');
    }

    public function testUnpublishedShadows(): void
    {
        $article = $this->createArticle(
            [
                'article' => 'Test content',
            ],
            'Test Article',
            'default_with_route'
        );
        $secondLocale = 'de';
        $thirdLocale = 'fr';

        $this->updateArticle(
            $article['id'],
            $secondLocale,
            [
                'id' => $article['id'],
                'article' => 'Test Inhalt',
            ],
            'Test Artikel Deutsch',
            'default_with_route'
        );
        $this->updateArticle(
            $article['id'],
            $secondLocale,
            [
                'id' => $article['id'],
                'shadowOn' => true,
                'shadowBaseLanguage' => $this->locale,
            ],
            null,
            null
        );

        $this->updateArticle(
            $article['id'],
            $thirdLocale,
            [
                'id' => $article['id'],
                'article' => 'Test French Content',
            ],
            'Test Artikel French',
            'default_with_route'
        );
        $this->updateArticle(
            $article['id'],
            $thirdLocale,
            [
                'id' => $article['id'],
                'shadowOn' => true,
                'shadowBaseLanguage' => $this->locale,
            ],
            null,
            null
        );

        self::assertNotNull($this->findLiveViewDocument($article['id'], $this->locale));
        self::assertNotNull($this->findLiveViewDocument($article['id'], $secondLocale));
        self::assertNotNull($this->findLiveViewDocument($article['id'], $thirdLocale));

        $this->unpublishArticle($article['id'], $this->locale);
        $this->unpublishArticle($article['id'], $secondLocale);
        $this->unpublishArticle($article['id'], $thirdLocale);

        self::assertNull($this->findLiveViewDocument($article['id'], $this->locale));
        self::assertNull($this->findLiveViewDocument($article['id'], $secondLocale));
        self::assertNull($this->findLiveViewDocument($article['id'], $thirdLocale));

        // publish the shadow
        $this->updateArticle(
            $article['id'],
            $secondLocale,
            [
                'id' => $article['id'],
            ],
            null,
            null
        );

        // only the DE shadow should be published
        self::assertNull($this->findLiveViewDocument($article['id'], $this->locale));
        self::assertNotNull($this->findLiveViewDocument($article['id'], $secondLocale));
        self::assertNull($this->findLiveViewDocument($article['id'], $thirdLocale));
    }

    public function testIndexPageTreeRoute()
    {
        $page = $this->createPage();
        $article = $this->createArticle(
            [
                'routePath' => [
                    'page' => ['uuid' => $page->getUuid(), 'path' => $page->getResourceSegment()],
                    'path' => $page->getResourceSegment() . '/test-article',
                ],
            ],
            'Test Article',
            'page_tree_route'
        );

        $document = $this->documentManager->find($article['id'], $this->locale);
        $this->liveIndexer->index($document);

        $viewDocument = $this->findLiveViewDocument($article['id']);
        $this->assertEquals($page->getUuid(), $viewDocument->getParentPageUuid());
    }

    public function testSetUnpublished()
    {
        $article = $this->createArticle();

        $viewDocument = $this->liveIndexer->setUnpublished($article['id'], $this->locale);
        $this->assertNull($viewDocument->getPublished());
        $this->assertFalse($viewDocument->getPublishedState());
    }

    public function testIndexTaggedProperties()
    {
        $data = [
            'title' => 'Test Article Title',
            'pageTitle' => 'Test Page Title',
            'article' => '<p>Test Article</p>',
            'article_2' => '<p>should not be indexed</p>',
        ];

        if (\class_exists(ImageMapContentType::class)) {
            $data['blocks'] = [
                [
                    'type' => 'title-with-article',
                    'settings' => [],
                    'title' => 'Test Title in Block',
                    'article' => '<p>Test Article in Block</p>',
                ],
            ];
        } else {
            $data['blocks'] = [
                [
                    'type' => 'title-with-article',
                    'title' => 'Test Title in Block',
                    'article' => '<p>Test Article in Block</p>',
                ],
            ];
        }

        $article = $this->createArticle($data, $data['title'], 'default_with_search_tags');
        $this->documentManager->clear();

        $document = $this->documentManager->find($article['id'], $this->locale);
        $this->liveIndexer->index($document);
        $this->liveIndexer->flush();

        $viewDocument = $this->findLiveViewDocument($article['id']);
        $contentFields = $viewDocument->getContentFields();

        $this->assertSame($article['id'], $viewDocument->getUuid());
        $this->assertSame($data, \json_decode($viewDocument->getContentData(), true));

        $this->assertCount(5, $contentFields);
        $this->assertContains('Test Article Title', $contentFields);
        $this->assertContains('Test Page Title', $contentFields);
        $this->assertContains('Test Article', $contentFields);
        $this->assertContains('Test Title in Block', $contentFields);
        $this->assertContains('Test Article in Block', $contentFields);
    }

    public function testIndexTaggedPropertiesBlocksInBlocks(): void
    {
        if (!\method_exists(RouteRepositoryInterface::class, 'remove')) {
            $this->markTestSkipped('Only for Sulu > 2.1.0 (requires nested blocks)');
        }

        $data = [
            'title' => 'Test Article',
            'blocks' => [
                [
                    'type' => 'text-with-blocks',
                    'settings' => [],
                    'text_1' => 'Level 1 Text_1',
                    'blocks_1' => [
                        [
                            'type' => 'article-with-blocks',
                            'settings' => [],
                            'article_2' => 'Level 2 Article_1',
                            'blocks_2' => [
                                [
                                    'type' => 'area-with-blocks',
                                    'settings' => [],
                                    'area_3' => 'Level 3 Area_1',
                                    'blocks_3' => [
                                        [
                                            'type' => 'article',
                                            'settings' => [],
                                            'article_4' => 'Level 4 Article_1',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'text-with-blocks',
                    'settings' => [],
                    'text_1' => 'Level 1 Text_2',
                    'blocks_1' => [
                        [
                            'type' => 'article-with-blocks',
                            'settings' => [],
                            'article_2' => 'Level 2 Article_2',
                            'blocks_2' => [
                                [
                                    'type' => 'area-with-blocks',
                                    'settings' => [],
                                    'area_3' => 'Level 3 Area_2',
                                    'blocks_3' => [
                                        [
                                            'type' => 'article',
                                            'settings' => [],
                                            'article_4' => 'Level 4 Article_2',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $article = $this->createArticle($data, $data['title'], 'default_blocks_in_blocks');
        $this->documentManager->clear();

        $document = $this->documentManager->find($article['id'], $this->locale);
        $this->liveIndexer->index($document);
        $this->liveIndexer->flush();

        $viewDocument = $this->findLiveViewDocument($article['id']);
        $contentFields = $viewDocument->getContentFields();

        $this->assertEquals($article['id'], $viewDocument->getUuid());

        $this->assertCount(9, $contentFields);
        $this->assertContains('Test Article', $contentFields);
        $this->assertContains('Level 1 Text_1', $contentFields);
        $this->assertContains('Level 2 Article_1', $contentFields);
        $this->assertContains('Level 3 Area_1', $contentFields);
        $this->assertContains('Level 4 Article_1', $contentFields);
        $this->assertContains('Level 1 Text_2', $contentFields);
        $this->assertContains('Level 2 Article_2', $contentFields);
        $this->assertContains('Level 3 Area_2', $contentFields);
        $this->assertContains('Level 4 Article_2', $contentFields);
    }

    public function testIndexContentData()
    {
        $data = [
            'title' => 'Test Article',
            'routePath' => '/test-article',
            'pageTitle' => 'Test Page Title',
            'article' => 'Test Article',
        ];

        $article = $this->createArticle($data, $data['title'], 'default_pages');
        $this->documentManager->clear();

        $this->createArticlePage($article);
        $this->documentManager->clear();

        $document = $this->documentManager->find($article['id'], $this->locale);
        $this->liveIndexer->index($document);
        $this->liveIndexer->flush();

        $viewDocument = $this->findLiveViewDocument($article['id']);
        $this->assertEquals($article['id'], $viewDocument->getUuid());
        $this->assertEquals($data, \json_decode($viewDocument->getContentData(), true));

        $this->assertProxies($data, $viewDocument->getContent(), $viewDocument->getView());

        $this->assertCount(1, $viewDocument->getPages());
        foreach ($viewDocument->getPages() as $page) {
            $this->assertProxies(
                [
                    'title' => 'Test Article',
                    'routePath' => '/test-article/page-2',
                    'pageTitle' => 'Test-Page',
                    'article' => '',
                ],
                $page->content,
                $page->view
            );
        }
    }

    private function assertProxies(array $data, $contentProxy, $viewProxy)
    {
        $this->assertInstanceOf(\ArrayObject::class, $contentProxy);
        $this->assertInstanceOf(\ArrayObject::class, $viewProxy);

        $content = \iterator_to_array($contentProxy);
        $view = \iterator_to_array($viewProxy);

        $this->assertEquals($data, $content);
        foreach ($data as $key => $value) {
            $this->assertArrayHasKey($key, $view);
        }
    }

    /**
     * Create a new article.
     *
     * @param string $title
     * @param string $template
     *
     * @return mixed
     */
    private function createArticle(array $data = [], $title = 'Test Article', $template = 'default')
    {
        $this->client->jsonRequest(
            'POST',
            '/api/articles?locale=' . $this->locale . '&action=publish',
            \array_merge(['title' => $title, 'template' => $template], $data)
        );

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Update existing article.
     *
     * @param string $uuid
     * @param string $locale
     * @param string $title
     * @param string $template
     *
     * @return mixed
     */
    private function updateArticle(
        $uuid,
        $locale = null,
        array $data = [],
        $title = 'Test Article',
        $template = 'default'
    ) {
        $requestData = $data;

        if ($title) {
            $requestData['title'] = $title;
        }

        if ($template) {
            $requestData['template'] = $template;
        }

        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $uuid . '?locale=' . ($locale ? $locale : $this->locale) . '&action=publish',
            $requestData
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    private function unpublishArticle($uuid, $locale = null)
    {
        $this->client->jsonRequest(
            'POST',
            '/api/articles/' . $uuid . '?locale=' . ($locale ?: $this->locale) . '&action=unpublish'
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Create article page.
     *
     * @param string $pageTitle
     * @param string $template
     *
     * @return array
     */
    private function createArticlePage(
        array $article,
        array $data = [],
        $pageTitle = 'Test-Page',
        $template = 'default_pages'
    ) {
        $this->client->jsonRequest(
            'POST',
            '/api/articles/' . $article['id'] . '/pages?locale=' . $this->locale . '&action=publish',
            \array_merge(['pageTitle' => $pageTitle, 'template' => $template], $data)
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Create a new page.
     *
     * @param string $title
     * @param string $resourceSegment
     * @param string $locale
     *
     * @return PageDocument
     */
    private function createPage($title = 'Test Page', $resourceSegment = '/test-page', $locale = 'de')
    {
        $sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $page = $this->documentManager->create('page');

        $uuidReflection = new \ReflectionProperty(PageDocument::class, 'uuid');
        $uuidReflection->setAccessible(true);
        $uuidReflection->setValue($page, Uuid::uuid4()->toString());

        $page->setTitle($title);
        $page->setStructureType('default');
        $page->setParent($this->documentManager->find($sessionManager->getContentPath('sulu_io')));
        $page->setResourceSegment($resourceSegment);

        $this->documentManager->persist($page, $locale);
        $this->documentManager->publish($page, $locale);
        $this->documentManager->flush();

        return $page;
    }

    /**
     * Find view-document in live index.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return ArticleViewDocument
     */
    private function findLiveViewDocument($uuid, $locale = null)
    {
        return $this->liveManager->find(
            $this->getContainer()->getParameter('sulu_article.view_document.article.class'),
            $uuid . '-' . ($locale ? $locale : $this->locale)
        );
    }

    /**
     * Find view-document in live index.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return ArticleViewDocument
     */
    private function findViewDocument($uuid, $locale = null)
    {
        return $this->manager->find(
            $this->getContainer()->getParameter('sulu_article.view_document.article.class'),
            $uuid . '-' . ($locale ? $locale : $this->locale)
        );
    }
}
