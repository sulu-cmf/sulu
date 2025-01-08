<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Functional\Controller;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class TargetGroupControllerTest extends SuluTestCase
{
    public const BASE_URL = 'api/target-groups';

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
    }

    public function testGetById(): void
    {
        $targetGroup = $this->createTargetGroup([
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
            'webspaces' => [
                [
                    'webspaceKey' => 'my-webspace-1',
                ],
                [
                    'webspaceKey' => 'my-webspace-2',
                ],
            ],
        ]);

        $this->getEntityManager()->flush();

        $this->client->request('GET', self::BASE_URL . '/' . $targetGroup->getId());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Target Group Title', $response['title']);
        $this->assertEquals('Target group description number 1', $response['description']);
        $this->assertEquals(3, $response['priority']);
        $this->assertEquals(true, $response['active']);
        $this->assertCount(2, $response['webspaces']);
    }

    public function testGetAll(): void
    {
        $targetGroup1 = $this->createTargetGroup([
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
        ]);
        $targetGroup2 = $this->createTargetGroup([
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
        ]);

        $this->getEntityManager()->flush();

        $this->client->request('GET', self::BASE_URL);

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $targetGroups = $response['_embedded']['target_groups'];
        $this->assertCount(2, $targetGroups);
    }

    public function testPost(): void
    {
        $data = [
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
            'webspaceKeys' => ['my-webspace-1', 'my-webspace-2'],
            'rules' => [
                [
                    'title' => 'rule-1',
                    'frequency' => 1,
                    'conditions' => [
                        [
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'de',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', self::BASE_URL, [], [], [], \json_encode($data, \JSON_THROW_ON_ERROR));

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($data['title'], $response['title']);
        $this->assertEquals($data['description'], $response['description']);
        $this->assertEquals($data['priority'], $response['priority']);
        $this->assertEquals($data['active'], $response['active']);
        $this->assertCount(\count($data['webspaceKeys']), $response['webspaceKeys']);

        $this->assertNotNull($this->getTargetGroupRepository()->find($response['id']));
        $targetGroup = $this->getTargetGroupRepository()->find($response['id']);
        $rule1 = $targetGroup->getRules()[0];
        $rule1Conditions = $rule1->getConditions()[0];
        $this->assertEquals($data['rules'][0]['title'], $rule1->getTitle());
        $this->assertEquals($data['rules'][0]['frequency'], $rule1->getFrequency());
        $this->assertEquals($data['rules'][0]['conditions'][0]['type'], $rule1Conditions->getType());
        $this->assertEquals($data['rules'][0]['conditions'][0]['condition'], $rule1Conditions->getCondition());
    }

    public function testPut(): void
    {
        $targetGroup = $this->createTargetGroup([
            'title' => 'Target Group Title',
        ]);

        $this->getEntityManager()->flush();

        $data = [
            'title' => 'Target Group Title 2',
            'description' => 'Target group description number 2',
            'priority' => 4,
            'active' => false,
            'webspaceKeys' => [
                'my-webspace-1',
            ],
            'rules' => [
                [
                    'title' => 'rule-1',
                    'frequency' => 1,
                    'conditions' => [
                        [
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'de',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('PUT', self::BASE_URL . '/' . $targetGroup->getId(), [], [], [], \json_encode($data, \JSON_THROW_ON_ERROR));

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($data['title'], $response['title']);
        $this->assertEquals($data['description'], $response['description']);
        $this->assertEquals($data['priority'], $response['priority']);
        $this->assertEquals($data['active'], $response['active']);
        $this->assertCount(\count($data['webspaceKeys']), $response['webspaces']);
        $this->assertCount(\count($data['rules']), $response['rules']);
        $this->assertEquals($data['webspaceKeys'][0], $response['webspaces'][0]['webspaceKey']);
        $this->assertEquals($data['webspaceKeys'][0], $response['webspaceKeys'][0]);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($response['id']);
        $this->assertNotNull($targetGroup);
        $this->assertCount(\count($data['webspaceKeys']), $targetGroup->getWebspaces());
        $this->assertCount(\count($data['rules']), $targetGroup->getRules());
        $webspace1 = $targetGroup->getWebspaces()[0];
        $rule1 = $targetGroup->getRules()[0];
        $rule1Conditions = $rule1->getConditions()[0];
        $this->assertCount(\count($data['webspaceKeys']), $response['webspaces']);
        $this->assertEquals($data['webspaceKeys'][0], $webspace1->getWebspaceKey());
        $this->assertCount(\count($data['rules']), $response['rules']);
        $this->assertEquals($data['rules'][0]['title'], $rule1->getTitle());
        $this->assertEquals($data['rules'][0]['frequency'], $rule1->getFrequency());
        $this->assertEquals($data['rules'][0]['conditions'][0]['type'], $rule1Conditions->getType());
        $this->assertEquals($data['rules'][0]['conditions'][0]['condition'], $rule1Conditions->getCondition());
    }

    public function testPutWithRemoveRoleAndWebspaces(): void
    {
        $targetGroup = $this->createTargetGroup([
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
            'webspaces' => [
                [
                    'webspaceKey' => 'my-webspace-1',
                ],
                [
                    'webspaceKey' => 'my-webspace-2',
                ],
            ],
            'rules' => [
                [
                    'title' => 'rule-1',
                    'frequency' => 1,
                    'conditions' => [
                        [
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'de',
                            ],
                        ],
                        [
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'en',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'rule-2',
                    'frequency' => 2,
                    'conditions' => [],
                ],
            ],
        ]);

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $data = [
            'title' => 'Target Group Title',
            'description' => 'Target group description number 1',
            'priority' => 3,
            'active' => true,
            'webspaceKeys' => [
                'my-webspace-1',
            ],
            'rules' => [
                [
                    'id' => $targetGroup->getRules()[0]->getId(),
                    'title' => 'rule-1',
                    'frequency' => 1,
                    'conditions' => [
                        [
                            'id' => $targetGroup->getRules()[0]->getConditions()[0]->getId(),
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'en',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('PUT', self::BASE_URL . '/' . $targetGroup->getId(), [], [], [], \json_encode($data, \JSON_THROW_ON_ERROR));

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(\count($data['webspaceKeys']), $response['webspaces']);
        $this->assertCount(\count($data['rules']), $response['rules']);
        $this->assertCount(\count($data['rules'][0]['conditions']), $response['rules'][0]['conditions']);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($response['id']);
        $this->assertNotNull($targetGroup);
        $this->assertCount(\count($data['webspaceKeys']), $targetGroup->getWebspaces());
        $this->assertEquals($data['webspaceKeys'][0], $targetGroup->getWebspaces()[0]->getWebspaceKey());
        $this->assertEquals($data['webspaceKeys'][0], $targetGroup->getWebspaces()[0]->getWebspaceKey());
        $this->assertCount(\count($data['rules']), $targetGroup->getRules());
        $rule1 = $targetGroup->getRules()[0];
        $this->assertEquals($data['rules'][0]['title'], $rule1->getTitle());
        $this->assertEquals($data['rules'][0]['frequency'], $rule1->getFrequency());
        $this->assertCount(\count($data['rules'][0]['conditions']), $rule1->getConditions());
        $this->assertEquals($data['rules'][0]['conditions'][0]['type'], $rule1->getConditions()[0]->getType());
        $this->assertEquals($data['rules'][0]['conditions'][0]['condition'], $rule1->getConditions()[0]->getCondition());
    }

    public function testPutWithCreateTargetGroupCondition(): void
    {
        $targetGroup = $this->createTargetGroup([
            'title' => 'Target Group Title',
        ]);

        $this->getEntityManager()->flush();

        $data = [
            'title' => 'Target Group Title 2',
            'description' => 'Target group description number 2',
            'priority' => 4,
            'active' => false,
            'webspaceKeys' => [
                'my-webspace-1',
            ],
            'rules' => [
                [
                    'title' => 'rule-1',
                    'frequency' => 1,
                    'conditions' => [
                        [
                            'id' => null,
                            'type' => 'locale',
                            'condition' => [
                                'locale' => 'de',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('PUT', self::BASE_URL . '/' . $targetGroup->getId(), [], [], [], \json_encode($data, \JSON_THROW_ON_ERROR));

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($data['title'], $response['title']);
        $this->assertEquals($data['description'], $response['description']);
        $this->assertEquals($data['priority'], $response['priority']);
        $this->assertEquals($data['active'], $response['active']);
        $this->assertCount(\count($data['webspaceKeys']), $response['webspaces']);
        $this->assertCount(\count($data['rules']), $response['rules']);
        $this->assertEquals($data['webspaceKeys'][0], $response['webspaces'][0]['webspaceKey']);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($response['id']);
        $this->assertNotNull($targetGroup);
        $this->assertCount(\count($data['webspaceKeys']), $targetGroup->getWebspaces());
        $this->assertCount(\count($data['rules']), $targetGroup->getRules());
        $webspace1 = $targetGroup->getWebspaces()[0];
        $rule1 = $targetGroup->getRules()[0];
        $rule1Conditions = $rule1->getConditions()[0];
        $this->assertCount(\count($data['webspaceKeys']), $response['webspaces']);
        $this->assertEquals($data['webspaceKeys'][0], $webspace1->getWebspaceKey());
        $this->assertCount(\count($data['rules']), $response['rules']);
        $this->assertEquals($data['rules'][0]['title'], $rule1->getTitle());
        $this->assertEquals($data['rules'][0]['frequency'], $rule1->getFrequency());
        $this->assertEquals($data['rules'][0]['conditions'][0]['type'], $rule1Conditions->getType());
        $this->assertEquals($data['rules'][0]['conditions'][0]['condition'], $rule1Conditions->getCondition());
    }

    public function testSingleDelete(): void
    {
        $targetGroup = $this->createTargetGroup([
            'title' => 'Target Group Title',
        ]);

        $this->getEntityManager()->flush();

        $targetGroupId = $targetGroup->getId();

        $this->client->request('DELETE', self::BASE_URL . '/' . $targetGroupId);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($targetGroupId);

        $this->assertNull($targetGroup);
    }

    public function testMultipleDelete(): void
    {
        $targetGroup1 = $this->createTargetGroup([
            'title' => 'Target Group Title',
        ]);
        $targetGroup2 = $this->createTargetGroup([
            'title' => 'Target Group Title',
        ]);

        $this->getEntityManager()->flush();

        $targetGroupId1 = $targetGroup1->getId();
        $targetGroupId2 = $targetGroup2->getId();

        $this->client->request(
            'DELETE',
            self::BASE_URL . '?ids=' . \implode(',', [$targetGroupId1, $targetGroupId2])
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($targetGroupId1);
        $targetGroup2 = $this->getTargetGroupRepository()->find($targetGroupId2);

        $this->assertNull($targetGroup);
        $this->assertNull($targetGroup2);
    }

    /**
     * Create a new Target Group.
     *
     * @param array $data
     *
     * @return TargetGroupInterface
     */
    private function createTargetGroup($data)
    {
        /** @var TargetGroupInterface $targetGroup */
        $targetGroup = $this->getTargetGroupRepository()->createNew();
        $this->getEntityManager()->persist($targetGroup);
        $targetGroup->setTitle($this->getProperty($data, 'title', 'Target Group'));
        $targetGroup->setDescription($this->getProperty($data, 'description', 'Target Group Description'));
        $targetGroup->setPriority($this->getProperty($data, 'priority', 1));
        $targetGroup->setAllWebspaces($this->getProperty($data, 'allWebspaces', false));
        $targetGroup->setActive($this->getProperty($data, 'active', true));

        $webspaces = $this->getProperty($data, 'webspaces', []);
        foreach ($webspaces as $index => $webspaceData) {
            $this->createTargetGroupWebspace($webspaceData, $targetGroup);
        }

        $rules = $this->getProperty($data, 'rules', []);
        foreach ($rules as $ruleData) {
            $this->createTargetGroupRule($ruleData, $targetGroup);
        }

        return $targetGroup;
    }

    /**
     * Creates a target group webspace entity.
     *
     * @param array $data
     *
     * @return TargetGroupWebspaceInterface
     */
    private function createTargetGroupWebspace($data, TargetGroupInterface $targetGroup)
    {
        /** @var TargetGroupWebspaceInterface $webspace */
        $webspace = $this->getTargetGroupWebspaceRepository()->createNew();
        $this->getEntityManager()->persist($targetGroup);
        $webspace->setTargetGroup($targetGroup);
        $webspace->setWebspaceKey($this->getProperty($data, 'webspaceKey', 'webspacekey-' . \uniqid()));
        $targetGroup->addWebspace($webspace);

        return $webspace;
    }

    private function createTargetGroupRule($data, TargetGroupInterface $targetGroup)
    {
        $targetGroupRule = $this->getTargetGroupRuleRepository()->createNew();
        $this->getEntityManager()->persist($targetGroupRule);
        $targetGroupRule->setTargetGroup($targetGroup);
        $targetGroupRule->setTitle($data['title']);
        $targetGroupRule->setFrequency($data['frequency']);
        $targetGroup->addRule($targetGroupRule);

        $conditions = $this->getProperty($data, 'conditions', []);
        foreach ($conditions as $conditionData) {
            $this->createTargetGroupCondition($conditionData, $targetGroupRule);
        }

        return $targetGroupRule;
    }

    private function createTargetGroupCondition($data, TargetGroupRuleInterface $targetGroupRule)
    {
        $targetGroupCondition = $this->getTargetGroupConditionRepository()->createNew();
        $this->getEntityManager()->persist($targetGroupCondition);
        $targetGroupCondition->setRule($targetGroupRule);
        $targetGroupCondition->setType($data['type']);
        $targetGroupCondition->setCondition($data['condition']);
        $targetGroupRule->addCondition($targetGroupCondition);

        return $targetGroupCondition;
    }

    /**
     * Returns value from data array with given key. If none found, given default is returned.
     *
     * @param array $data
     * @param string $key
     */
    private function getProperty($data, $key, $default)
    {
        if (\array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * @return TargetGroupRepositoryInterface
     */
    private function getTargetGroupRepository()
    {
        return $this->getContainer()->get('sulu.repository.target_group');
    }

    /**
     * @return TargetGroupWebspaceRepositoryInterface
     */
    private function getTargetGroupWebspaceRepository()
    {
        return $this->getContainer()->get('sulu.repository.target_group_webspace');
    }

    /**
     * @return TargetGroupWebspaceRepositoryInterface
     */
    private function getTargetGroupRuleRepository()
    {
        return $this->getContainer()->get('sulu.repository.target_group_rule');
    }

    private function getTargetGroupConditionRepository()
    {
        return $this->getContainer()->get('sulu.repository.target_group_condition');
    }
}
