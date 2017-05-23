<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Functional\Controller;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TargetGroupControllerTest extends SuluTestCase
{
    const BASE_URL = 'api/target-groups';

    /**
     * @var TargetGroupInterface
     */
    private $targetGroup;

    /**
     * @var TargetGroupInterface
     */
    private $targetGroup2;

    /**
     * Data used for setup initial target groups.
     *
     * @var array
     */
    private $setupData;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initOrm();
    }

    /**
     * Initialize test data.
     */
    public function initOrm()
    {
        $this->purgeDatabase();

        $this->setupData = [
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
        ];

        // Create first target group.
        $this->targetGroup = $this->createTargetGroup($this->setupData);
        $this->targetGroup2 = $this->createTargetGroup($this->setupData);

        // Flush.
        $this->getEntityManager()->flush();
    }

    /**
     * Test if controller returns correct entity when perform get by id request.
     */
    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', self::BASE_URL . '/' . $this->targetGroup->getId());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $sampleData = $this->setupData;

        $this->assertEquals($sampleData['title'], $response['title']);
        $this->assertEquals($sampleData['description'], $response['description']);
        $this->assertEquals($sampleData['priority'], $response['priority']);
        $this->assertEquals($sampleData['active'], $response['active']);
        $this->assertCount(count($sampleData['webspaces']), $response['webspaces']);
    }

    /**
     * Test if cget action returns all target-groups.
     */
    public function testGetAll()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', self::BASE_URL);

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $targetGroups = $response['_embedded']['target-groups'];
        $this->assertCount(2, $targetGroups);
    }

    /**
     * Test if post of target group.
     */
    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $data = [
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
        ];

        $client->request('POST', self::BASE_URL, [], [], [], json_encode($data));

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data['title'], $response['title']);
        $this->assertEquals($data['description'], $response['description']);
        $this->assertEquals($data['priority'], $response['priority']);
        $this->assertEquals($data['active'], $response['active']);
        $this->assertCount(count($data['webspaces']), $response['webspaces']);

        $this->assertNotNull($this->getTargetGroupRepository()->find($response['id']));
    }

    /**
     * Test if controller returns correct entity when perform get by id request.
     */
    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'title' => 'Target Group Title 2',
            'description' => 'Target group description number 2',
            'priority' => 4,
            'active' => false,
            'webspaces' => [
                [
                    'webspaceKey' => 'my-webspace-1',
                ],
            ],
        ];

        $client->request('PUT', self::BASE_URL . '/' . $this->targetGroup->getId(), [], [], [], json_encode($data));

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data['title'], $response['title']);
        $this->assertEquals($data['description'], $response['description']);
        $this->assertEquals($data['priority'], $response['priority']);
        $this->assertEquals($data['active'], $response['active']);
        $this->assertCount(count($data['webspaces']), $response['webspaces']);
        $this->assertEquals($data['webspaces'][0]['webspaceKey'], $response['webspaces'][0]['webspaceKey']);

        $this->assertNotNull($this->getTargetGroupRepository()->find($response['id']));
    }

    /**
     * Test deleting a target group over api.
     */
    public function testSingleDelete()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', self::BASE_URL . '/' . $this->targetGroup->getId());

        $response = $client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($this->targetGroup->getId());

        $this->assertNull($targetGroup);
    }

    /**
     * Test deleting multiple target groups over api.
     */
    public function testMultipleDelete()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            self::BASE_URL . '?ids=' . implode(',', [$this->targetGroup->getId(), $this->targetGroup2->getId()])
        );

        $response = $client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $this->getEntityManager()->clear();

        $targetGroup = $this->getTargetGroupRepository()->find($this->targetGroup->getId());
        $targetGroup2 = $this->getTargetGroupRepository()->find($this->targetGroup2->getId());

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

        return $targetGroup;
    }

    /**
     * Creates a target group webspace entity.
     *
     * @param array $data
     * @param TargetGroupInterface $targetGroup
     *
     * @return TargetGroupWebspaceInterface
     */
    private function createTargetGroupWebspace($data, TargetGroupInterface $targetGroup)
    {
        /** @var TargetGroupWebspaceInterface $webspace */
        $webspace = $this->getTargetGroupWebspaceRepository()->createNew();
        $this->getEntityManager()->persist($targetGroup);
        $webspace->setTargetGroup($targetGroup);
        $webspace->setWebspaceKey($this->getProperty($data, 'webspaceKey', 'webspacekey-' . uniqid()));
        $targetGroup->addWebspace($webspace);

        return $webspace;
    }

    /**
     * Returns value from data array with given key. If none found, given default is returned.
     *
     * @param array $data
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    private function getProperty($data, $key, $default)
    {
        if (array_key_exists($key, $data)) {
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
}
