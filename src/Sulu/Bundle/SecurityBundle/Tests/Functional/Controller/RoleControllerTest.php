<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\SecurityType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Util\SortUtils;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class RoleControllerTest extends SuluTestCase
{
    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var Role
     */
    private $role1;

    /**
     * @var Role
     */
    private $role2;

    /**
     * @var Role
     */
    private $role3;

    /**
     * @var Permission
     */
    private $permission1;

    /**
     * @var Permission
     */
    private $permission2;

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
        $this->em = $this->getEntityManager();
        $this->activityRepository = $this->em->getRepository(ActivityInterface::class);
        $this->purgeDatabase();

        $role1 = new Role();
        $role1->setName('Sulu Administrator');
        $role1->setKey('sulu_administrator');
        $role1->setSystem('Sulu');
        $this->em->persist($role1);
        $this->role1 = $role1;

        $role2 = new Role();
        $role2->setName('Sulu Editor');
        $role2->setSystem('Sulu');
        $this->em->persist($role2);
        $this->role2 = $role2;

        $role3 = new Role();
        $role3->setName('Anonymous Role');
        $role3->setSystem('Website');
        $role3->setAnonymous(true);
        $this->em->persist($role3);
        $this->role3 = $role3;

        $permission1 = new Permission();
        $permission1->setRole($role1);
        $permission1->setContext('context1');
        $permission1->setPermissions(15);
        $this->em->persist($permission1);
        $this->permission1 = $permission1;

        $permission2 = new Permission();
        $permission2->setRole($role1);
        $permission2->setContext('context2');
        $permission2->setPermissions(17);
        $this->em->persist($permission2);
        $this->permission2 = $permission2;

        $permission3 = new Permission();
        $permission3->setRole($role2);
        $permission3->setContext('context1');
        $permission3->setPermissions(64);
        $this->em->persist($permission3);

        $permission4 = new Permission();
        $permission4->setRole($role2);
        $permission4->setContext('context2');
        $permission4->setPermissions(35);
        $this->em->persist($permission4);

        $this->em->flush();
        $this->em->clear();
    }

    public function testList(): void
    {
        $this->client->jsonRequest('GET', '/api/roles?flat=true');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(2, \count($response->_embedded->roles));
        $this->assertEquals('Sulu Administrator', $response->_embedded->roles[0]->name);
        $this->assertEquals('sulu_administrator', $response->_embedded->roles[0]->key);
        $this->assertEquals('Sulu', $response->_embedded->roles[0]->system);
    }

    public function testListWithAnonymous(): void
    {
        $this->client->request('GET', '/api/roles?flat=true&include-anonymous=true');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(3, \count($response->_embedded->roles));
    }

    public function testGetById(): void
    {
        $this->client->jsonRequest('GET', '/api/roles/' . $this->role1->getId());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('Sulu Administrator', $response->name);
        $this->assertEquals('sulu_administrator', $response->key);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals(2, \count($response->permissions));
        $this->assertEquals('context1', $response->permissions[0]->context);
        $this->assertEquals(false, $response->permissions[0]->permissions->view);
        $this->assertEquals(false, $response->permissions[0]->permissions->add);
        $this->assertEquals(false, $response->permissions[0]->permissions->edit);
        $this->assertEquals(true, $response->permissions[0]->permissions->delete);
        $this->assertEquals(true, $response->permissions[0]->permissions->archive);
        $this->assertEquals(true, $response->permissions[0]->permissions->live);
        $this->assertEquals(true, $response->permissions[0]->permissions->security);
        $this->assertEquals('context2', $response->permissions[1]->context);
        $this->assertEquals(false, $response->permissions[1]->permissions->view);
        $this->assertEquals(false, $response->permissions[1]->permissions->add);
        $this->assertEquals(true, $response->permissions[1]->permissions->edit);
        $this->assertEquals(false, $response->permissions[1]->permissions->delete);
        $this->assertEquals(false, $response->permissions[1]->permissions->archive);
        $this->assertEquals(false, $response->permissions[1]->permissions->live);
        $this->assertEquals(true, $response->permissions[1]->permissions->security);
    }

    public function testPost(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/roles',
            [
                'name' => 'Portal Manager',
                'key' => 'portal_manager',
                'system' => 'Sulu',
                'permissions' => [
                    [
                        'context' => 'portal1',
                        'permissions' => [
                            'view' => false,
                            'add' => false,
                            'edit' => true,
                            'delete' => true,
                            'archive' => false,
                            'live' => true,
                            'security' => false,
                        ],
                    ],
                    [
                        'context' => 'portal2',
                        'permissions' => [
                            'view' => false,
                            'add' => false,
                            'edit' => false,
                            'delete' => true,
                            'archive' => false,
                            'live' => false,
                            'security' => false,
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('portal_manager', $response->key);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals(2, \count($response->permissions));
        $this->assertEquals('portal1', $response->permissions[0]->context);
        $this->assertEquals(false, $response->permissions[0]->permissions->view);
        $this->assertEquals(false, $response->permissions[0]->permissions->add);
        $this->assertEquals(true, $response->permissions[0]->permissions->edit);
        $this->assertEquals(true, $response->permissions[0]->permissions->delete);
        $this->assertEquals(false, $response->permissions[0]->permissions->archive);
        $this->assertEquals(true, $response->permissions[0]->permissions->live);
        $this->assertEquals(false, $response->permissions[0]->permissions->security);
        $this->assertEquals('portal2', $response->permissions[1]->context);
        $this->assertEquals(false, $response->permissions[1]->permissions->view);
        $this->assertEquals(false, $response->permissions[1]->permissions->add);
        $this->assertEquals(false, $response->permissions[1]->permissions->edit);
        $this->assertEquals(true, $response->permissions[1]->permissions->delete);
        $this->assertEquals(false, $response->permissions[1]->permissions->archive);
        $this->assertEquals(false, $response->permissions[1]->permissions->live);
        $this->assertEquals(false, $response->permissions[1]->permissions->security);

        $this->client->jsonRequest(
            'GET',
            '/api/roles/' . $response->id
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('portal_manager', $response->key);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals(2, \count($response->permissions));
        $this->assertEquals('portal1', $response->permissions[0]->context);
        $this->assertEquals(false, $response->permissions[0]->permissions->view);
        $this->assertEquals(false, $response->permissions[0]->permissions->add);
        $this->assertEquals(true, $response->permissions[0]->permissions->edit);
        $this->assertEquals(true, $response->permissions[0]->permissions->delete);
        $this->assertEquals(false, $response->permissions[0]->permissions->archive);
        $this->assertEquals(true, $response->permissions[0]->permissions->live);
        $this->assertEquals(false, $response->permissions[0]->permissions->security);
        $this->assertEquals('portal2', $response->permissions[1]->context);
        $this->assertEquals(false, $response->permissions[1]->permissions->view);
        $this->assertEquals(false, $response->permissions[1]->permissions->add);
        $this->assertEquals(false, $response->permissions[1]->permissions->edit);
        $this->assertEquals(true, $response->permissions[1]->permissions->delete);
        $this->assertEquals(false, $response->permissions[1]->permissions->archive);
        $this->assertEquals(false, $response->permissions[1]->permissions->live);
        $this->assertEquals(false, $response->permissions[1]->permissions->security);
    }

    public function testPut(): void
    {
        $putData = [
            'name' => 'Portal Manager',
            'key' => 'portal_manager',
            'system' => 'Sulu',
            'permissions' => [
                [
                    'id' => $this->permission1->getId(),
                    'context' => 'portal1',
                    'module' => null,
                    'permissions' => [
                        'view' => true,
                        'add' => true,
                        'edit' => true,
                        'delete' => true,
                        'archive' => false,
                        'live' => false,
                        'security' => false,
                    ],
                ],
                [
                    'id' => $this->permission2->getId(),
                    'context' => 'portal2',
                    'module' => null,
                    'permissions' => [
                        'view' => false,
                        'add' => false,
                        'edit' => false,
                        'delete' => false,
                        'archive' => true,
                        'live' => true,
                        'security' => true,
                    ],
                ],
                [
                    'context' => 'portal3',
                    'module' => null,
                    'permissions' => [
                        'view' => false,
                        'add' => false,
                        'edit' => false,
                        'delete' => false,
                        'archive' => true,
                        'live' => true,
                        'security' => true,
                    ],
                ],
            ],
        ];

        $this->client->jsonRequest(
            'PUT',
            '/api/roles/' . $this->role1->getId(),
            $putData
        );

        $expectedData = $putData;

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        $this->assertIsArray($response);
        unset($response['id']);
        $this->assertSame('ROLE_SULU_PORTAL_MANAGER', $response['identifier']);
        unset($response['identifier']);
        $response['permissions'] = SortUtils::multisort($response['permissions'], '[id]');
        unset($response['permissions'][2]['id']);

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'modified']);
        $this->assertSame((string) $this->role1->getId(), $activity->getResourceId());

        $this->client->jsonRequest(
            'GET',
            '/api/roles/' . $this->role1->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true, \JSON_THROW_ON_ERROR);
        $this->assertIsArray($response);
        unset($response['id']);
        $this->assertSame('ROLE_SULU_PORTAL_MANAGER', $response['identifier']);
        unset($response['identifier']);
        $response['permissions'] = SortUtils::multisort($response['permissions'], '[id]');
        unset($response['permissions'][2]['id']);

        $this->assertSame($expectedData, $response);
    }

    public function testPutNotExisting(): void
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/roles/11230',
            [
                'name' => 'Portal Manager',
                'system' => 'Sulu',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $this->assertStringContainsString('11230', $response->message);
    }

    public function testPutWithExistingName(): void
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/roles/' . $this->role2->getId(),
            [
                'name' => 'Sulu Administrator',
                'system' => 'Sulu',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertEquals(1101, $response->code);
    }

    public function testPutWithExistingKey(): void
    {
        // setting key to 'sulu_administrator' should return a conflict
        $this->client->jsonRequest(
            'PUT',
            '/api/roles/' . $this->role2->getId(),
            [
                'name' => 'Sulu Administrator 2',
                'key' => 'sulu_administrator',
                'system' => 'Sulu',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertEquals(1101, $response->code);

        // setting key to 'null' should work although there is another role with the key 'null'
        $this->client->jsonRequest(
            'PUT',
            '/api/roles/' . $this->role1->getId(),
            [
                'name' => 'Sulu Editor 2',
                'key' => null,
                'system' => 'Sulu',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals(null, $response->key);
    }

    public function testDelete(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/roles'
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals(2, \count($response->_embedded->roles));

        $this->client->jsonRequest(
            'DELETE',
            '/api/roles/' . $this->role1->getId()
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'removed']);
        $this->assertSame((string) $this->role1->getId(), $activity->getResourceId());

        $this->client->jsonRequest(
            'GET',
            '/api/roles'
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals(1, \count($response->_embedded->roles));
    }

    public function testDeleteNotExisting(): void
    {
        $this->client->jsonRequest(
            'DELETE',
            '/api/roles/11230'
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $this->assertStringContainsString('11230', $response->message);
    }

    public function testGetAllRoles(): void
    {
        $this->client->jsonRequest('GET', '/api/roles');

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(2, \count($response->_embedded->roles));

        $this->assertEquals('Sulu Administrator', $response->_embedded->roles[0]->name);
        $this->assertEquals('Sulu Editor', $response->_embedded->roles[1]->name);

        $this->assertEquals('sulu_administrator', $response->_embedded->roles[0]->key);
        $this->assertEquals(null, $response->_embedded->roles[1]->key);

        $this->assertEquals('Sulu', $response->_embedded->roles[0]->system);
        $this->assertEquals('Sulu', $response->_embedded->roles[1]->system);

        $contexts = [
            $response->_embedded->roles[0]->permissions[0]->context,
            $response->_embedded->roles[1]->permissions[1]->context,
        ];

        $this->assertContains('context1', $contexts);
        $this->assertContains('context2', $contexts);
    }
}
