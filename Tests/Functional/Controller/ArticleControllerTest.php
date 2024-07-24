<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ObjectRepository;
use ONGR\ElasticsearchBundle\Service\Manager;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ActivityBundle\Domain\Model\Activity;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\IndexerInterface;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\DocumentManagerBundle\Slugifier\Urlizer;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\PageBundle\Content\Types\SegmentSelect;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Functional testcases for Article API.
 */
class ArticleControllerTest extends SuluTestCase
{
    use ArticleViewDocumentIdTrait;

    private static $typeMap = ['default' => 'blog', 'simple' => 'video', 'default_fallback' => 'other'];

    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var ContactManager
     */
    private $contactManager;

    /**
     * @var ObjectRepository<ActivityInterface>
     */
    private $activityRespository;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();

        $this->initPhpcr();
        $this->purgeDatabase();
        $this->purgeIndex();

        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        $this->userManager = $this->getContainer()->get('sulu_security.user_manager');
        $this->contactManager = $this->getContainer()->get('sulu_contact.contact_manager');

        $this->activityRespository = $this->getEntityManager()->getRepository(Activity::class);

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    protected function post(
        $title = 'Test-Article',
        $template = 'default',
        $authored = '2016-01-01',
        $action = null
    ) {
        $requestData = [
            'title' => $title,
            'template' => $template,
            'authored' => \date('c', \strtotime($authored)),
            'action' => $action,
        ];

        $this->client->jsonRequest(
            'POST',
            '/api/articles?locale=de',
            $requestData
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    protected function postPage($article, $pageTitle = 'Test-Page')
    {
        $this->client->jsonRequest(
            'POST',
            '/api/articles/' . $article['id'] . '/pages?locale=de',
            [
                'pageTitle' => $pageTitle,
                'template' => $article['template'],
                'authored' => \date('c', \strtotime('2016-01-01')),
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testPost($title = 'Test-Article', $template = 'default')
    {
        $response = $this->post($title, $template);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals($this->getRoute($title), $response['route']);
        $this->assertEquals(self::$typeMap[$template], $response['articleType']);
        $this->assertEquals($template, $response['template']);
        $this->assertEquals('2016-01-01', \date('Y-m-d', \strtotime($response['authored'])));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);
        $this->assertFalse($response['customizeWebspaceSettings']);

        $this->assertNotNull($this->findViewDocument($response['id'], 'de'));

        /** @var ActivityInterface $activity */
        $activity = $this->activityRespository->findOneBy(['type' => 'created']);
        $this->assertNotNull($activity);

        return $response;
    }

    public function testPostWithAuthor($title = 'Sulu is awesome', $locale = 'de')
    {
        $user = $this->createContact();

        $this->client->jsonRequest(
            'POST',
            '/api/articles?locale=' . $locale,
            [
                'title' => $title,
                'template' => 'default',
                'authored' => \date('c', \strtotime('2016-01-01')),
                'author' => $user->getId(),
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals($this->getRoute($title), $response['route']);
        $this->assertEquals('2016-01-01', \date('Y-m-d', \strtotime($response['authored'])));
        $this->assertEquals($user->getId(), $response['author']);

        $this->assertNotNull($this->findViewDocument($response['id'], 'de'));
    }

    protected function get($id, $locale = 'de')
    {
        $this->client->jsonRequest('GET', '/api/articles/' . $id . '?locale=' . $locale);

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testGet()
    {
        $article = $this->testPost();

        $response = $this->get($article['id']);
        foreach ($article as $name => $value) {
            $this->assertEquals($value, $response[$name]);
        }
    }

    protected function put($title = 'Sulu is awesome', $locale = 'de', $shadowLocale = null, $article = null)
    {
        if (!$article) {
            $article = $this->post();
        }

        $requestData = [
            'title' => $title,
            'template' => 'default',
            'authored' => \date('c', \strtotime('2016-01-01')),
        ];

        if ($shadowLocale) {
            $requestData['shadowOn'] = true;
            $requestData['shadowBaseLanguage'] = $shadowLocale;
        }

        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=' . $locale,
            $requestData
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testPut($title = 'Sulu is awesome', $locale = 'de', $article = null)
    {
        if (!$article) {
            $article = $this->testPost();
        }

        $response = $this->put($title, $locale, null, $article);
        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($article['route'], $response['route']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals('2016-01-01', \date('Y-m-d', \strtotime($response['authored'])));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);

        $this->assertNotNull($this->findViewDocument($response['id'], 'de'));

        /** @var ActivityInterface $activity */
        $activity = $this->activityRespository->findOneBy(['type' => 'modified']);
        $this->assertSame((string) $article['id'], $activity->getResourceId());

        return $article;
    }

    public function testPutWithAuthor($title = 'Sulu is awesome', $locale = 'de')
    {
        $user = $this->createContact();

        $article = $this->testPost();

        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=' . $locale,
            [
                'title' => $title,
                'template' => 'default',
                'authored' => \date('c', \strtotime('2016-01-01')),
                'author' => $user->getId(),
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals($article['route'], $response['route']);
        $this->assertEquals('2016-01-01', \date('Y-m-d', \strtotime($response['authored'])));
        $this->assertEquals($user->getId(), $response['author']);

        $this->assertNotNull($this->findViewDocument($response['id'], 'de'));
    }

    public function testPutTranslation($title = 'Sulu is nice')
    {
        $article = $this->put('Sulu ist toll', 'de');
        $response = $this->put($title, 'en', null, $article);

        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($this->getRoute($title), $response['route']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals('2016-01-01', \date('Y-m-d', \strtotime($response['authored'])));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);

        $this->assertNotNull($this->findViewDocument($response['id'], 'de'));

        /** @var ActivityInterface $activity */
        $activity = $this->activityRespository->findOneBy(['type' => 'translation_added']);
        $this->assertSame((string) $article['id'], $activity->getResourceId());
    }

    public function provideCustomWebspaceSettings()
    {
        return [
            [
                'Sulu is nice',
                'de',
            ],
            [
                'Sulu is nice',
                'de',
                'test',
                ['sulu_io', 'test-2'],
            ],
            [
                'Sulu is nice',
                'de',
                'sulu_io',
                ['test-2'],
            ],
            [
                'Sulu is nice',
                'de',
                'sulu_io',
            ],
        ];
    }

    /**
     * @dataProvider provideCustomWebspaceSettings
     */
    public function testPutCustomWebspaceSettings($title, $locale, $mainWebspace = null, $additionalWebspaces = null)
    {
        $article = $this->testPost();

        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=' . $locale,
            [
                'title' => $title,
                'template' => 'default',
                'mainWebspace' => $mainWebspace,
                'additionalWebspaces' => $additionalWebspaces,
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        // check response
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($title, $response['title']);
        $customizeWebspaceSettings = $response['customizeWebspaceSettings'];
        $this->assertEquals(null !== $mainWebspace, $customizeWebspaceSettings);
        $this->assertEquals($mainWebspace ?? 'sulu_io', $response['mainWebspace']);
        $this->assertEquals($customizeWebspaceSettings ? $additionalWebspaces : ($additionalWebspaces ?? []), $response['additionalWebspaces']);

        // check if phpcr document is correct
        $this->documentManager->clear();

        /** @var ArticleDocument $document */
        $document = $this->documentManager->find($response['id'], 'de');

        $this->assertEquals($title, $document->getTitle());
        $this->assertEquals($mainWebspace, $document->getMainWebspace());
        $this->assertEquals($additionalWebspaces, $document->getAdditionalWebspaces());

        /** @var ArticleViewDocument $viewDocument */
        $viewDocument = $this->findViewDocument($response['id'], 'de');
        $this->assertNotNull($viewDocument);
        $this->assertEquals($mainWebspace ?? 'sulu_io', $viewDocument->getMainWebspace());
        $this->assertEquals($additionalWebspaces ?? [], $viewDocument->getAdditionalWebspaces());

        // test that ghost do not serve default webspace settings
        $response = $this->get($article['id'], 'en');
        $this->assertEquals('sulu_io', $response['mainWebspace']);
        $this->assertEquals([], $response['additionalWebspaces']);

        $viewDocument = $this->findViewDocument($response['id'], 'en');
        $this->assertNotNull($viewDocument);
        $this->assertEquals('sulu_io', $viewDocument->getMainWebspace());
        $this->assertEquals([], $viewDocument->getAdditionalWebspaces());
    }

    /**
     * @dataProvider provideCustomWebspaceSettings
     */
    public function testPutCustomWebspaceSettingsWithShadow($title, $locale, $mainWebspace = null, $additionalWebspaces = null)
    {
        $article = $this->testPost();

        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=' . $locale,
            [
                'title' => $title,
                'template' => 'default',
                'mainWebspace' => $mainWebspace,
                'additionalWebspaces' => $additionalWebspaces,
            ]
        );

        $this->put('Sulu is great', 'en', $locale, $article);

        $response = $this->get($article['id'], 'en');
        $this->assertEquals($title, $response['title']);
        $customizeWebspaceSettings = $response['customizeWebspaceSettings'];
        $this->assertEquals(null !== $mainWebspace, $customizeWebspaceSettings);
        $this->assertEquals($mainWebspace ?? 'sulu_io', $response['mainWebspace']);
        $this->assertEquals($customizeWebspaceSettings ? $additionalWebspaces : ($additionalWebspaces ?? []), $response['additionalWebspaces']);

        /** @var ArticleViewDocument $viewDocument */
        $viewDocument = $this->findViewDocument($response['id'], 'en');
        $this->assertNotNull($viewDocument);
        $this->assertEquals($mainWebspace ?? 'sulu_io', $viewDocument->getMainWebspace());
        $this->assertEquals($additionalWebspaces ?? [], $viewDocument->getAdditionalWebspaces());
    }

    public function testGetEmptyDocument()
    {
        $title = 'Sulu ist toll';
        $article = $this->testPut($title, 'de');

        $this->client->jsonRequest('GET', '/api/articles/' . $article['id'] . '?locale=en');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('', $response['title']);
        $this->assertEquals(null, $response['authored']);
        $this->assertEquals(null, $response['author']);
        $this->assertEquals($article['template'], $response['template']);
    }

    public function testGetShadow()
    {
        $title = 'Sulu ist toll';
        $article = $this->put($title, 'de');

        $this->put('Sulu is great', 'en', 'de', $article);

        $this->client->jsonRequest('GET', '/api/articles/' . $article['id'] . '?locale=en');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals('2016-01-01', \date('Y-m-d', \strtotime($response['authored'])));
        $this->assertEquals($this->getTestUser()->getContact()->getId(), $response['author']);
        $this->assertEquals(['name' => 'shadow', 'value' => 'de'], $response['type']);
        $this->assertEquals('/articles/sulu-is-great', $response['route']);
        $this->assertTrue($response['shadowOn']);
        $this->assertEquals('de', $response['shadowBaseLanguage']);
    }

    public function testCGetGhost()
    {
        $title1 = 'Sulu ist toll - Test 1';
        $article1 = $this->put($title1, 'de');

        $title2 = 'Sulu ist toll - Test 2';
        $article2 = $this->put($title2, 'de');

        $title2_EN = $title2 . ' (EN)';
        $this->put($title2_EN, 'en', null, $article2);

        // Retrieve articles in 'de'.
        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=blog&fields=title');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $title1], $items);
        $this->assertContains([$article2['id'], $title2], $items);

        // Retrieve articles in 'en'.
        $this->client->jsonRequest('GET', '/api/articles?locale=en&types=blog&fields=title');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $title1], $items);
        $this->assertContains([$article2['id'], $title2_EN], $items);
    }

    public function testCGetExcludeGhost()
    {
        $title1 = 'Sulu ist toll - Test 1';
        $this->put($title1, 'de');

        $title2 = 'Sulu ist toll - Test 2';
        $article2 = $this->put($title2, 'de');

        $title2_EN = $title2 . ' (EN)';
        $this->put($title2_EN, 'en', null, $article2);

        // Retrieve articles in 'de'.
        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=blog&fields=title&exclude-ghosts=true');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        // Retrieve articles in 'en'.
        $this->client->jsonRequest('GET', '/api/articles?locale=en&types=blog&fields=title&exclude-ghosts=true');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);
    }

    public function testCGetShadow()
    {
        $title1 = 'Sulu ist toll - Test 1';
        $article1 = $this->put($title1, 'de');

        $title2 = 'Sulu ist toll - Test 2';
        $article2 = $this->put($title2, 'de');

        // create second language for article2
        $title2_EN = $title2 . ' (EN)';
        $this->put($title2_EN, 'en', null, $article2);

        $title3 = 'Sulu ist toll - Test 3';
        $article3 = $this->put($title3, 'de');

        // create shadow for article3
        $title3_EN = $title2 . ' (EN)';
        $this->put($title3_EN, 'en', 'de', $article3);

        // Retrieve articles in 'de'.
        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=blog&fields=title');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(3, $response['total']);
        $this->assertCount(3, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $title1], $items);
        $this->assertContains([$article2['id'], $title2], $items);
        $this->assertContains([$article3['id'], $title3], $items);

        // Retrieve articles in 'en'.
        $this->client->jsonRequest('GET', '/api/articles?locale=en&types=blog&fields=title,route');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(3, $response['total']);
        $this->assertCount(3, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title'], $item['localizationState']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $title1, ['state' => 'ghost', 'locale' => 'de']], $items);
        $this->assertContains([$article2['id'], $title2_EN, ['state' => 'localized']], $items);
        $this->assertContains([$article3['id'], $title3, ['state' => 'shadow', 'locale' => 'de']], $items);
    }

    public function testCGetExcludeShadow()
    {
        $title1 = 'Sulu ist toll - Test 1';
        $article1 = $this->put($title1, 'de');

        $title2 = 'Sulu ist toll - Test 2';
        $article2 = $this->put($title2, 'de');

        // create second language for article2
        $title2_EN = $title2 . ' (EN)';
        $this->put($title2_EN, 'en', null, $article2);

        $title3 = 'Sulu ist toll - Test 3';
        $article3 = $this->put($title3, 'de');

        // create shadow for article3
        $title3_EN = $title2 . ' (EN)';
        $this->put($title3_EN, 'en', 'de', $article3);

        // Retrieve articles in 'de'.
        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=blog&fields=title&exclude-shadows=true');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(3, $response['total']);
        $this->assertCount(3, $response['_embedded']['articles']);

        // Retrieve articles in 'en'.
        $this->client->jsonRequest('GET', '/api/articles?locale=en&types=blog&fields=title,route&exclude-shadows=true');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);
    }

    public function testPutExtensions(
        $title = 'Sulu is awesome',
        $extensions = [
            'seo' => [
                'title' => 'Seo title',
                'description' => 'Seo description',
                'keywords' => 'Seo keywords',
                'canonicalUrl' => 'http://canonical.lo',
                'hideInSitemap' => true,
                'noFollow' => false,
                'noIndex' => true,
            ],
            'excerpt' => [
                'title' => 'Excerpt title',
                'description' => 'Excerpt title',
                'more' => 'Excerpt more',
                'categories' => [1],
                'tags' => [
                    'Excerpt',
                    'Tags',
                ],
                'icon' => [
                    'displayOption' => 'top',
                    'ids' => [1],
                ],
                'images' => [
                    'displayOption' => 'top',
                    'ids' => [1],
                ],
                'audience_targeting_groups' => [],
                'segments' => [],
            ],
        ]
    ) {
        $media = $this->createMedia();

        $extensions['excerpt']['icon']['ids'] = [$media->getId()];
        $extensions['excerpt']['images']['ids'] = [$media->getId()];

        $article = $this->testPost();

        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=de',
            [
                'title' => $title,
                'template' => 'default',
                'ext' => $extensions,
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals($extensions['seo'], $response['ext']['seo']);

        if (!\class_exists(SegmentSelect::class)) {
            // segment is only returned for sulu versions >= 2.2
            unset($extensions['excerpt']['segments']);
        }

        $this->assertEquals($extensions['excerpt'], $response['ext']['excerpt']);
    }

    public function testPutDifferentTemplate($title = 'Sulu', $description = 'Sulu is awesome')
    {
        $article = $this->testPost();

        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=de',
            ['title' => $title, 'description' => $description, 'template' => 'simple']
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEquals($article['title'], $response['title']);
        $this->assertEquals($title, $response['title']);
        $this->assertEquals('simple', $response['template']);
        $this->assertEquals(self::$typeMap['simple'], $response['articleType']);
        $this->assertEquals($description, $response['description']);
    }

    public function testPutDifferentLocale($title = 'Sulu is awesome')
    {
        $article = $this->testPost();

        $this->client->jsonRequest(
            'PUT',
            '/api/articles/' . $article['id'] . '?locale=en',
            ['title' => $title, 'template' => 'default']
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['de', 'en'], $response['contentLocales']);
        $this->assertEquals($title, $response['title']);
    }

    public function testCGet()
    {
        $article1 = $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome');
        $this->flush();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=blog');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title']], $items);
        $this->assertContains([$article2['id'], $article2['title']], $items);
    }

    public function testCGetIds()
    {
        $article1 = $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome');
        $this->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/articles?locale=de&ids=' . \implode(',', [$article2['id'], $article1['id']])
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($article1['id'], $response['_embedded']['articles'][1]['id']);
    }

    public function testCGetAuthoredRange()
    {
        $this->post('Sulu');
        $this->flush();
        $article = $this->post('Sulu is awesome', 'default', '2016-01-10');
        $this->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/articles?locale=de&types=blog&authoredFrom=2016-01-09&authoredTo=2016-01-11&fields=title'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        $this->assertEquals($response['_embedded']['articles'][0]['title'], $article['title']);
        $this->assertEquals($response['_embedded']['articles'][0]['id'], $article['id']);
    }

    public function testCGetWorkflowStage()
    {
        $this->post('Sulu');
        $this->flush();
        $article = $this->post('Sulu is awesome', 'default', '2016-01-10', 'publish');
        $this->flush();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=blog&workflowStage=published&fields=title');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        $this->assertEquals($response['_embedded']['articles'][0]['title'], $article['title']);
        $this->assertEquals($response['_embedded']['articles'][0]['id'], $article['id']);
    }

    public function testCGetSearch()
    {
        $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome');
        $this->flush();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&searchFields=title&search=awesome&types=blog&fields=title');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);
        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($article2['title'], $response['_embedded']['articles'][0]['title']);
    }

    public function testCGetSearchWithoutSearchFields()
    {
        $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome');
        $this->flush();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&search=awesome&types=blog&fields=title');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);
        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($article2['title'], $response['_embedded']['articles'][0]['title']);
    }

    public function testCGetSearchRoutePath()
    {
        $articles = [
            $this->testPost('Sulu'),
            $this->testPost('Sulu is awesome'),
        ];
        $this->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/articles?locale=de&searchFields=route_path&search=/articles&types=blog&fields=title'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);
        $this->assertEquals($articles[0]['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($articles[0]['title'], $response['_embedded']['articles'][0]['title']);
        $this->assertEquals($articles[1]['id'], $response['_embedded']['articles'][1]['id']);
        $this->assertEquals($articles[1]['title'], $response['_embedded']['articles'][1]['title']);
    }

    public function testCGetSearchFieldRoutePath()
    {
        $routePathData = [
            'suffix' => '/test-route',
        ];
        $article = $this->postPageTreeRoute($routePathData, 'Article Name');

        $this->client->jsonRequest(
            'GET',
            '/api/articles?locale=de&page=1&limit=10&fields=id,title,creatorFullName,changerFullName,authorFullName,routePath&search=test-route&flat=true'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);
        $this->assertEquals($article['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($article['title'], $response['_embedded']['articles'][0]['title']);
    }

    public function testCGetSearchCaseInsensitive()
    {
        $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome');
        $this->flush();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&searchFields=title&search=AwEsoMe&types=blog&fields=title');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);
        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($article2['title'], $response['_embedded']['articles'][0]['title']);
    }

    public function testCGetSort()
    {
        $article1 = $this->testPost('Hikaru Sulu');
        $this->flush();
        $article2 = $this->testPost('USS Enterprise');
        $this->flush();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&sortBy=title&sortOrder=desc&types=blog&fields=title');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);
        $this->assertEquals($article2['id'], $response['_embedded']['articles'][0]['id']);
        $this->assertEquals($article2['title'], $response['_embedded']['articles'][0]['title']);
        $this->assertEquals($article1['id'], $response['_embedded']['articles'][1]['id']);
        $this->assertEquals($article1['title'], $response['_embedded']['articles'][1]['title']);
    }

    public function testCGetTypes()
    {
        $article1 = $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome', 'simple');
        $this->flush();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=blog&fields=title');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title']], $items);

        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=video&fields=title');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article2['id'], $article2['title']], $items);
    }

    public function testCGetMultipleTypes()
    {
        $article1 = $this->testPost('Sulu', 'default');
        $article2 = $this->testPost('Sulu is awesome', 'simple');
        $article3 = $this->testPost('Sulu is great', 'default_fallback');
        $this->flush();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=blog,video&fields=title');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
    }

    public function testCGetFilterByContactId()
    {
        // create contact1
        $contact1 = $this->contactManager->save(
            [
                'firstName' => 'Testi 1',
                'lastName' => 'Testo 1',
            ],
            null,
            false,
            true
        );

        // create contact2
        $contact2 = $this->contactManager->save(
            [
                'firstName' => 'Testi 2',
                'lastName' => 'Testo 2',
            ],
            null,
            false,
            true
        );

        // create contact3
        $contact3 = $this->contactManager->save(
            [
                'firstName' => 'Testi 3',
                'lastName' => 'Testo 3',
            ],
            null,
            false,
            true
        );

        // create user1
        $user1 = $this->userManager->save(
            [
                'username' => 'testi.testo1',
                'email' => 'testi.testo1@LOL.xyz',
                'password' => 'ThisIsSave!#123',
                'contact' => [
                    'id' => $contact1->getId(),
                ],
            ],
            'de',
            null,
            false,
            true
        );

        // create user2
        $user2 = $this->userManager->save(
            [
                'username' => 'testi.testo2',
                'email' => 'testi.testo2@LOL.xyz',
                'password' => 'ThisIsSave!#123',
                'contact' => [
                    'id' => $contact2->getId(),
                ],
            ],
            'de',
            null,
            false,
            true
        );

        // create user3
        $user3 = $this->userManager->save(
            [
                'username' => 'testi.testo3',
                'email' => 'testi.testo3@LOL.xyz',
                'password' => 'ThisIsSave!#123',
                'contact' => [
                    'id' => $contact3->getId(),
                ],
            ],
            'de',
            null,
            false,
            true
        );

        /** @var ArticleDocument $article */
        $article = $this->documentManager->create('article');
        $article->setTitle('first title');
        $article->setStructureType('default');
        // user 3 is author
        $article->setAuthor($contact3->getId());

        // user 1 is creator
        $this->documentManager->persist($article, 'de', ['user' => $user1->getId()]);
        $this->documentManager->publish($article, 'de');
        $this->documentManager->flush();

        // user 2 is changer
        $this->documentManager->persist($article, 'de', ['user' => $user2->getId()]);
        $this->documentManager->publish($article, 'de');
        $this->documentManager->flush();

        // add article
        /** @var ArticleDocument $article */
        $article2 = $this->documentManager->create('article');
        $article2->setTitle('first title');
        $article2->setStructureType('default');

        $this->documentManager->persist($article2, 'de', ['user' => $user1->getId()]);
        $this->documentManager->publish($article2, 'de');
        $this->documentManager->flush();

        // retrieve all articles for user1
        $this->client->jsonRequest(
            'GET',
            '/api/articles?locale=de&searchFields=title&types=blog&contactId=' . $user1->getContact()->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        // retrieve all articles for user2
        $this->client->jsonRequest(
            'GET',
            '/api/articles?locale=de&searchFields=title&types=blog&contactId=' . $user2->getContact()->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        // retrieve all articles for user1
        $this->client->jsonRequest(
            'GET',
            '/api/articles?locale=de&searchFields=title&types=blog&contactId=' . $user1->getContact()->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        // retrieve all articles for user2
        $this->client->jsonRequest(
            'GET',
            '/api/articles?locale=de&searchFields=title&types=blog&contactId=' . $user2->getContact()->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);

        // retrieve all articles for user3
        $this->client->jsonRequest(
            'GET',
            '/api/articles?locale=de&searchFields=title&types=blog&contactId=' . $user3->getContact()->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['articles']);
    }

    public function testDelete()
    {
        $article = $this->testPost('Sulu');
        $this->flush();

        $this->client->jsonRequest('DELETE', '/api/articles/' . $article['id']);
        $this->flush();

        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=blog');

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(0, $response['total']);
        $this->assertCount(0, $response['_embedded']['articles']);

        $this->assertNull($this->findViewDocument($article['id'], 'de'));

        /** @var ActivityInterface $activity */
        $activity = $this->activityRespository->findOneBy(['type' => 'removed']);
        $this->assertSame((string) $article['id'], $activity->getResourceId());
    }

    public function testCDelete()
    {
        $article1 = $this->testPost('Sulu');
        $this->flush();
        $article2 = $this->testPost('Sulu is awesome', 'simple');
        $this->flush();

        $this->client->jsonRequest('DELETE', '/api/articles?ids=' . \implode(',', [$article1['id'], $article2['id']]));
        $this->flush();

        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/articles?locale=de&types=blog');

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(0, $response['total']);
        $this->assertCount(0, $response['_embedded']['articles']);

        $this->assertNull($this->findViewDocument($article1['id'], 'de'));
        $this->assertNull($this->findViewDocument($article2['id'], 'de'));
    }

    public function testCopy()
    {
        // create article
        $sourceArticle = $this->testPost('Sulu ist toll - Artikel 1');
        $this->publish($sourceArticle['id']);
        $this->flush();

        // trigger copy action
        $this->client->jsonRequest('POST',
            \sprintf('/api/articles/%s?action=copy', $sourceArticle['id'])
        );
        $response = $this->client->getResponse();

        $result = \json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);

        $this->assertNotEquals($sourceArticle['id'], $result['id']);
        $this->assertEquals($sourceArticle['title'], $result['title']);
        $this->assertEquals($sourceArticle['articleType'], $result['articleType']);
        $this->assertEquals(false, $result['publishedState']);
    }

    public function testCopyLocaleWithoutSource()
    {
        // prepare vars
        $locale = 'de';
        $destLocale = 'en';

        // create article in default locale
        $article1 = $this->testPost('Sulu ist toll - Artikel 1');
        $this->publish($article1['id']);
        $article2 = $this->testPost('Sulu ist toll - Artikel 2');
        $this->publish($article2['id']);
        $this->flush();

        // get all articles in default locale
        $this->client->jsonRequest('GET', '/api/articles?locale=' . $locale . '&types=blog&fields=title');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title']], $items);
        $this->assertContains([$article2['id'], $article2['title']], $items);

        // get all articles in dest locale (both should be ghosts)
        $this->client->jsonRequest('GET', '/api/articles?locale=' . $destLocale . '&types=blog&fields=title');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title'], $item['localizationState']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title'], ['state' => 'ghost', 'locale' => 'de']], $items);
        $this->assertContains([$article2['id'], $article2['title'], ['state' => 'ghost', 'locale' => 'de']], $items);

        // request copy-locale post action for article1 (published de locale should be returned)
        $this->client->jsonRequest(
            'POST',
            '/api/articles/' . $article1['id'] . '?locale=' . $locale . '&dest=' . $destLocale . '&action=copy-locale'
        );
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($article1['id'], $response['id']);
        $this->assertEquals(true, $response['publishedState']);
        $this->assertEquals([$locale, $destLocale], $response['contentLocales']);

        // get all articles in dest locale (now only one should be a ghost)
        $this->client->jsonRequest('GET', '/api/articles?locale=' . $destLocale . '&types=blog&fields=title');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title'], $item['localizationState']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title'], ['state' => 'localized']], $items);
        $this->assertContains([$article2['id'], $article2['title'], ['state' => 'ghost', 'locale' => 'de']], $items);
    }

