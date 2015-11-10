<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\SecurityType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class RoleControllerTest extends SuluTestCase
{
    /**
     * @var SecurityType
     */
    protected $securityType1;

    /**
     * @var SecurityType
     */
    protected $securityType2;

    public function setUp()
    {
        $this->em = $this->db('ORM')->getOm();
        $this->purgeDatabase();

        $this->securityType1 = new SecurityType();
        $this->securityType1->setName('Security Type 1');
        $this->em->persist($this->securityType1);

        $this->securityType2 = new SecurityType();
        $this->securityType2->setName('Security Type 2');
        $this->em->persist($this->securityType2);

        $role = new Role();
        $role->setName('Sulu Administrator');
        $role->setSystem('Sulu');
        $role->setSecurityType($this->securityType1);
        $this->em->persist($role);
        $this->role1 = $role;

        $role2 = new Role();
        $role2->setName('Sulu Editor');
        $role2->setSystem('Sulu');
        $this->em->persist($role2);
        $this->role2 = $role2;

        $this->em->flush();

        $permission1 = new Permission();
        $permission1->setRole($role);
        $permission1->setContext('context1');
        $permission1->setPermissions(15);
        $this->em->persist($permission1);
        $this->permission1 = $permission1;

        $permission2 = new Permission();
        $permission2->setRole($role);
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
    }

    public function testList()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/roles?flat=true');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, count($response->_embedded->roles));
        $this->assertEquals('Sulu Administrator', $response->_embedded->roles[0]->name);
        $this->assertEquals('Sulu', $response->_embedded->roles[0]->system);
    }

    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/roles/' . $this->role1->getId());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu Administrator', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals(2, count($response->permissions));

        $permissions = $response->permissions;
        usort($permissions, function ($a, $b) {
            return ($a->id < $b->id) ? -1 : 1;
        });

        $this->assertEquals('context1', $permissions[0]->context);
        $this->assertEquals(false, $permissions[0]->permissions->view);
        $this->assertEquals(false, $permissions[0]->permissions->add);
        $this->assertEquals(false, $permissions[0]->permissions->edit);
        $this->assertEquals(true, $permissions[0]->permissions->delete);
        $this->assertEquals(true, $permissions[0]->permissions->archive);
        $this->assertEquals(true, $permissions[0]->permissions->live);
        $this->assertEquals(true, $permissions[0]->permissions->security);
        $this->assertEquals('context2', $permissions[1]->context);
        $this->assertEquals(false, $permissions[1]->permissions->view);
        $this->assertEquals(false, $permissions[1]->permissions->add);
        $this->assertEquals(true, $permissions[1]->permissions->edit);
        $this->assertEquals(false, $permissions[1]->permissions->delete);
        $this->assertEquals(false, $permissions[1]->permissions->archive);
        $this->assertEquals(false, $permissions[1]->permissions->live);
        $this->assertEquals(true, $permissions[1]->permissions->security);

        $this->assertEquals('Security Type 1', $response->securityType->name);
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/roles',
            [
                'name' => 'Portal Manager',
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
                'securityType' => [
                    'id' => $this->securityType2->getId(),
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals(2, count($response->permissions));

        $permissions = $response->permissions;
        usort($permissions, function ($a, $b) {
            return ($a->id < $b->id) ? -1 : 1;
        });
        $this->assertEquals('portal1', $permissions[0]->context);
        $this->assertEquals(false, $permissions[0]->permissions->view);
        $this->assertEquals(false, $permissions[0]->permissions->add);
        $this->assertEquals(true, $permissions[0]->permissions->edit);
        $this->assertEquals(true, $permissions[0]->permissions->delete);
        $this->assertEquals(false, $permissions[0]->permissions->archive);
        $this->assertEquals(true, $permissions[0]->permissions->live);
        $this->assertEquals(false, $permissions[0]->permissions->security);
        $this->assertEquals('portal2', $permissions[1]->context);
        $this->assertEquals(false, $permissions[1]->permissions->view);
        $this->assertEquals(false, $permissions[1]->permissions->add);
        $this->assertEquals(false, $permissions[1]->permissions->edit);
        $this->assertEquals(true, $permissions[1]->permissions->delete);
        $this->assertEquals(false, $permissions[1]->permissions->archive);
        $this->assertEquals(false, $permissions[1]->permissions->live);
        $this->assertEquals(false, $permissions[1]->permissions->security);

        $this->assertEquals('Security Type 2', $response->securityType->name);

        $client->request(
            'GET',
            '/api/roles/' . $response->id
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);

        $this->assertEquals(2, count($response->permissions));
        $permissions = $response->permissions;
        usort($permissions, function ($a, $b) {
            return ($a->id < $b->id) ? -1 : 1;
        });
        $this->assertEquals('portal1', $permissions[0]->context);
        $this->assertEquals(false, $permissions[0]->permissions->view);
        $this->assertEquals(false, $permissions[0]->permissions->add);
        $this->assertEquals(true, $permissions[0]->permissions->edit);
        $this->assertEquals(true, $permissions[0]->permissions->delete);
        $this->assertEquals(false, $permissions[0]->permissions->archive);
        $this->assertEquals(true, $permissions[0]->permissions->live);
        $this->assertEquals(false, $permissions[0]->permissions->security);
        $this->assertEquals('portal2', $permissions[1]->context);
        $this->assertEquals(false, $permissions[1]->permissions->view);
        $this->assertEquals(false, $permissions[1]->permissions->add);
        $this->assertEquals(false, $permissions[1]->permissions->edit);
        $this->assertEquals(true, $permissions[1]->permissions->delete);
        $this->assertEquals(false, $permissions[1]->permissions->archive);
        $this->assertEquals(false, $permissions[1]->permissions->live);
        $this->assertEquals(false, $permissions[1]->permissions->security);
        $this->assertEquals('Security Type 2', $response->securityType->name);
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/roles/' . $this->role1->getId(),
            [
                'name' => 'Portal Manager',
                'system' => 'Sulu',
                'permissions' => [
                    [
                        'id' => $this->permission1->getId(),
                        'context' => 'portal1',
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
                'securityType' => [
                    'id' => $this->securityType2,
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);

        $permissions = $response->permissions;
        usort($permissions, function ($a, $b) {
            return ($a->id < $b->id) ? -1 : 1;
        });

        $this->assertEquals('portal1', $permissions[0]->context);
        $this->assertEquals(true, $permissions[0]->permissions->view);
        $this->assertEquals(true, $permissions[0]->permissions->add);
        $this->assertEquals(true, $permissions[0]->permissions->edit);
        $this->assertEquals(true, $permissions[0]->permissions->delete);
        $this->assertEquals(false, $permissions[0]->permissions->archive);
        $this->assertEquals(false, $permissions[0]->permissions->live);
        $this->assertEquals(false, $permissions[0]->permissions->security);
        $this->assertEquals('portal2', $permissions[1]->context);
        $this->assertEquals(false, $permissions[1]->permissions->view);
        $this->assertEquals(false, $permissions[1]->permissions->add);
        $this->assertEquals(false, $permissions[1]->permissions->edit);
        $this->assertEquals(false, $permissions[1]->permissions->delete);
        $this->assertEquals(true, $permissions[1]->permissions->archive);
        $this->assertEquals(true, $permissions[1]->permissions->live);
        $this->assertEquals(true, $permissions[1]->permissions->security);
        $this->assertEquals('portal3', $permissions[2]->context);
        $this->assertEquals(false, $permissions[2]->permissions->view);
        $this->assertEquals(false, $permissions[2]->permissions->add);
        $this->assertEquals(false, $permissions[2]->permissions->edit);
        $this->assertEquals(false, $permissions[2]->permissions->delete);
        $this->assertEquals(true, $permissions[2]->permissions->archive);
        $this->assertEquals(true, $permissions[2]->permissions->live);
        $this->assertEquals(true, $permissions[2]->permissions->security);

        $this->assertEquals('Security Type 2', $response->securityType->name);

        $client->request(
            'GET',
            '/api/roles/' . $this->role1->getId()
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);

        $permissions = $response->permissions;
        usort($permissions, function ($a, $b) {
            return ($a->id < $b->id) ? -1 : 1;
        });
        $this->assertEquals(true, $permissions[0]->permissions->view);
        $this->assertEquals(true, $permissions[0]->permissions->add);
        $this->assertEquals(true, $permissions[0]->permissions->edit);
        $this->assertEquals(true, $permissions[0]->permissions->delete);
        $this->assertEquals(false, $permissions[0]->permissions->archive);
        $this->assertEquals(false, $permissions[0]->permissions->live);
        $this->assertEquals(false, $permissions[0]->permissions->security);
        $this->assertEquals('portal2', $permissions[1]->context);
        $this->assertEquals(false, $permissions[1]->permissions->view);
        $this->assertEquals(false, $permissions[1]->permissions->add);
        $this->assertEquals(false, $permissions[1]->permissions->edit);
        $this->assertEquals(false, $permissions[1]->permissions->delete);
        $this->assertEquals(true, $permissions[1]->permissions->archive);
        $this->assertEquals(true, $permissions[1]->permissions->live);
        $this->assertEquals(true, $permissions[1]->permissions->security);
        $this->assertEquals('portal3', $permissions[2]->context);
        $this->assertEquals(false, $permissions[2]->permissions->view);
        $this->assertEquals(false, $permissions[2]->permissions->add);
        $this->assertEquals(false, $permissions[2]->permissions->edit);
        $this->assertEquals(false, $permissions[2]->permissions->delete);
        $this->assertEquals(true, $permissions[2]->permissions->archive);
        $this->assertEquals(true, $permissions[2]->permissions->live);
        $this->assertEquals(true, $permissions[2]->permissions->security);

        $this->assertEquals('Security Type 2', $response->securityType->name);
    }

    public function testPutRemoveSecurityType()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/roles/' . $this->role1->getId(),
            [
                'name' => 'Portal Manager',
                'system' => 'Sulu',
                'permissions' => [
                    [
                        'id' => $this->permission1->getId(),
                        'context' => 'portal1',
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
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);

        $permissions = $response->permissions;
        usort($permissions, function ($a, $b) {
            return ($a->id < $b->id) ? -1 : 1;
        });
        $this->assertEquals('portal1', $permissions[0]->context);
        $this->assertEquals(true, $permissions[0]->permissions->view);
        $this->assertEquals(true, $permissions[0]->permissions->add);
        $this->assertEquals(true, $permissions[0]->permissions->edit);
        $this->assertEquals(true, $permissions[0]->permissions->delete);
        $this->assertEquals(false, $permissions[0]->permissions->archive);
        $this->assertEquals(false, $permissions[0]->permissions->live);
        $this->assertEquals(false, $permissions[0]->permissions->security);
        $this->assertEquals('portal2', $permissions[1]->context);
        $this->assertEquals(false, $permissions[1]->permissions->view);
        $this->assertEquals(false, $permissions[1]->permissions->add);
        $this->assertEquals(false, $permissions[1]->permissions->edit);
        $this->assertEquals(false, $permissions[1]->permissions->delete);
        $this->assertEquals(true, $permissions[1]->permissions->archive);
        $this->assertEquals(true, $permissions[1]->permissions->live);
        $this->assertEquals(true, $permissions[1]->permissions->security);
        $this->assertEquals('portal3', $permissions[2]->context);
        $this->assertEquals(false, $permissions[2]->permissions->view);
        $this->assertEquals(false, $permissions[2]->permissions->add);
        $this->assertEquals(false, $permissions[2]->permissions->edit);
        $this->assertEquals(false, $permissions[2]->permissions->delete);
        $this->assertEquals(true, $permissions[2]->permissions->archive);
        $this->assertEquals(true, $permissions[2]->permissions->live);
        $this->assertEquals(true, $permissions[2]->permissions->security);

        $this->assertObjectNotHasAttribute('securityType', $response);

        $client->request(
            'GET',
            '/api/roles/' . $this->role1->getId()
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);

        $permissions = $response->permissions;
        usort($permissions, function ($a, $b) {
            return ($a->id < $b->id) ? -1 : 1;
        });
        $this->assertEquals(true, $permissions[0]->permissions->view);
        $this->assertEquals(true, $permissions[0]->permissions->add);
        $this->assertEquals(true, $permissions[0]->permissions->edit);
        $this->assertEquals(true, $permissions[0]->permissions->delete);
        $this->assertEquals(false, $permissions[0]->permissions->archive);
        $this->assertEquals(false, $permissions[0]->permissions->live);
        $this->assertEquals(false, $permissions[0]->permissions->security);
        $this->assertEquals('portal2', $permissions[1]->context);
        $this->assertEquals(false, $permissions[1]->permissions->view);
        $this->assertEquals(false, $permissions[1]->permissions->add);
        $this->assertEquals(false, $permissions[1]->permissions->edit);
        $this->assertEquals(false, $permissions[1]->permissions->delete);
        $this->assertEquals(true, $permissions[1]->permissions->archive);
        $this->assertEquals(true, $permissions[1]->permissions->live);
        $this->assertEquals(true, $permissions[1]->permissions->security);
        $this->assertEquals('portal3', $permissions[2]->context);
        $this->assertEquals(false, $permissions[2]->permissions->view);
        $this->assertEquals(false, $permissions[2]->permissions->add);
        $this->assertEquals(false, $permissions[2]->permissions->edit);
        $this->assertEquals(false, $permissions[2]->permissions->delete);
        $this->assertEquals(true, $permissions[2]->permissions->archive);
        $this->assertEquals(true, $permissions[2]->permissions->live);
        $this->assertEquals(true, $permissions[2]->permissions->security);

        $this->assertObjectNotHasAttribute('securityType', $response);
    }

    public function testPutNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/roles/11230',
            [
                'name' => 'Portal Manager',
                'system' => 'Sulu',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('11230', $response->message);
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/roles'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(2, count($response->_embedded->roles));

        $client->request(
            'DELETE',
            '/api/roles/' . $this->role1->getId()
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/roles'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, count($response->_embedded->roles));
    }

    public function testDeleteNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/api/roles/11230'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('11230', $response->message);
    }

    public function testGetAllRoles()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/roles');

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(2, count($response->_embedded->roles));

        $this->assertEquals('Sulu Administrator', $response->_embedded->roles[0]->name);
        $this->assertEquals('Sulu Editor', $response->_embedded->roles[1]->name);

        $this->assertEquals('Sulu', $response->_embedded->roles[0]->system);
        $this->assertEquals('Sulu', $response->_embedded->roles[1]->system);

        $this->assertEquals('context1', $response->_embedded->roles[0]->permissions[0]->context);
        $this->assertEquals('context2', $response->_embedded->roles[1]->permissions[1]->context);
    }
}
