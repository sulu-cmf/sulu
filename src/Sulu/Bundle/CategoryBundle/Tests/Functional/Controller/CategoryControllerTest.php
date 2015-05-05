<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Functional\Controller;

use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMeta;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CategoryControllerTest extends SuluTestCase
{
    /**
     * @var Category
     */
    private $category1;

    /**
     * @var Category
     */
    private $category2;

    /**
     * @var Category
     */
    private $category3;

    /**
     * @var Category
     */
    private $category4;

    /**
     * @var Category
     */
    private $meta1;

    public function setUp()
    {
        $this->em = $this->db('ORM')->getOm();

        $this->initOrm();
    }

    public function initOrm()
    {
        $this->db('ORM')->purgeDatabase();
        /* First Category
        -------------------------------------*/
        $category = new Category();
        $category->setKey('first-category-key');

        // name for first category
        $categoryTrans = new CategoryTranslation();
        $categoryTrans->setLocale('en');
        $categoryTrans->setTranslation('First Category');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);
        $this->category1 = $category;

        // meta for first category
        $categoryMeta = new CategoryMeta();
        $categoryMeta->setLocale('en');
        $categoryMeta->setKey('description');
        $categoryMeta->setValue('Description of Category');
        $categoryMeta->setCategory($category);
        $category->addMeta($categoryMeta);
        $this->meta1 = $categoryMeta;

        $this->em->persist($category);

        /* Second Category
        -------------------------------------*/
        $category2 = new Category();
        $category2->setKey('second-category-key');
        $this->category2 = $category2;

        // name for second category
        $categoryTrans2 = new CategoryTranslation();
        $categoryTrans2->setLocale('de');
        $categoryTrans2->setTranslation('Second Category');
        $categoryTrans2->setCategory($category2);
        $category2->addTranslation($categoryTrans2);

        // meta for second category
        $categoryMeta2 = new CategoryMeta();
        $categoryMeta2->setLocale('de');
        $categoryMeta2->setKey('description');
        $categoryMeta2->setValue('Description of second Category');
        $categoryMeta2->setCategory($category2);
        $category2->addMeta($categoryMeta2);

        // meta without locale for second category
        $categoryMeta3 = new CategoryMeta();
        $categoryMeta3->setKey('noLocaleKey');
        $categoryMeta3->setValue('noLocaleValue');
        $categoryMeta3->setCategory($category2);
        $category2->addMeta($categoryMeta3);

        $this->em->persist($category2);

        /* Third Category (child of first)
        -------------------------------------*/
        $category3 = new Category();
        $category3->setParent($category);
        $this->category3 = $category3;

        // name for third category
        $categoryTrans3 = new CategoryTranslation();
        $categoryTrans3->setLocale('en');
        $categoryTrans3->setTranslation('Third Category');
        $categoryTrans3->setCategory($category3);
        $category3->addTranslation($categoryTrans3);

        // meta for third category
        $categoryMeta4 = new CategoryMeta();
        $categoryMeta4->setLocale('de');
        $categoryMeta4->setKey('another');
        $categoryMeta4->setValue('Description of third Category');
        $categoryMeta4->setCategory($category3);
        $category3->addMeta($categoryMeta4);

        $this->em->persist($category3);

        /* Fourth Category (child of third)
        -------------------------------------*/
        $category4 = new Category();
        $category4->setParent($category3);
        $this->category4 = $category4;

        // name for fourth category
        $categoryTrans4 = new CategoryTranslation();
        $categoryTrans4->setLocale('en');
        $categoryTrans4->setTranslation('Fourth Category');
        $categoryTrans4->setCategory($category4);
        $category4->addTranslation($categoryTrans4);

        // meta for fourth category
        $categoryMeta5 = new CategoryMeta();
        $categoryMeta5->setLocale('de');
        $categoryMeta5->setKey('anotherkey');
        $categoryMeta5->setValue('Description of fourth Category');
        $categoryMeta5->setCategory($category4);
        $category4->addMeta($categoryMeta5);

        $this->em->persist($category4);

        $this->em->flush();
    }

    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId()
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('First Category', $response->name);
        $this->assertEquals('first-category-key', $response->key);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals($this->category1->getId(), $response->id);
        $this->assertEquals(1, count($response->meta));
        $this->assertEquals('description', $response->meta[0]->key);
        $this->assertEquals('Description of Category', $response->meta[0]->value);
    }

    public function testByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/101230'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertTrue(isset($response->message));
    }

    public function testCGet()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $categories = $response->_embedded->categories;

        $this->assertEquals('First Category', $categories[0]->name);
        $this->assertEquals('Third Category', $categories[0]->children[0]->name);
        $this->assertEquals('Fourth Category', $categories[0]->children[0]->children[0]->name);
        $this->assertEquals('second-category-key', $categories[1]->key);

        $this->assertCount(2, $categories);
        $this->assertCount(1, $categories[0]->children);
        $this->assertCount(1, $categories[0]->children[0]->children);
    }

    public function testCGetWithParent()
    {
        $this->markTestSkipped('Fix dme: https://github.com/sulu-cmf/sulu/issues/355');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?flat=true&parent=' . $this->category1->getId()
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->_embedded->categories));
        $this->assertEquals($this->category3->getId(), $response->_embedded->categories[0]->id);
        $this->assertEquals('Third Category', $response->_embedded->categories[0]->name);
    }

    public function testCGetWithDepth()
    {
        $this->markTestSkipped('Fix dme: https://github.com/sulu-cmf/sulu/issues/355');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?flat=true&depth=1'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->_embedded->categories));
        $this->assertEquals($this->category3->getId(), $response->_embedded->categories[0]->id);
        $this->assertEquals('Third Category', $response->_embedded->categories[0]->name);
    }

    public function testCGetWithSorting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?flat=true&sortBy=depth&sortOrder=desc'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(4, count($response->_embedded->categories));
        $this->assertEquals($this->category4->getId(), $response->_embedded->categories[0]->id);
        $this->assertEquals('Fourth Category', $response->_embedded->categories[0]->name);
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/categories',
            array(
                'name' => 'New Category',
                'key' => 'new-category-key',
                'meta' => array(
                    array(
                        'key' => 'myKey',
                        'value' => 'myValue'
                    ),
                    array(
                        'key' => 'anotherKey',
                        'value' => 'should not be visible due to locale',
                        'locale' => 'de-ch'
                    )
                )
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('New Category', $response->name);
        $this->assertEquals('new-category-key', $response->key);
        $this->assertEquals(1, count($response->meta));
        $this->assertEquals('myKey', $response->meta[0]->key);
        $this->assertEquals('myValue', $response->meta[0]->value);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/' . $response->id
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('New Category', $response->name);
        $this->assertEquals('new-category-key', $response->key);
        $this->assertEquals(1, count($response->meta));
        $this->assertEquals('myKey', $response->meta[0]->key);
        $this->assertEquals('myValue', $response->meta[0]->value);
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId(),
            array(
                'name' => 'Modified Category',
                'key' => 'modified-category-key',
                'meta' => array(
                    array(
                        'id' => $this->meta1->getId(),
                        'key' => 'modifiedKey',
                        'value' => 'This meta got overriden',
                        'locale' => null
                    ),
                    array(
                        'key' => 'newMeta',
                        'value' => 'This meta got added'
                    ),
                )
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Modified Category', $response->name);
        $this->assertEquals('modified-category-key', $response->key);
        $this->assertEquals(2, count($response->meta));
        $this->assertTrue('modifiedKey' === $response->meta[0]->key || 'newMeta' === $response->meta[0]->key);
        $this->assertTrue('This meta got overriden' === $response->meta[0]->value || 'This meta got added' === $response->meta[0]->value);
        $this->assertTrue('modifiedKey' === $response->meta[1]->key || 'newMeta' === $response->meta[1]->key);
        $this->assertTrue('This meta got overriden' === $response->meta[1]->value || 'This meta got added' === $response->meta[1]->value);

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId()
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Modified Category', $response->name);
        $this->assertEquals('modified-category-key', $response->key);
        $this->assertEquals(2, count($response->meta));
        $this->assertTrue('modifiedKey' === $response->meta[0]->key || 'newMeta' === $response->meta[0]->key);
        $this->assertTrue('This meta got overriden' === $response->meta[0]->value || 'This meta got added' === $response->meta[0]->value);
        $this->assertTrue('modifiedKey' === $response->meta[1]->key || 'newMeta' === $response->meta[1]->key);
        $this->assertTrue('This meta got overriden' === $response->meta[1]->value || 'This meta got added' === $response->meta[1]->value);
    }

    public function testPutWithDifferentLocale()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '?locale=cn',
            array(
                'name' => 'Imagine this is chinese'
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Imagine this is chinese', $response->name);

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId(). '?locale=cn'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Imagine this is chinese', $response->name);

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId()
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('First Category', $response->name);
    }

    public function testPutWithMissingArgument()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId(),
            array(
                'meta' => array(
                    array(
                        'key' => 'newMeta',
                        'value' => 'This meta got added'
                    ),
                )
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testPatch()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/categories/' . $this->category1->getId(),
            array(
                'name' => 'Name changed through patch'
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals($this->category1->getId(), $response->id);
        $this->assertEquals('Name changed through patch', $response->name);

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId()
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals($this->category1->getId(), $response->id);
        $this->assertEquals('Name changed through patch', $response->name);
    }

    public function testPatchWithNotUniqueKey()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/categories/' . $this->category3->getId(),
            array(
                'key' => 'first-category-key'
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->code);
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/categories/' . $this->category2->getId()
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories' . $this->category2->getId()
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteOfParent()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/categories/' . $this->category1->getId()
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories'
        );

        //$this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->_embedded->categories));
        $this->assertEquals($this->category2->getId(), $response->_embedded->categories[0]->id);
    }

    public function testGetChildren()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/first-category-key/children'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $response->_embedded->categories);
        $this->assertEquals($this->category3->getId(), $response->_embedded->categories[0]->id);
    }

    public function testGetChildrenAsList()
    {
        $this->markTestSkipped('Fix dme: https://github.com/sulu-cmf/sulu/issues/355');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/first-category-key/children?flat=true&sortBy=depth&sortOrder=desc'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(2, count($response->_embedded->categories));
        $this->assertEquals($this->category4->getId(), $response->_embedded->categories[0]->id);
        $this->assertEquals($this->category3->getId(), $response->_embedded->categories[1]->id);
    }
}
