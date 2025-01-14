<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class KeywordControllerTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CategoryInterface
     */
    private $category1;

    /**
     * @var CategoryInterface
     */
    private $category2;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->entityManager = $this->getEntityManager();

        $this->initOrm();
    }

    public function initOrm(): void
    {
        $this->purgeDatabase();

        $this->category1 = $this->getContainer()->get('sulu.repository.category')->createNew();
        $this->category1->setKey('1');
        $this->category1->setDefaultLocale('de');
        $categoryTranslation1 = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTranslation1->setCategory($this->category1);
        $categoryTranslation1->setTranslation('test-1');
        $categoryTranslation1->setLocale('de');
        $this->category1->addTranslation($categoryTranslation1);

        $this->category2 = $this->getContainer()->get('sulu.repository.category')->createNew();
        $this->category2->setKey('2');
        $this->category2->setDefaultLocale('de');
        $categoryTranslation2 = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTranslation2->setCategory($this->category2);
        $categoryTranslation2->setTranslation('test-2');
        $categoryTranslation2->setLocale('de');
        $this->category2->addTranslation($categoryTranslation2);

        $this->entityManager->persist($this->category1);
        $this->entityManager->persist($this->category2);
        $this->entityManager->persist($categoryTranslation1);
        $this->entityManager->persist($categoryTranslation2);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function testCget(): void
    {
        $this->testPost('keyword1', 'de', $this->category1->getId());
        $this->testPost('keyword2', 'de', $this->category1->getId());

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $this->category1->getId() . '/keywords?locale=de'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(2, $response->total);

        \usort($response->_embedded->category_keywords, function($key1, $key2) {
            return $key1->id <=> $key2->id;
        });

        $this->assertEquals('keyword1', $response->_embedded->category_keywords[0]->keyword);
        $this->assertEquals('keyword2', $response->_embedded->category_keywords[1]->keyword);
    }

    public function testGet(): void
    {
        $keyword = $this->testPost('keyword1', 'de', $this->category1->getId());

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $keyword['id'] . '?locale=de'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('keyword1', $response->keyword);
    }

    public function testGetNotExisting(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $this->category1->getId() . '/keywords/1?locale=de'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPost($keyword = 'Test', $locale = 'de', $categoryId = null)
    {
        $this->client->jsonRequest(
            'POST',
            '/api/categories/' . ($categoryId ?: $this->category1->getId()) . '/keywords',
            ['locale' => $locale, 'keyword' => $keyword]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertNotNull($result['id']);

        return $result;
    }

    public function testPostExisting($keyword = 'Test', $locale = 'de'): void
    {
        $first = $this->testPost($keyword, $locale);

        $this->client->jsonRequest(
            'POST',
            '/api/categories/' . $this->category1->getId() . '/keywords',
            ['locale' => $locale, 'keyword' => $keyword]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($first['id'], $result['id']);
    }

    public function testPostWithNotExistingCategoryTranslation(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/categories/' . $this->category1->getId() . '/keywords',
            ['locale' => 'it', 'keyword' => 'my-keyword']
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('my-keyword', $result['keyword']);
        $this->assertEquals('it', $result['locale']);
        $this->assertNotNull($result['id']);
    }

    public function testPostExistingOtherCategory($keyword = 'Test', $locale = 'de')
    {
        $first = $this->testPost($keyword, $locale);

        $this->client->jsonRequest(
            'POST',
            '/api/categories/' . $this->category2->getId() . '/keywords',
            ['locale' => $locale, 'keyword' => $keyword]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($first['id'], $result['id']);

        return $result;
    }

    public function testPostExistingOtherkeyword($keyword = 'Test-1', $locale = 'de'): void
    {
        $first = $this->testPost('Test', $locale);

        $this->client->jsonRequest(
            'POST',
            '/api/categories/' . $this->category2->getId() . '/keywords',
            ['locale' => $locale, 'keyword' => $keyword]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertNotEquals($first['id'], $result['id']);
        $this->assertNotNull($result['id']);
    }

    public function testPut($keyword = 'Test-1', $locale = 'de'): void
    {
        $first = $this->testPost('Test', $locale);

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'],
            ['keyword' => $keyword]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($first['id'], $result['id']);
    }

    public function testPutForceOverwrite($keyword = 'Test-1', $locale = 'de'): void
    {
        $first = $this->testPost('Test', $locale);

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'] . '?force=overwrite',
            ['keyword' => $keyword]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($first['id'], $result['id']);
    }

    public function testPutForceDetach($keyword = 'Test-1', $locale = 'de'): void
    {
        $first = $this->testPost('Test', $locale);

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'] . '?force=detach',
            ['keyword' => $keyword]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertNotNull($result['id']);
        $this->assertNotEquals($first['id'], $result['id']);

        // old entity should be deleted
        $entity = $this->entityManager->find(KeywordInterface::class, $first['id']);
        $this->assertNull($entity);
    }

    public function testPutMultipleCategories($keyword = 'Test-1', $locale = 'de'): void
    {
        $first = $this->testPostExistingOtherCategory('Test', $locale);

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'],
            ['keyword' => $keyword]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertEquals(2002, $result['code']);
    }

    public function testPutMultipleCategoriesForceOverwrite($keyword = 'Test-1', $locale = 'de'): void
    {
        $first = $this->testPostExistingOtherCategory('Test', $locale);

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'] . '?force=overwrite',
            ['keyword' => $keyword]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($first['id'], $result['id']);
    }

    public function testPutMultipleCategoriesForceDetach($keyword = 'Test-1', $locale = 'de'): void
    {
        $first = $this->testPostExistingOtherCategory('Test', $locale);

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'] . '?force=detach',
            ['keyword' => $keyword]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertNotNull($result['id']);
        $this->assertNotEquals($first['id'], $result['id']);

        $entity = $this->entityManager->find(KeywordInterface::class, $first['id']);
        $this->assertEquals($first['keyword'], $entity->getKeyword());
    }

    public function testPutSamekeyword($keyword1 = 'Test-1', $keyword2 = 'Test-2', $locale = 'de'): void
    {
        $data1 = $this->testPost($keyword1, $locale, $this->category1->getId());
        $data2 = $this->testPost($keyword2, $locale, $this->category2->getId());

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $this->category2->getId() . '/keywords/' . $data2['id'],
            ['keyword' => $data1['keyword']]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertEquals(2001, $result['code']);
    }

    public function testPutSamekeywordMerge($keyword1 = 'Test-1', $keyword2 = 'Test-2', $locale = 'de'): void
    {
        $data1 = $this->testPost($keyword1, $locale, $this->category1->getId());
        $data2 = $this->testPost($keyword2, $locale, $this->category2->getId());

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $this->category2->getId() . '/keywords/' . $data2['id'] . '?force=merge',
            ['keyword' => $data1['keyword']]
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($keyword1, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($data1['id'], $result['id']);
    }

    public function testDelete($keyword = 'Test', $locale = 'de'): void
    {
        $first = $this->testPost($keyword, $locale);

        $this->client->jsonRequest(
            'DELETE',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id']
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertNull($this->entityManager->find(KeywordInterface::class, $first['id']));
    }

    public function testDeleteMultipleCategories($keyword = 'Test', $locale = 'de'): void
    {
        $first = $this->testPostExistingOtherCategory($keyword, $locale);

        $this->client->jsonRequest(
            'DELETE',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id']
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertNotNull($this->entityManager->find(KeywordInterface::class, $first['id']));
    }
}
