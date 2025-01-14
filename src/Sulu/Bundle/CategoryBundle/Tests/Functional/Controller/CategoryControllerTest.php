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
use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class CategoryControllerTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var ObjectRepository<ActivityInterface>
     */
    private $activityRepository;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->activityRepository = $this->em->getRepository(ActivityInterface::class);
    }

    public function testGetById(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $categoryMeta1 = $this->createCategoryMeta($category1, 'en', 'description', 'Description of Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('First Category', $response->name);
        $this->assertEquals('first-category-key', $response->key);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals($category1->getId(), $response->id);
        $this->assertEquals(1, \count($response->meta));
        $this->assertEquals('description', $response->meta[0]->key);
        $this->assertEquals('Description of Category', $response->meta[0]->value);
    }

    public function testGetByIdChild(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en', $category1);
        $this->createCategoryTranslation($category2, 'en', 'Second Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category2->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('Second Category', $response->name);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals($category2->getId(), $response->id);
        $this->assertEquals($category1->getId(), $response->parentId);
    }

    public function testGetByIdWithNoLocale(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category1->getId()
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testGetByIdNotExisting(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/categories/101230?locale=en'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGetByIdLocaleFallback(): void
    {
        $category = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category->setDefaultLocale('en');

        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale('en');
        $categoryTrans->setTranslation('EN');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale('en_us');
        $categoryTrans->setTranslation('EN-US');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        $this->em->persist($category);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category->getId() . '?locale=de'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('en', $response->defaultLocale);
        $this->assertEquals('EN', $response->name);

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category->getId() . '?locale=en_us'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('en_us', $response->locale);
        $this->assertEquals('en', $response->defaultLocale);
        $this->assertEquals('EN-US', $response->name);
    }

    public function testCGet(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');
        $category5 = $this->createCategory(null, 'de');
        $this->createCategoryTranslation($category5, 'de', 'Fünfte Kategorie');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertCount(3, $categories);
        $this->assertCount(1, $categories[0]->children);
        $this->assertCount(1, $categories[0]->children[0]->children);

        $this->assertEquals('First Category', $categories[0]->name);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('en', $categories[0]->locale);
        $this->assertEquals('first-category-key', $categories[0]->key);

        $this->assertEquals('second-category-key', $categories[1]->key);
        $this->assertEquals('en', $categories[1]->defaultLocale);
        $this->assertEquals('Third Category', $categories[0]->children[0]->name);
        $this->assertEquals('Fourth Category', $categories[0]->children[0]->children[0]->name);
        $this->assertEquals('Fünfte Kategorie', $categories[2]->name);
        $this->assertEquals('de', $categories[2]->locale);
        $this->assertEquals('de', $categories[2]->ghostLocale);
    }

    public function testCGetByIds(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&ids=' . $category3->getId() . ',' . $category4->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;

        $this->assertCount(2, $categories);

        $this->assertEquals('Third Category', $categories[0]->name);
        $this->assertEquals('Fourth Category', $categories[1]->name);
    }

    public function testCGetByEmptyIds(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&ids='
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;

        $this->assertCount(0, $categories);
    }

    public function testCGetFlat(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $this->createCategoryTranslation($category1, 'de', 'Erste Kategorie');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $this->createCategoryTranslation($category2, 'de', 'Zweite Kategorie');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'de');
        $this->createCategoryTranslation($category4, 'de', 'Vierte Kategorie');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertCount(3, $categories);
        $this->assertEquals(3, $response->total);

        $this->assertEquals('First Category', $categories[0]->name);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('en', $categories[0]->locale);
        $this->assertEquals('first-category-key', $categories[0]->key);
        $this->assertTrue($categories[0]->hasChildren);
        $this->assertNull($categories[0]->ghostLocale);

        $this->assertEquals('second-category-key', $categories[1]->key);
        $this->assertEquals('en', $categories[1]->defaultLocale);
        $this->assertFalse($categories[1]->hasChildren);
        $this->assertNull($categories[1]->ghostLocale);

        $this->assertEquals('Vierte Kategorie', $categories[2]->name);
        $this->assertEquals('de', $categories[2]->locale);
        $this->assertEquals('de', $categories[2]->ghostLocale);
    }

    public function testCGetFlatWithSelectedIds(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $this->createCategoryTranslation($category1, 'de', 'Erste Kategorie');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $this->createCategoryTranslation($category2, 'de', 'Zweite Kategorie');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Third Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&selectedIds=' . $category3->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertCount(2, $categories);
        $this->assertEquals(3, $response->total);

        $category1 = $categories[0];
        $this->assertEquals('First Category', $category1->name);
        $this->assertEquals('en', $category1->defaultLocale);
        $this->assertEquals('en', $category1->locale);
        $this->assertEquals('first-category-key', $category1->key);
        $this->assertTrue($category1->hasChildren);

        $category2 = $categories[1];
        $this->assertEquals('second-category-key', $category2->key);
        $this->assertFalse($category2->hasChildren);

        $category3 = $category1->_embedded->categories[0];
        $this->assertEquals('Third Category', $category3->name);
        $this->assertTrue($category3->hasChildren);
        $this->assertFalse(\property_exists($category3, '_embedded'));
    }

    public function testCGetFlatWithExpandedIds(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $this->createCategoryTranslation($category1, 'de', 'Erste Kategorie');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $this->createCategoryTranslation($category2, 'de', 'Zweite Kategorie');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&expandedIds=' . $category3->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertCount(2, $categories);
        $this->assertEquals(4, $response->total);

        $category1 = $categories[0];
        $this->assertEquals('First Category', $category1->name);
        $this->assertEquals('en', $category1->defaultLocale);
        $this->assertEquals('en', $category1->locale);
        $this->assertEquals('first-category-key', $category1->key);
        $this->assertTrue($category1->hasChildren);

        $category2 = $categories[1];
        $this->assertEquals('second-category-key', $category2->key);
        $this->assertFalse($category2->hasChildren);

        $category3 = $category1->_embedded->categories[0];
        $this->assertEquals('Third Category', $category3->name);
        $this->assertTrue($category3->hasChildren);

        $category4 = $category3->_embedded->categories[0];
        $this->assertEquals('Fourth Category', $category4->name);
        $this->assertFalse($category4->hasChildren);
    }

    public function testCGetFlatWithExpandIdsSameLevel(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $this->createCategoryTranslation($category1, 'de', 'Erste Kategorie');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $this->createCategoryTranslation($category2, 'de', 'Zweite Kategorie');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&expandedIds=' . $category2->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertCount(2, $categories);
        $this->assertEquals(2, $response->total);

        $this->assertEquals('First Category', $categories[0]->name);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('en', $categories[0]->locale);
        $this->assertEquals('first-category-key', $categories[0]->key);
        $this->assertTrue($categories[0]->hasChildren);

        $this->assertEquals('second-category-key', $categories[1]->key);
        $this->assertEquals('en', $categories[1]->defaultLocale);
        $this->assertFalse($categories[1]->hasChildren);
    }

    public function testCGetFlatWithSearch(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&search=Third&searchFields=name'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $categories = $response->_embedded->categories;

        $this->assertCount(1, $categories);
        $this->assertEquals(1, $response->total);

        $this->assertEquals('Third Category', $categories[0]->name);
        $this->assertTrue($categories[0]->hasChildren);
    }

    public function testCGetWithNoLocale(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/categories'
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testCGetFlatWithNoLocale(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/categories?flat=true'
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testCGetWithRoot(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&rootKey=' . $category1->getKey()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertCount(1, $categories);
        $this->assertEquals($category3->getId(), $categories[0]->id);
        $this->assertEquals($category4->getId(), $categories[0]->children[0]->id);
    }

    public function testCGetFlatWithRoot(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=' . $category1->getKey()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertEquals(1, $response->total);
        $this->assertEquals($category3->getId(), $categories[0]->id);
        $this->assertTrue($categories[0]->hasChildren);
    }

    public function testCGetFlatWithRootAndSearch(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        // search for existing third category

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=' . $category1->getKey() .
            '&searchFields=name&search=Third'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertEquals(1, $response->total);
        $this->assertEquals($category3->getId(), $categories[0]->id);
        $this->assertTrue($categories[0]->hasChildren);

        // search for the root category => should be excluded also!

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=' . $category1->getKey() .
            '&searchFields=name&search=First'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;

        $this->assertEquals(0, \count($categories));

        // search for not existing category

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=' . $category1->getKey() .
            '&searchFields=name&search=XXX'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;

        $this->assertEquals(0, \count($categories));
    }

    public function testCGetFlatWithRootAndExpandIds(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $category3 = $this->createCategory('third-category-key', 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory('fourth-category-key', 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=' . $category1->getKey()
            . '&expandedIds=' . $category4->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertEquals(2, $response->total);

        $this->assertEquals($category3->getId(), $categories[0]->id);
        $this->assertTrue($categories[0]->hasChildren);

        $this->assertEquals('Fourth Category', $categories[0]->_embedded->categories[0]->name);
        $this->assertFalse($categories[0]->_embedded->categories[0]->hasChildren);
    }

    public function testCGetFlatWithRootAndWrongExpandIds(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'de', 'Erste Kategorie');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'de', 'Zweite Kategorie');
        $category3 = $this->createCategory('third-category-key', 'en', $category1);
        $this->createCategoryTranslation($category3, 'de', 'Dritte Kategorie');
        $category4 = $this->createCategory('fourth-category-key', 'en', $category3);
        $this->createCategoryTranslation($category4, 'de', 'Vierte Kategorie');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=' . $category1->getKey()
            . '&expandedIds=' . $category2->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertEquals(1, $response->total);
        $this->assertEquals($category3->getId(), $categories[0]->id);
        $this->assertTrue($categories[0]->hasChildren);
    }

    public function testCGetWithNotExistingRoot(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&rootKey=101'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCGetFlatWithNotExistingRoot(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=101'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCGetFlatWithParent(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'de', 'Erste Kategorie');
        $category2 = $this->createCategory('second-category-key', 'en', $category1);
        $this->createCategoryTranslation($category2, 'de', 'Zweite Kategorie');
        $category3 = $this->createCategory('third-category-key', 'en', $category2);
        $this->createCategoryTranslation($category3, 'de', 'Dritte Kategorie');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=en&flat=true&parentId=' . $category1->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertEquals(1, $response->total);
        $this->assertEquals($category2->getId(), $categories[0]->id);
        $this->assertTrue($categories[0]->hasChildren);
    }

    public function testCGetFlatWithSorting(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'de', 'Erste Kategorie');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'de', 'Zweite Kategorie');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=de&flat=true&sortBy=name&sortOrder=desc'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $categories = $response->_embedded->categories;

        $this->assertCount(2, $categories);

        $this->assertEquals('Zweite Kategorie', $categories[0]->name);
        $this->assertEquals('Erste Kategorie', $categories[1]->name);
    }

    public function testCGetFlatWithDefaultSorting(): void
    {
        $category1 = $this->createCategory('B', 'en');
        $this->createCategoryTranslation($category1, 'de', 'B');
        $category2 = $this->createCategory('A', 'en');
        $this->createCategoryTranslation($category2, 'de', 'A');
        $category3 = $this->createCategory('C', 'en');
        $this->createCategoryTranslation($category3, 'de', 'C');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=de&flat=true'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $categories = $response->_embedded->categories;

        $this->assertCount(3, $categories);

        $this->assertEquals('A', $categories[0]->name);
        $this->assertEquals('B', $categories[1]->name);
        $this->assertEquals('C', $categories[2]->name);
    }

    public function testCGetLocaleFallback(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $this->createCategoryTranslation($category1, 'de', 'Erste Kategorie');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $this->createCategoryTranslation($category2, 'de', 'Zweite Kategorie');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=de'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertCount(2, $categories);

        $this->assertEquals('de', $categories[0]->locale);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('Erste Kategorie', $categories[0]->name);
        $this->assertEquals('de', $categories[1]->locale);
        $this->assertEquals('en', $categories[1]->defaultLocale);
        $this->assertEquals('Zweite Kategorie', $categories[1]->name);

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=de&rootKey=' . $category1->getKey()
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $response->_embedded->categories);

        $this->assertEquals('en', $response->_embedded->categories[0]->locale);
        $this->assertEquals('en', $response->_embedded->categories[0]->defaultLocale);
        $this->assertEquals('Third Category', $response->_embedded->categories[0]->name);
    }

    public function testCGetFlatLocaleFallback(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $this->createCategoryTranslation($category1, 'de', 'Erste Kategorie');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $this->createCategoryTranslation($category2, 'de', 'Zweite Kategorie');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=de&flat=true'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $categories = $response->_embedded->categories;
        \usort(
            $categories,
            function($cat1, $cat2) {
                return $cat1->id <=> $cat2->id;
            }
        );

        $this->assertCount(2, $categories);

        $this->assertEquals('de', $categories[0]->locale);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('Erste Kategorie', $categories[0]->name);
        $this->assertEquals('de', $categories[1]->locale);
        $this->assertEquals('en', $categories[1]->defaultLocale);
        $this->assertEquals('Zweite Kategorie', $categories[1]->name);

        $this->client->jsonRequest(
            'GET',
            '/api/categories?locale=de&flat=true&rootKey=' . $category1->getKey()
        );
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertCount(1, $response->_embedded->categories);

        $this->assertEquals('en', $response->_embedded->categories[0]->locale);
        $this->assertEquals('en', $response->_embedded->categories[0]->defaultLocale);
        $this->assertEquals('Third Category', $response->_embedded->categories[0]->name);
    }

    public function testPost(): void
    {
        $collection = $this->createCollection();
        $type = $this->createImageType();

        $medias = [
            $this->createMedia('test-1', $type, $collection),
            $this->createMedia('test-2', $type, $collection),
            $this->createMedia('test-3', $type, $collection),
        ];

        $this->em->flush();
        $this->em->clear();

        $ids = \array_map(
            function(Media $media) {
                return $media->getId();
            },
            $medias
        );

        $this->client->jsonRequest(
            'POST',
            '/api/categories?locale=en',
            [
                'name' => 'New Category',
                'description' => 'Sulu is awesome',
                'medias' => ['ids' => $ids],
                'key' => 'new-category-key',
                'meta' => [
                    [
                        'key' => 'myKey',
                        'value' => 'myValue',
                    ],
                    [
                        'key' => 'anotherKey',
                        'value' => 'should not be visible due to locale',
                        'locale' => 'de-ch',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('New Category', $response->name);
        $this->assertEquals('Sulu is awesome', $response->description);
        $this->assertEquals(['ids' => $ids], (array) $response->medias);
        $this->assertEquals('new-category-key', $response->key);
        $this->assertEquals('en', $response->defaultLocale);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals(1, \count($response->meta));
        $this->assertEquals('myKey', $response->meta[0]->key);
        $this->assertEquals('myValue', $response->meta[0]->value);

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'created']);
        $this->assertSame((string) $response->id, $activity->getResourceId());

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $response->id . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('New Category', $response->name);
        $this->assertEquals('new-category-key', $response->key);
        $this->assertEquals(1, \count($response->meta));
        $this->assertEquals('myKey', $response->meta[0]->key);
        $this->assertEquals('myValue', $response->meta[0]->value);
    }

    public function testPostWithoutMedia(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/categories?locale=en',
            [
                'name' => 'New Category',
                'medias' => null,
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('New Category', $response->name);
        $this->assertEquals(['ids' => []], (array) $response->medias);
    }

    public function testPostWithNoLocale(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/categories',
            [
                'name' => 'New Category',
                'key' => 'new-category-key',
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testPostWithExistingKey(): void
    {
        $this->createCategory('first-category-key', 'en');
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'POST',
            '/api/categories?locale=en',
            [
                'name' => 'New Category',
                'key' => 'first-category-key',
            ]
        );

        $this->assertHttpStatusCode(409, $this->client->getResponse());
    }

    public function testPostWithoutName(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/categories?locale=en',
            [
                'key' => 'new-category-key',
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testPostWithParent(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en', $category1);
        $this->createCategoryTranslation($category2, 'en', 'Second Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $response->children);

        $this->client->jsonRequest(
            'POST',
            '/api/categories?locale=en&parentId=' . $category1->getId(),
            [
                'name' => 'New Category',
                'key' => 'new-category-key',
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('New Category', $response->name);
        $this->assertEquals('new-category-key', $response->key);
        $this->assertEquals('en', $response->defaultLocale);
        $this->assertEquals($category1->getId(), $response->parentId);

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $response->children);
    }

    public function testPut(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $categoryMeta1 = $this->createCategoryMeta($category1, 'en', 'description', 'Description of Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $category1->getId() . '?locale=en',
            [
                'name' => 'Modified Category',
                'meta' => [
                    [
                        'id' => $categoryMeta1->getId(),
                        'key' => 'modifiedKey',
                        'value' => 'This meta got overriden',
                        'locale' => null,
                    ],
                    [
                        'key' => 'newMeta',
                        'value' => 'This meta got added',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('Modified Category', $response->name);
        $this->assertNull($response->key);
        $this->assertEquals('en', $response->defaultLocale);
        $this->assertEquals(2, \count($response->meta));

        \usort(
            $response->meta,
            function($m1, $m2) {
                return \strcmp($m1->key, $m2->key);
            }
        );
        $this->assertTrue('modifiedKey' === $response->meta[0]->key);
        $this->assertTrue('This meta got overriden' === $response->meta[0]->value);
        $this->assertTrue('newMeta' === $response->meta[1]->key);
        $this->assertTrue('This meta got added' === $response->meta[1]->value);

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'modified']);
        $this->assertSame((string) $response->id, $activity->getResourceId());

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('Modified Category', $response->name);
        $this->assertNull($response->key);
        $this->assertEquals(2, \count($response->meta));

        \usort(
            $response->meta,
            function($m1, $m2) {
                return \strcmp($m1->key, $m2->key);
            }
        );
        $this->assertTrue('modifiedKey' === $response->meta[0]->key);
        $this->assertTrue('This meta got overriden' === $response->meta[0]->value);
        $this->assertTrue('newMeta' === $response->meta[1]->key);
        $this->assertTrue('This meta got added' === $response->meta[1]->value);
    }

    public function testSortingOfMedia(): void
    {
        $collection = $this->createCollection();
        $type = $this->createImageType();
        $media1 = $this->createMedia('test-1', $type, $collection);
        $media2 = $this->createMedia('test-2', $type, $collection);

        $category1 = $this->createCategory('first-category-key', 'en');
        $categoryTranslation = $this->createCategoryTranslation($category1, 'en', 'First Category');
        $categoryTranslation->setMedias([$media1, $media2]);

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame([
            'ids' => [
                $media1->getId(),
                $media2->getId(),
            ],
        ], $response['medias']);

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $category1->getId() . '?locale=en',
            [
                'name' => 'Modified Category',
                'medias' => [
                    'ids' => [
                        $media2->getId(),
                        $media1->getId(),
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame([
            'ids' => [
                $media2->getId(),
                $media1->getId(),
            ],
        ], $response['medias']);

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame([
            'ids' => [
                $media2->getId(),
                $media1->getId(),
            ],
        ], $response['medias']);
    }

    public function testPutWithNoLocale(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $category1->getId(),
            [
                'name' => 'Modified Category',
                'key' => 'modified-category-key',
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testPutWithDifferentLocale(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $category1->getId() . '?locale=cn',
            [
                'name' => 'Imagine this is chinese',
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('Imagine this is chinese', $response->name);

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category1->getId() . '?locale=cn'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('Imagine this is chinese', $response->name);

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('First Category', $response->name);
    }

    public function testPutWithoutName(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $category1->getId() . '?locale=en',
            [
                'meta' => [
                    [
                        'key' => 'newMeta',
                        'value' => 'This meta got added',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testPutWithExistingKey(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/categories/' . $category2->getId() . '?locale=en',
            [
                'name' => 'New Category',
                'key' => 'first-category-key',
            ]
        );

        $this->assertHttpStatusCode(409, $this->client->getResponse());
    }

    public function testPutNotExisting(): void
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/categories/101?locale=en',
            [
                'name' => 'No existing Category',
                'meta' => [
                    [
                        'key' => 'newMeta',
                        'value' => 'This meta got added',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPatch(): void
    {
        $category = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category, 'en', 'First Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PATCH',
            '/api/categories/' . $category->getId() . '?locale=en',
            [
                'name' => 'Name changed through patch',
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals($category->getId(), $response->id);
        $this->assertEquals('Name changed through patch', $response->name);
        $this->assertEquals('first-category-key', $response->key);

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals($category->getId(), $response->id);
        $this->assertEquals('Name changed through patch', $response->name);
        $this->assertEquals('first-category-key', $response->key);
    }

    public function testPatchWithExistingKey(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PATCH',
            '/api/categories/' . $category2->getId() . '?locale=en',
            [
                'key' => 'first-category-key',
            ]
        );

        $this->assertHttpStatusCode(409, $this->client->getResponse());
    }

    public function testPatchNotExisting(): void
    {
        $this->client->jsonRequest(
            'PATCH',
            '/api/categories/101?locale=en',
            [
                'name' => 'Not existing Category',
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testDelete(): void
    {
        $category = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category, 'en', 'First Category');

        $this->em->flush();
        $this->em->clear();

        $categoryId = $category->getId();

        $this->client->jsonRequest(
            'DELETE',
            '/api/categories/' . $categoryId
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'removed']);
        $this->assertSame((string) $categoryId, $activity->getResourceId());

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $categoryId . '?locale=en'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testDeleteNotExisting(): void
    {
        $this->client->jsonRequest(
            'DELETE',
            '/api/categories/101'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testDeleteWithChildren(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');
        $category5 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category5, 'en', 'Fifth Category');

        $this->em->flush();

        $category1Id = $category1->getId();
        $category3Id = $category3->getId();
        $category4Id = $category4->getId();
        $category5Id = $category5->getId();

        $this->em->clear();

        $this->client->jsonRequest(
            'DELETE',
            '/api/categories/' . $category1Id
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(409, $response);

        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('errors', $content);
        unset($content['errors']);

        $this->assertEquals([
            'code' => 1105,
            'message' => 'Resource has 3 dependant resources.',
            'dependantResourcesCount' => 3,
            'dependantResourceBatches' => [
                [
                    [
                        'id' => $category4Id,
                        'resourceKey' => 'categories',
                    ],
                    [
                        'id' => $category5Id,
                        'resourceKey' => 'categories',
                    ],
                ],
                [
                    [
                        'id' => $category3Id,
                        'resourceKey' => 'categories',
                    ],
                ],
            ],
            'resource' => [
                'id' => $category1Id,
                'resourceKey' => 'categories',
            ],
            'title' => 'Delete 3 subcategories?',
            'detail' => 'Are you sure that you also want to delete 3 subcategories?',
        ], $content);
    }

    public function testMove(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'POST',
            '/api/categories/' . $category4->getId() . '?locale=en&action=move&destination=' . $category3->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($category3->getId(), $response['parentId']);

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category4->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($category3->getId(), $response['parentId']);
    }

    public function testMoveRoot(): void
    {
        $category1 = $this->createCategory('first-category-key', 'en');
        $this->createCategoryTranslation($category1, 'en', 'First Category');
        $category2 = $this->createCategory('second-category-key', 'en');
        $this->createCategoryTranslation($category2, 'en', 'Second Category');
        $category3 = $this->createCategory(null, 'en', $category1);
        $this->createCategoryTranslation($category3, 'en', 'Third Category');
        $category4 = $this->createCategory(null, 'en', $category3);
        $this->createCategoryTranslation($category4, 'en', 'Fourth Category');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'POST',
            '/api/categories/' . $category4->getId() . '?locale=en&action=move&destination=root'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('parent', $response);

        $this->client->jsonRequest(
            'GET',
            '/api/categories/' . $category4->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('parent', $response);
    }

    protected function createImageType()
    {
        $imageType = new MediaType();
        $imageType->setName('image');
        $imageType->setDescription('This is an image');

        $this->em->persist($imageType);

        return $imageType;
    }

    private function createCategory(
        ?string $key = null,
        ?string $defaultLocale = null,
        ?CategoryInterface $parentCategory = null
    ) {
        $category = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category->setKey($key);
        $category->setDefaultLocale($defaultLocale);

        if ($parentCategory) {
            $category->setParent($parentCategory);
            $parentCategory->addChild($category);
        }

        $this->em->persist($category);

        return $category;
    }

    private function createCategoryTranslation(CategoryInterface $category, string $locale, string $title)
    {
        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale($locale);
        $categoryTrans->setTranslation($title);
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        $this->em->persist($categoryTrans);

        return $categoryTrans;
    }

    private function createCategoryMeta(CategoryInterface $category, string $locale, string $key, string $value)
    {
        $categoryMeta = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta->setLocale($locale);
        $categoryMeta->setKey($key);
        $categoryMeta->setValue($value);
        $categoryMeta->setCategory($category);
        $category->addMeta($categoryMeta);

        $this->em->persist($categoryMeta);

        return $categoryMeta;
    }

    protected function createCollection()
    {
        $collection = new Collection();
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');
        $collection->setType($collectionType);

        $this->em->persist($collection);
        $this->em->persist($collectionType);
        $this->em->flush();

        return $collection;
    }

    protected function createMedia($name, $type, $collection, $locale = 'en')
    {
        $media = new Media();
        $media->setType($type);
        $extension = 'jpeg';
        $mimeType = 'image/jpg';

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName($name . '.' . $extension);
        $fileVersion->setMimeType($mimeType);
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1937-04-20'));
        $fileVersion->setCreated(new \DateTime('1937-04-20'));

        // create meta
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setLocale($locale);
        $fileVersionMeta->setTitle($name);
        $fileVersionMeta->setFileVersion($fileVersion);

        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setDefaultMeta($fileVersionMeta);

        $file->addFileVersion($fileVersion);

        $media->addFile($file);
        $media->setCollection($collection);

        $this->em->persist($media);
        $this->em->persist($file);
        $this->em->persist($fileVersionMeta);
        $this->em->persist($fileVersion);

        return $media;
    }
}
