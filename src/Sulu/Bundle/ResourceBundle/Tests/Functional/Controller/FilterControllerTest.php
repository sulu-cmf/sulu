<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use stdClass;
use Sulu\Bundle\ResourceBundle\Entity\Condition;
use Sulu\Bundle\ResourceBundle\Entity\ConditionGroup;
use Sulu\Bundle\ResourceBundle\Entity\Filter;
use Sulu\Bundle\ResourceBundle\Entity\FilterTranslation;
use Sulu\Bundle\ResourceBundle\Resource\DataTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpKernel\Client;

class FilterControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Filter
     */
    protected $filter1;

    /**
     * @var Filter
     */
    protected $filter2;

    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->db('ORM')->getOm();
        $this->purgeDatabase();
        $this->setUpFilter();
        $this->client = $this->createAuthenticatedClient();
    }

    protected function setUpFilter()
    {
        $this->filter1 = $this->createFilter('filter1', 'and', 'contact');
        $this->filter2 = $this->createFilter('filter2', 'or', 'Product');
        $this->filter3 = $this->createFilter('filter3', 'and', 'contact');

        $this->em->flush();
    }

    protected function createFilter($name, $conjunction, $context)
    {
        $filter = new Filter();
        $filter->setConjunction($conjunction);
        $filter->setContext($context);
        $filter->setChanged(new \DateTime());
        $filter->setCreated(new \DateTime());

        $filter->setCreator($this->getTestUser());
        $filter->setChanger($this->getTestUser());

        $trans = new FilterTranslation();
        $trans->setLocale('de');
        $trans->setName($name);
        $trans->setFilter($filter);

        $filter->addTranslation($trans);

        $conditionGroup1 = new ConditionGroup();
        $conditionGroup1->setFilter($filter);
        $conditionGroup1->addCondition(
            $this->createCondition($conditionGroup1, DataTypes::STRING_TYPE, 'test', 'LIKE', 'name')
        );

        $conditionGroup2 = new ConditionGroup();
        $conditionGroup2->setFilter($filter);
        $conditionGroup2->addCondition(
            $this->createCondition($conditionGroup2, DataTypes::NUMBER_TYPE, '2', '=', 'id')
        );

        $conditionGroup3 = new ConditionGroup();
        $conditionGroup3->setFilter($filter);
        $conditionGroup3->addCondition(
            $this->createCondition($conditionGroup3, DataTypes::DATETIME_TYPE, '2015-01-01', '>', 'created')
        );
        $conditionGroup3->addCondition(
            $this->createCondition($conditionGroup3, DataTypes::DATETIME_TYPE, '2015-02-02', '<', 'created')
        );

        $filter->addConditionGroup($conditionGroup1);
        $filter->addConditionGroup($conditionGroup2);
        $filter->addConditionGroup($conditionGroup3);

        $this->em->persist($filter);
        $this->em->persist($trans);
        $this->em->persist($conditionGroup1);
        $this->em->persist($conditionGroup2);
        $this->em->persist($conditionGroup3);

        return $filter;
    }

    protected function createCondition($conditionGroup, $type, $value, $operator, $name)
    {
        $condition = new Condition();
        $condition->setType($type);
        $condition->setValue($value);
        $condition->setOperator($operator);
        $condition->setField($name);
        $condition->setConditionGroup($conditionGroup);

        $this->em->persist($condition);

        return $condition;
    }

    /**
     * Test Filter GET by ID
     */
    public function testGetById()
    {
        $this->client->request(
            'GET',
            '/api/filters/' . $this->filter1->getId()
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($this->filter1->getId(), $response->id);
        $this->assertEquals($this->filter1->getConjunction(), $response->conjunction);
        $this->assertEquals($this->filter1->getContext(), $response->context);
        $this->assertNotEmpty($response->conditionGroups);

        $this->assertEquals(count($this->filter1->getConditionGroups()), count($response->conditionGroups));

        /** @var ConditionGroup $cg */
        $cg = $this->filter1->getConditionGroups()[0];
        $cgData = $this->getElementById($cg->getId(), $response->conditionGroups);
        $this->assertEquals($cg->getId(), $cgData->id);
        $this->assertEquals(count($cg->getConditions()), count($cgData->conditions));

        /** @var Condition $condition */
        $condition = $cg->getConditions()[0];
        $conditionData = $this->getElementById($condition->getId(), $cgData->conditions);
        $this->assertEquals($condition->getId(), $conditionData->id);
        $this->assertEquals($condition->getField(), $conditionData->field);
        $this->assertEquals($condition->getOperator(), $conditionData->operator);
        $this->assertEquals($condition->getType(), $conditionData->type);
        $this->assertEquals($condition->getValue(), $conditionData->value);
    }

    /**
     * @param $id
     * @param array $group
     * @return null|stdClass
     */
    protected function getElementById($id, array $group)
    {
        foreach ($group as $el) {
            if ($id === $el->id) {
                return $el;
            }
        }

        return null;
    }

    public function testCgetFlat()
    {
        $this->client->request(
            'GET',
            '/api/filters?flat=true&context=contact'
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertNotEmpty($response);
        $this->assertEquals(2, $response->total);
    }

    /**
     * Test GET for non existing filter (404)
     */
    public function testGetByIdNotExisting()
    {
        $this->client->request(
            'GET',
            '/api/filters/666'
        );
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test POST to create a new filter with details
     */
    public function testPost()
    {
        $filter = $this->createFilterAsArray('newFilter', false, 'contact');
        $this->client->request('POST', '/api/filters', $filter);
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/filters/' . $response->id);
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->assertEquals($filter['conjunction'], $response->conjunction);
        $this->assertEquals($filter['context'], $response->context);
        $this->assertEquals($filter['name'], $response->name);

        $this->assertEquals(count($filter['conditionGroups']), count($response->conditionGroups));
        $this->assertEquals(
            count($filter['conditionGroups'][0]['conditions']),
            count($response->conditionGroups[0]->conditions)
        );
    }

    /**
     * Test POST to create a new filter with details
     */
    public function testPostWithNotDefinedContext()
    {
        $filter = $this->createFilterAsArray('newFilter', false, 'not defined');
        $this->client->request('POST', '/api/filters', $filter);
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test POST to create a new filter with invalid data
     */
    public function testInvalidPost()
    {
        $filter = array(
            'conjunction' => false,
            'context' => 'contact',
        );
        $this->client->request('POST', '/api/filters', $filter);

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $filter = array(
            'name' => 'name',
            'context' => 'contact',
        );
        $this->client->request('POST', '/api/filters', $filter);

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $filter = array(
            'name' => 'name',
            'conjunction' => false,
        );
        $this->client->request('POST', '/api/filters', $filter);

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    public function createFilterAsArray($name, $conjunction, $context, $partial = false)
    {
        $result = array(
            'name' => $name,
            'conjunction' => $conjunction,
            'context' => $context,
        );

        if (!$partial) {
            $result['conditionGroups'] = array(
                array(
                    'conditions' => array(
                        array(
                            'value' => '5',
                            'field' => 'id',
                            'operator' => '>',
                            'type' => Condition::TYPE_NUMBER,
                        ),
                        array(
                            'value' => 'test',
                            'field' => 'name',
                            'operator' => 'LIKE',
                            'type' => Condition::TYPE_STRING,
                        ),
                    ),
                ),
            );
        }

        return $result;
    }

    /**
     * Test POST to create a new filter without conditions
     */
    public function testPostWithoutConditions()
    {
        $filter = $this->createFilterAsArray('newFilter', 'and', 'account', true);
        $this->client->request('POST', '/api/filters', $filter);
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/filters/' . $response->id);
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->assertEquals($filter['conjunction'], $response->conjunction);
        $this->assertEquals($filter['context'], $response->context);
        $this->assertEquals($filter['name'], $response->name);
    }

    /**
     * Test PUT to update an existing filter
     */
    public function testPut()
    {
        $newName = 'The new name';
        $conjunction = false;
        $newContext = 'account';

        // remove old condition group and add a new one
        $this->client->request(
            'PUT',
            '/api/filters/' . $this->filter1->getId(),
            array(
                'name' => $newName,
                'conjunction' => $conjunction,
                'context' => $newContext,
                'conditionGroups' => array(
                    array(
                        'conditions' => array(
                            array(
                                'value' => '6',
                                'field' => 'nr',
                                'operator' => '<',
                                'type' => Condition::TYPE_NUMBER,
                            ),
                            array(
                                'value' => 'test',
                                'field' => 'comment',
                                'operator' => '%LIKE%',
                                'type' => Condition::TYPE_STRING,
                            ),
                        ),
                    ),
                ),
            )
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($newName, $response->name);
        $this->assertEquals($conjunction, $response->conjunction);
        $this->assertEquals($newContext, $response->context);
        $this->assertEquals(1, count($response->conditionGroups));

        $conditionGroupId = $response->conditionGroups[0]->id;

        // remove old condition group and add a new one
        $this->client->request(
            'PUT',
            '/api/filters/' . $this->filter1->getId(),
            array(
                'conditionGroups' => array(
                    array(
                        'id' => $conditionGroupId,
                        'conditions' => array(
                            array(
                                'value' => '7',
                                'field' => 'id',
                                'operator' => 'LIKE',
                                'type' => Condition::TYPE_STRING,
                            ),
                            array(
                                'value' => 'test2',
                                'field' => 'nr',
                                'operator' => '>',
                                'type' => Condition::TYPE_NUMBER,
                            ),
                        ),
                    ),
                    array(
                        'conditions' => array(
                            array(
                                'value' => '123',
                                'field' => 'nr',
                                'operator' => '=<',
                                'type' => Condition::TYPE_NUMBER,
                            ),
                            array(
                                'value' => 'test17',
                                'field' => 'comment',
                                'operator' => '%LIKE%',
                                'type' => Condition::TYPE_STRING,
                            ),
                        ),
                    ),
                ),
            )
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($newName, $response->name);
        $this->assertEquals($conjunction, $response->conjunction);
        $this->assertEquals($newContext, $response->context);
        $this->assertEquals(2, count($response->conditionGroups));
    }

    /**
     * Test PUT to update an existing condition group
     */
    public function testPutNewConditionExistingConditionGroup()
    {
        $newName = 'The new name';
        $conjunction = false;
        $newContext = 'account';

        // remove old condition group and add a new one
        $this->client->request(
            'PUT',
            '/api/filters/' . $this->filter1->getId(),
            array(
                'name' => $newName,
                'conjunction' => $conjunction,
                'context' => $newContext,
                'conditionGroups' => array(
                    array(
                        'id' => $this->filter1->getConditionGroups()[0]->getId(),
                        'conditions' => array(
                            array(
                                'value' => '6',
                                'field' => 'nr',
                                'operator' => '<',
                                'type' => Condition::TYPE_NUMBER,
                            )
                        ),
                    ),
                ),
            )
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($newName, $response->name);
        $this->assertEquals($conjunction, $response->conjunction);
        $this->assertEquals($newContext, $response->context);
        $this->assertCount(1, $response->conditionGroups);
        $this->assertCount(1, $response->conditionGroups[0]->conditions);
        $this->assertEquals('6', $response->conditionGroups[0]->conditions[0]->value);
        $this->assertEquals('nr', $response->conditionGroups[0]->conditions[0]->field);
        $this->assertEquals('<', $response->conditionGroups[0]->conditions[0]->operator);
    }

    /**
     * Test PUT to update an existing filter without conditions
     */
    public function testPutWithoutConditions()
    {
        $newName = 'The new name';
        $conjunction = false;
        $newContext = 'account';

        $this->client->request(
            'PUT',
            '/api/filters/' . $this->filter1->getId(),
            array(
                'name' => $newName,
                'conjunction' => $conjunction,
                'context' => $newContext,
            )
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($newName, $response->name);
        $this->assertEquals($conjunction, $response->conjunction);
        $this->assertEquals($newContext, $response->context);
    }

    /**
     * Test PUT to update a not existing filter
     */
    public function testPutNotExisting()
    {
        $this->client->request('PUT', '/api/filters/666', array('code' => 'Missing filter'));
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            'Entity with the type "SuluResourceBundle:Filter" and the id "666" not found.',
            $response->message
        );
    }

    /**
     * Test DELETE
     */
    public function testDeleteById()
    {
        $this->client->request('DELETE', '/api/filters/' . $this->filter1->getId());
        $this->assertEquals('204', $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/filters/' . $this->filter1->getId());
        $this->assertEquals('404', $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test CDELETE
     */
    public function testCDeleteByIds()
    {
        $this->client->request('DELETE',
            '/api/filters?ids=' . $this->filter1->getId() . ',' . $this->filter2->getId() . ',' . $this->filter3->getId(
            )
        );
        $this->assertEquals('204', $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/filters');
        $this->assertEquals('200', $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEmpty($response->_embedded->filters);
    }

    /**
     * Test CDELETE with non existent ids
     */
    public function testCDeleteByIdsNotExisting()
    {
        $this->client->request('DELETE', '/api/filters?ids=666,999');
        $this->assertEquals('204', $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/filters');
        $this->assertEquals('200', $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(3, count($response->_embedded->filters));
    }

    /**
     * Test CDELETE with partially existent ids
     */
    public function testCDeleteByIdsPartialExistent()
    {
        $this->client->request('DELETE', '/api/filters?ids=' . $this->filter1->getId() . ',666');
        $this->assertEquals('204', $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/filters');
        $this->assertEquals('200', $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(2, count($response->_embedded->filters));
    }

    /**
     * Test DELETE on none existing Object
     */
    public function testDeleteByIdNotExisting()
    {
        $this->client->request('GET', '/api/filters/666');
        $this->assertEquals('404', $this->client->getResponse()->getStatusCode());
    }
}