    public function testCopyLocaleWithSource()
    {
        // prepare vars
        $locale = 'en';
        $srclocale = 'de';
        $destLocale = 'en';

        // create article in default locale
        $article1 = $this->testPost('Sulu ist toll - Artikel 1');
        $this->publish($article1['id']);
        $article2 = $this->testPost('Sulu ist toll - Artikel 2');
        $this->publish($article2['id']);
        $this->flush();

        // get all articles in default locale
        $this->client->jsonRequest('GET', '/api/articles?locale=' . $srclocale . '&types=blog&fields=title');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title']], $items);
        $this->assertContains([$article2['id'], $article2['title']], $items);

        // get all articles in dest locale (both should be ghosts)
        $this->client->jsonRequest('GET', '/api/articles?locale=' . $destLocale . '&types=blog&fields=title');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title'], $item['localizationState']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title'], ['state' => 'ghost', 'locale' => 'de']], $items);
        $this->assertContains([$article2['id'], $article2['title'], ['state' => 'ghost', 'locale' => 'de']], $items);

        // request copy-locale post action for article1 (unpublished en locale should be returned)
        $this->client->jsonRequest(
            'POST',
            '/api/articles/' . $article1['id'] . '?locale=' . $locale . '&src=' . $srclocale . '&dest=' . $destLocale . '&action=copy-locale'
        );
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($article1['id'], $response['id']);
        $this->assertEquals(false, $response['publishedState']);
        $this->assertEquals([$srclocale, $destLocale], $response['contentLocales']);

        // get all articles in dest locale (now only one should be a ghost)
        $this->client->jsonRequest('GET', '/api/articles?locale=' . $destLocale . '&types=blog&fields=title');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['articles']);

        $items = \array_map(
            function($item) {
                return [$item['id'], $item['title'], $item['localizationState']];
            },
            $response['_embedded']['articles']
        );

        $this->assertContains([$article1['id'], $article1['title'], ['state' => 'localized']], $items);
        $this->assertContains([$article2['id'], $article2['title'], ['state' => 'ghost', 'locale' => 'de']], $items);

        /** @var ActivityInterface $activity */
        $activity = $this->activityRespository->findOneBy(['type' => 'translation_copied']);
        $this->assertSame((string) $article1['id'], $activity->getResourceId());
    }

    public function testCgetFilterByCategory()
    {
        $title = 'Test-Article';
        $template = 'default';
        $category = $this->createCategory();

        $this->client->jsonRequest(
            'POST',
            '/api/articles?locale=de',
            [
                'title' => $title,
                'template' => $template,
                'authored' => \date('c', \strtotime('2016-01-01')),
                'ext' => ['excerpt' => ['categories' => [$category->getId()]]],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $article1 = \json_decode($this->client->getResponse()->getContent(), true);
        // create second article which should not appear in response
        $article2 = $this->post();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&categoryId=' . $category->getId());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $result['_embedded']['articles']);
        $this->assertEquals($article1['id'], $result['_embedded']['articles'][0]['id']);
    }

    public function testCgetFilterByTag()
    {
        $title = 'Test-Article';
        $template = 'default';
        $tag = $this->createTag();

        $this->client->jsonRequest(
            'POST',
            '/api/articles?locale=de',
            [
                'title' => $title,
                'template' => $template,
                'authored' => \date('c', \strtotime('2016-01-01')),
                'ext' => ['excerpt' => ['tags' => [$tag->getName()]]],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $article1 = \json_decode($this->client->getResponse()->getContent(), true);
        // create second article which should not appear in response
        $article2 = $this->post();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&tagId=' . $tag->getId());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $result['_embedded']['articles']);
        $this->assertEquals($article1['id'], $result['_embedded']['articles'][0]['id']);
    }

    public function testCgetFilterByPage()
    {
        $page = $this->createPage('Test Page', '/test-page');

        $routePathData = [
            'page' => [
                'uuid' => $page->getUuid(),
                'path' => $page->getResourceSegment(),
                'webspace' => 'sulu_io',
            ],
            'suffix' => 'test-article',
            'path' => '/test-page/test-article',
        ];

        $article1 = $this->postPageTreeRoute($routePathData);

        // create second article which should not appear in response
        $this->post();

        $this->client->jsonRequest('GET', '/api/articles?locale=de&pageId=' . $page->getUuid());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $result['_embedded']['articles']);
        $this->assertEquals($article1['id'], $result['_embedded']['articles'][0]['id']);
    }

    public function testPostPageTreeRoute()
    {
        $page = $this->createPage('Test Page', '/test-page');

        $routePathData = [
            'page' => [
                'uuid' => $page->getUuid(),
                'path' => $page->getResourceSegment(),
            ],
            'suffix' => '/test-article',
            'path' => '/test-page/test-article',
        ];

        $response = $this->postPageTreeRoute($routePathData);

        $this->assertEquals('Test Article', $response['title']);
        $this->assertEquals('/test-page/test-article', $response['route']);
        $this->assertEquals($routePathData, $response['routePath']);
    }

    public function testPostPageTreeRouteGenerate()
    {
        $page = $this->createPage('Test Page', '/test-page');

        $routePathData = [
            'page' => [
                'uuid' => $page->getUuid(),
                'path' => $page->getResourceSegment(),
            ],
            'suffix' => '/articles/test-article',
            'path' => '/test-page/articles/test-article',
        ];

        $response = $this->postPageTreeRoute(['page' => $routePathData['page']]);

        $this->assertEquals('Test Article', $response['title']);
        $this->assertEquals('/test-page/articles/test-article', $response['route']);
        $this->assertEquals($routePathData, $response['routePath']);
    }

    public function testPostPageTreeRouteGeneratePublishPage()
    {
        $page = $this->createPage('Test Page', '/test-page');

        $routePathData = [
            'page' => [
                'uuid' => $page->getUuid(),
                'path' => $page->getResourceSegment(),
            ],
            'suffix' => 'articles/test-article',
            'path' => '/test-page/articles/test-article',
        ];

        $article = $this->postPageTreeRoute(['page' => $routePathData['page']]);

        $page->setResourceSegment('/test-page-2');

        $this->documentManager->persist($page, 'de');
        $this->documentManager->publish($page, 'de');
        $this->documentManager->flush();
        $this->documentManager->clear();

        $this->client->jsonRequest('GET', '/api/articles/' . $article['id'] . '?locale=de');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Test Article', $response['title']);
        $this->assertEquals('/test-page-2/articles/test-article', $response['route']);
        $this->assertEquals(
            [
                'page' => [
                    'uuid' => $page->getUuid(),
                    'path' => $page->getResourceSegment(),
                ],
                'suffix' => '/articles/test-article',
                'path' => '/test-page-2/articles/test-article',
            ],
            $response['routePath']
        );
    }

    public function testPostPageTreeRouteGenerateMovePage()
    {
        $page1 = $this->createPage('Page 1', '/page-1');
        $page2 = $this->createPage('Page 2', '/page-2');

        $routePathData = [
            'page' => [
                'uuid' => $page1->getUuid(),
                'path' => $page1->getResourceSegment(),
            ],
            'suffix' => '/test-article',
            'path' => '/test-page/articles/test-article',
        ];

        $article = $this->postPageTreeRoute(['page' => $routePathData['page']]);

        $this->documentManager->move($page1, $page2->getUuid());
        $this->documentManager->flush();
        $this->documentManager->clear();

        $page1 = $this->documentManager->find($page1->getUuid(), 'de');

        $this->client->jsonRequest('GET', '/api/articles/' . $article['id'] . '?locale=de');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Test Article', $response['title']);
        $this->assertEquals('/page-2/page-1/articles/test-article', $response['route']);
        $this->assertEquals(
            [
                'page' => [
                    'uuid' => $page1->getUuid(),
                    'path' => $page1->getResourceSegment(),
                ],
                'suffix' => '/articles/test-article',
                'path' => '/page-2/page-1/articles/test-article',
            ],
            $response['routePath']
        );
    }

    public function testPostPageTreeRouteGenerateRemovePage()
    {
        $page = $this->createPage('Test Page', '/test-page');

        $routePathData = [
            'page' => [
                'uuid' => $page->getUuid(),
                'path' => $page->getResourceSegment(),
            ],
            'suffix' => '/articles/test-article',
            'path' => '/test-page/articles/test-article',
        ];

        $article = $this->postPageTreeRoute($routePathData);

        $this->documentManager->remove($page);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $this->client->jsonRequest('GET', '/api/articles/' . $article['id'] . '?locale=de');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Test Article', $response['title']);
        $this->assertEquals('/test-page/articles/test-article', $response['route']);
        $this->assertEquals(
            [
                'page' => [
                    'uuid' => $page->getUuid(),
                    'path' => $page->getResourceSegment(),
                ],
                'suffix' => '/articles/test-article',
                'path' => '/test-page/articles/test-article',
            ],
            $response['routePath']
        );
    }

    protected function publish($id)
    {
        $this->client->jsonRequest('PUT', '/api/articles/' . $id . '?action=publish&locale=de', $this->get($id));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testOrderPages()
    {
        $this->markTestSkipped();

        $article = $this->post();
        $pages = [
            $this->postPage($article, 'Page 1'),
            $this->postPage($article, 'Page 2'),
            $this->postPage($article, 'Page 3'),
            $this->postPage($article, 'Page 4'),
        ];
        $this->publish($article['id']);

        $pages[] = $this->postPage($article, 'Page 5');

        $expectedPages = [$pages[4]['id'], $pages[3]['id'], $pages[2]['id'], $pages[1]['id'], $pages[0]['id']];

        $this->client->jsonRequest(
            'POST',
            '/api/articles/' . $article['id'] . '?action=order&locale=de',
            ['pages' => $expectedPages]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = $this->publish($article['id']);

        $responsePages = $response['children'];
        for ($i = 0; $i < \count($expectedPages); ++$i) {
            $this->assertEquals($expectedPages[$i], $responsePages[$i]['id']);
            $this->assertEquals($i + 2, $responsePages[$i]['pageNumber']);
            $this->assertEquals(
                $article['route'] . '/page-' . $responsePages[$i]['pageNumber'],
                $responsePages[$i]['route']
            );

            $route = $this->findRoute($responsePages[$i]['route'], 'de');
            $this->assertTrue(\is_subclass_of($route->getEntityClass(), ArticlePageDocument::class) || ArticlePageDocument::class === $route->getEntityClass());
            $this->assertEquals($responsePages[$i]['id'], $route->getEntityId());
            $this->assertEquals('de', $route->getLocale());
            $this->assertFalse($route->isHistory());
            $this->assertNull($route->getTarget());
        }
    }

    private function postPageTreeRoute($routePathData, $title = 'Test Article')
    {
        $this->client->jsonRequest(
            'POST',
            '/api/articles?locale=de&action=publish',
            [
                'title' => $title,
                'template' => 'page_tree_route',
                'routePath' => $routePathData,
                'authored' => \date('c', \strtotime('2016-01-01')),
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return \json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Create a media.
     *
     * @return Media
     */
    private function createMedia()
    {
        $collection = new Collection();
        $collection->setType($this->getEntityManager()->getReference(CollectionType::class, 1));
        $media = new Media();
        $media->setType($this->getEntityManager()->getReference(MediaType::class, 1));
        $media->setCollection($collection);

        $this->getEntityManager()->persist($collection);
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();

        return $media;
    }

    /**
     * Create a category.
     *
     * @return Category
     */
    private function createCategory()
    {
        $entityManager = $this->getEntityManager();

        $category = new Category();
        $category->setDefaultLocale('de');
        $entityManager->persist($category);
        $entityManager->flush();

        return $category;
    }

    /**
     * Create a tag.
     *
     * @return Tag
     */
    private function createTag()
    {
        $entityManager = $this->getEntityManager();

        $tag = new Tag();
        $tag->setName('Test');
        $entityManager->persist($tag);
        $entityManager->flush();

        return $tag;
    }

    /**
     * Create a contact.
     *
     * @return Contact
     */
    private function createContact()
    {
        $entityManager = $this->getEntityManager();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $entityManager->persist($contact);
        $entityManager->flush();

        return $contact;
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
    private function createPage($title, $resourceSegment, $locale = 'de')
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

    private function purgeIndex()
    {
        /** @var IndexerInterface $indexer */
        $indexer = $this->getContainer()->get('sulu_article.elastic_search.article_indexer');
        $indexer->clear();
    }

    private function flush()
    {
        /** @var IndexerInterface $indexer */
        $indexer = $this->getContainer()->get('sulu_article.elastic_search.article_indexer');
        $indexer->flush();
    }

    private function findViewDocument($uuid, $locale)
    {
        /** @var Manager $manager */
        $manager = $this->getContainer()->get('es.manager.default');

        return $manager->find(
            $this->getContainer()->getParameter('sulu_article.view_document.article.class'),
            $this->getViewDocumentId($uuid, $locale)
        );
    }

    /**
     * @param string $path
     * @param string $locale
     *
     * @return RouteInterface
     */
    private function findRoute($path, $locale)
    {
        return $this->getContainer()->get('sulu.repository.route')->findByPath($path, $locale);
    }

    private function getRoute($title)
    {
        return '/articles/' . Urlizer::urlize($title);
    }
}
