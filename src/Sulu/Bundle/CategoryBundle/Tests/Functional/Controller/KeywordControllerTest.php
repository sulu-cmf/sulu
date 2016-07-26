<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Entity\Keyword;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class KeywordControllerTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Category
     */
    private $category1;

    /**
     * @var Category
     */
    private $category2;

    public function setUp()
    {
        $this->entityManager = $this->getEntityManager();

        $this->initOrm();
    }

    public function initOrm()
    {
        $this->purgeDatabase();

        $this->category1 = new Category();
        $this->category1->setKey('1');
        $this->category1->setDefaultLocale('de');
        $categoryTranslation1 = new CategoryTranslation();
        $categoryTranslation1->setCategory($this->category1);
        $categoryTranslation1->setTranslation('test-1');
        $categoryTranslation1->setLocale('de');
        $this->category1->addTranslation($categoryTranslation1);

        $this->category2 = new Category();
        $this->category2->setKey('2');
        $this->category2->setDefaultLocale('de');
        $categoryTranslation2 = new CategoryTranslation();
        $categoryTranslation2->setCategory($this->category2);
        $categoryTranslation2->setTranslation('test-2');
        $categoryTranslation2->setLocale('de');
        $this->category2->addTranslation($categoryTranslation2);

        $this->entityManager->persist($this->category1);
        $this->entityManager->persist($this->category2);
        $this->entityManager->persist($categoryTranslation1);
        $this->entityManager->persist($categoryTranslation2);
        $this->entityManager->flush();
    }

    public function testPost($keyword = 'Test', $locale = 'de', $categoryId = null)
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/categories/' . ($categoryId ?: $this->category1->getId()) . '/keywords',
            ['locale' => $locale, 'keyword' => $keyword]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertNotNull($result['id']);

        return $result;
    }

    public function testPostExisting($keyword = 'Test', $locale = 'de')
    {
        $first = $this->testPost($keyword, $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/categories/' . $this->category1->getId() . '/keywords',
            ['locale' => $locale, 'keyword' => $keyword]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($first['id'], $result['id']);
    }

    public function testPostExistingOtherCategory($keyword = 'Test', $locale = 'de')
    {
        $first = $this->testPost($keyword, $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/categories/' . $this->category2->getId() . '/keywords',
            ['locale' => $locale, 'keyword' => $keyword]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($first['id'], $result['id']);

        return $result;
    }

    public function testPostExistingOtherkeyword($keyword = 'Test-1', $locale = 'de')
    {
        $first = $this->testPost('Test', $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/categories/' . $this->category2->getId() . '/keywords',
            ['locale' => $locale, 'keyword' => $keyword]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertNotEquals($first['id'], $result['id']);
        $this->assertNotNull($result['id']);
    }

    public function testPut($keyword = 'Test-1', $locale = 'de')
    {
        $first = $this->testPost('Test', $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'],
            ['keyword' => $keyword]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($first['id'], $result['id']);
    }

    public function testPutForceOverwrite($keyword = 'Test-1', $locale = 'de')
    {
        $first = $this->testPost('Test', $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'] . '?force=overwrite',
            ['keyword' => $keyword]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($first['id'], $result['id']);
    }

    public function testPutForceDetach($keyword = 'Test-1', $locale = 'de')
    {
        $first = $this->testPost('Test', $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'] . '?force=detach',
            ['keyword' => $keyword]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertNotNull($result['id']);
        $this->assertNotEquals($first['id'], $result['id']);

        // old entity should be deleted
        $entity = $this->entityManager->find(Keyword::class, $first['id']);
        $this->assertNull($entity);
    }

    public function testPutMultipleCategories($keyword = 'Test-1', $locale = 'de')
    {
        $first = $this->testPostExistingOtherCategory('Test', $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'],
            ['keyword' => $keyword]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(409, $client->getResponse());
        $this->assertEquals(2002, $result['code']);
    }

    public function testPutMultipleCategoriesForceOverwrite($keyword = 'Test-1', $locale = 'de')
    {
        $first = $this->testPostExistingOtherCategory('Test', $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'] . '?force=overwrite',
            ['keyword' => $keyword]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($first['id'], $result['id']);
    }

    public function testPutMultipleCategoriesForceDetach($keyword = 'Test-1', $locale = 'de')
    {
        $first = $this->testPostExistingOtherCategory('Test', $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id'] . '?force=detach',
            ['keyword' => $keyword]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($keyword, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertNotNull($result['id']);
        $this->assertNotEquals($first['id'], $result['id']);

        $entity = $this->entityManager->find(Keyword::class, $first['id']);
        $this->assertEquals($first['keyword'], $entity->getKeyword());
    }

    public function testPutSamekeyword($keyword1 = 'Test-1', $keyword2 = 'Test-2', $locale = 'de')
    {
        $data1 = $this->testPost($keyword1, $locale, $this->category1->getId());
        $data2 = $this->testPost($keyword2, $locale, $this->category2->getId());

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category2->getId() . '/keywords/' . $data2['id'],
            ['keyword' => $data1['keyword']]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(409, $client->getResponse());
        $this->assertEquals(2001, $result['code']);
    }

    public function testPutSamekeywordMerge($keyword1 = 'Test-1', $keyword2 = 'Test-2', $locale = 'de')
    {
        $data1 = $this->testPost($keyword1, $locale, $this->category1->getId());
        $data2 = $this->testPost($keyword2, $locale, $this->category2->getId());

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category2->getId() . '/keywords/' . $data2['id'] . '?force=merge',
            ['keyword' => $data1['keyword']]
        );

        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($keyword1, $result['keyword']);
        $this->assertEquals($locale, $result['locale']);
        $this->assertEquals($data1['id'], $result['id']);
    }

    public function testDelete($keyword = 'Test', $locale = 'de')
    {
        $first = $this->testPost($keyword, $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id']
        );

        $this->assertHttpStatusCode(204, $client->getResponse());
        $this->assertNull($this->entityManager->find(Keyword::class, $first['id']));
    }

    public function testDeleteMultipleCategories($keyword = 'Test', $locale = 'de')
    {
        $first = $this->testPostExistingOtherCategory($keyword, $locale);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/categories/' . $this->category1->getId() . '/keywords/' . $first['id']
        );

        $this->assertHttpStatusCode(204, $client->getResponse());
        $this->assertNotNull($this->entityManager->find(Keyword::class, $first['id']));
    }
}
