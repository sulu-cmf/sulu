<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class UserControllerTest extends SuluTestCase
{
    public function setUp()
    {
        $this->em = $this->db('ORM')->getOm();
        $this->purgeDatabase();

        $emailType = new EmailType();
        $emailType->setName('Private');
        $this->em->persist($emailType);

        $email1 = new Email();
        $email1->setEmail('contact.unique@test.com');
        $email1->setEmailType($emailType);
        $this->em->persist($email1);

        // Contact
        $contact1 = new Contact();
        $contact1->setFirstName('Max');
        $contact1->setLastName('Mustermann');
        $contact1->addEmail($email1);
        $this->em->persist($contact1);
        $this->contact1 = $contact1;

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);
        $this->em->persist($email);

        $contact2 = new Contact();
        $contact2->setFirstName('Max');
        $contact2->setLastName('Muster');
        $contact2->addEmail($email);
        $this->em->persist($contact2);
        $this->contact2 = $contact2;

        $contact3 = new Contact();
        $contact3->setFirstName('Disabled');
        $contact3->setLastName('User');
        $contact3->addEmail($email);
        $this->em->persist($contact3);
        $this->contact3 = $contact3;

        $this->em->flush();

        $role1 = new Role();
        $role1->setName('Role1');
        $role1->setSystem('Sulu');
        $this->em->persist($role1);
        $this->role1 = $role1;

        $role2 = new Role();
        $role2->setName('Role2');
        $role2->setSystem('Sulu');
        $this->em->persist($role2);
        $this->role2 = $role2;

        // User 1
        $user = new User();
        $user->setUsername('admin');
        $user->setEmail('admin@test.com');
        $user->setPassword('securepassword');
        $user->setSalt('salt');
        $user->setLocale('de');
        $user->setContact($contact2);
        $this->em->persist($user);
        $this->user1 = $user;

        // User 2
        $user1 = new User();
        $user1->setUsername('disabled');
        $user1->setEmail('disabled@test.com');
        $user1->setPassword('securepassword');
        $user1->setSalt('salt');
        $user1->setLocale('de');
        $user1->setContact($contact3);
        $user1->setEnabled(false);
        $this->em->persist($user1);
        $this->user2 = $user1;

        $this->em->flush();

        $userRole1 = new UserRole();
        $userRole1->setRole($role1);
        $userRole1->setUser($user);
        $userRole1->setLocale(json_encode(array('de', 'en')));
        $this->em->persist($userRole1);

        $userRole2 = new UserRole();
        $userRole2->setRole($role2);
        $userRole2->setUser($user);
        $userRole2->setLocale(json_encode(array('de', 'en')));
        $this->em->persist($userRole2);

        $userRole3 = new UserRole();
        $userRole3->setRole($role2);
        $userRole3->setUser($user);
        $userRole3->setLocale(json_encode(array('de', 'en')));
        $this->em->persist($userRole3);

        $permission1 = new Permission();
        $permission1->setPermissions(122);
        $permission1->setRole($role1);
        $permission1->setContext('Context 1');
        $this->em->persist($permission1);

        $permission2 = new Permission();
        $permission2->setPermissions(122);
        $permission2->setRole($role2);
        $permission2->setContext('Context 2');
        $this->em->persist($permission2);

        // user groups
        $group1 = new Group();
        $group1->setName('Group1');
        $group1->setLft(0);
        $group1->setRgt(0);
        $group1->setDepth(0);
        $this->em->persist($group1);
        $this->group1 = $group1;

        $group2 = new Group();
        $group2->setName('Group2');
        $group2->setLft(0);
        $group2->setRgt(0);
        $group2->setDepth(0);
        $this->em->persist($group2);
        $this->group2 = $group2;

        $this->em->flush();
    }

    public function testList()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/users?flat=true');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, count($response->_embedded->users));
        $this->assertEquals('admin', $response->_embedded->users[0]->username);
        $this->assertEquals('admin@test.com', $response->_embedded->users[0]->email);
        $this->assertEquals('securepassword', $response->_embedded->users[0]->password);
        $this->assertEquals('de', $response->_embedded->users[0]->locale);
    }

    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/users/' . $this->user1->getId());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('admin', $response->username);
        $this->assertEquals('admin@test.com', $response->email);
        $this->assertEquals('securepassword', $response->password);
        $this->assertEquals('de', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('Max Muster', $response->fullName);
    }

    public function testGetByNotExistingId()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/users/1120');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('1120', $response->message);
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/users',
            array(
                'username' => 'manager',
                'email' => 'manager@test.com',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
                'userRoles' => array(
                    array(
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                    array(
                        'role' => array(
                            'id' => $this->role2->getId(),
                        ),
                        'locales' => array('en'),
                    ),
                ),
                'userGroups' => array(
                    array(
                        'group' => array(
                            'id' => $this->group1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                    array(
                        'group' => array(
                            'id' => $this->group2->getId(),
                        ),
                        'locales' => array('en'),
                    ),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('manager@test.com', $response->email);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);
        $this->assertEquals('Group1', $response->userGroups[0]->group->name);
        $this->assertEquals('de', $response->userGroups[0]->locales[0]);
        $this->assertEquals('en', $response->userGroups[0]->locales[1]);
        $this->assertEquals('Group2', $response->userGroups[1]->group->name);
        $this->assertEquals('en', $response->userGroups[1]->locales[0]);

        $client->request(
            'GET',
            '/api/users/' . $response->id
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('manager@test.com', $response->email);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);
        $this->assertEquals('Group1', $response->userGroups[0]->group->name);
        $this->assertEquals('de', $response->userGroups[0]->locales[0]);
        $this->assertEquals('en', $response->userGroups[0]->locales[1]);
        $this->assertEquals('Group2', $response->userGroups[1]->group->name);
        $this->assertEquals('en', $response->userGroups[1]->locales[0]);
    }

    public function testPostWithMissingArgument()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/users',
            array(
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'userRoles' => array(
                    array(
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => '["de"]',
                    ),
                    array(
                        'role' => array(
                            'id' => $this->role2->getId(),
                        ),
                        'locales' => '["de"]',
                    ),
                ),
            )
        );
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertContains('username', $response->message);
    }

    public function testPostWithNotUniqueEmail()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/users',
            array(
                'username' => 'hikari',
                'email' => 'admin@test.com', //already used by admin
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
                'userRoles' => array(
                    array(
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => '["de"]',
                    ),
                ),
            )
        );
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(409, $client->getResponse()->getStatusCode());
        $this->assertContains('email', strtolower($response->message));
        $this->assertEquals(1004, $response->code);
    }

    public function testPostWithContactEmail()
    {
        $client = $this->createAuthenticatedClient();

        // no user-email passed, but a unique contact-email
        // so the controller should use the contact-email as the user-email as well
        $client->request(
            'POST',
            '/api/users',
            array(
                'username' => 'hikari',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                    'emails' => array(array('email' => $this->contact1->getEmails()[0]->getEmail())),
                ),
                'userRoles' => array(
                    array(
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => '["de"]',
                    ),
                ),
            )
        );
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('hikari', $response->username);
        $this->assertEquals('contact.unique@test.com', $response->email);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals($this->contact1->getEmails()[0]->getEmail(), $response->contact->emails[0]->email);
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/users/' . $this->user1->getId());

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/users/' . $this->user1->getId());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/users/11235');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            array(
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
                'userRoles' => array(
                    array(
                        'id' => $this->user1->getId(),
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                    array(
                        'id' => 2,
                        'role' => array(
                            'id' => $this->role2->getId(),
                        ),
                        'locales' => array('en'),
                    ),
                ),
                'userGroups' => array(
                    array(
                        'group' => array(
                            'id' => $this->group1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                    array(
                        'group' => array(
                            'id' => $this->group2->getId(),
                        ),
                        'locales' => array('en'),
                    ),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);

        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);

        $this->assertEquals('Group1', $response->userGroups[0]->group->name);
        $this->assertEquals('de', $response->userGroups[0]->locales[0]);
        $this->assertEquals('en', $response->userGroups[0]->locales[1]);
        $this->assertEquals('Group2', $response->userGroups[1]->group->name);
        $this->assertEquals('en', $response->userGroups[1]->locales[0]);

        $client->request(
            'GET',
            '/api/users/' . $this->user1->getId()
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);

        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);

        $this->assertEquals('Group1', $response->userGroups[0]->group->name);
        $this->assertEquals('de', $response->userGroups[0]->locales[0]);
        $this->assertEquals('en', $response->userGroups[0]->locales[1]);
        $this->assertEquals('Group2', $response->userGroups[1]->group->name);
        $this->assertEquals('en', $response->userGroups[1]->locales[0]);
    }

    public function testPostNonUniqueName()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/users',
            array(
                'username' => 'admin',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(409, $client->getResponse()->getStatusCode());
        $this->assertEquals('admin', $response->username);
        $this->assertEquals(1001, $response->code);
    }

    public function testPutNonUniqueName()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/users',
            array(
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
            )
        );

        $client->request(
            'PUT',
            '/api/users/' . $this->user2->getId(),
            array(
                'username' => 'admin',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(409, $client->getResponse()->getStatusCode());
        $this->assertEquals('admin', $response->username);
        $this->assertEquals(1001, $response->code);
    }

    public function testPatch()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PATCH',
            '/api/users/' . $this->user1->getId(),
            array(
                'locale' => 'en',
            )
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('en', $response->locale);

        $client->request(
            'PATCH',
            '/api/users/' . $this->user1->getId(),
            array(
                'username' => 'newusername',
            )
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('newusername', $response->username);

        $client->request(
            'PATCH',
            '/api/users/' . $this->user1->getId(),
            array(
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
            )
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals($this->contact1->getId(), $response->contact->id);

        $client->request(
            'GET',
            '/api/users/' . $this->user1->getId()
        );
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('en', $response->locale);
        $this->assertEquals('newusername', $response->username);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
    }

    public function testPatchNonUniqueName()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/users',
            array(
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
            )
        );

        $client->request(
            'PATCH',
            '/api/users/' . $this->user2->getId(),
            array(
                'username' => 'admin',
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(409, $client->getResponse()->getStatusCode());
        $this->assertEquals('admin', $response->username);
        $this->assertEquals(1001, $response->code);
    }

    public function testPutWithMissingArgument()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            array(
                'username' => 'manager',
                'locale' => 'en',
                'userRoles' => array(
                    array(
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                    array(
                        'role' => array(
                            'id' => $this->role2->getId(),
                        ),
                        'locales' => array('en'),
                    ),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertContains('password', $response->message);
    }

    public function testGetUserAndRolesByContact()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/users?contactId=' . $this->contact2->getId()
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($this->user1->getId(), $response->_embedded->users[0]->id);
        $this->assertEquals('admin', $response->_embedded->users[0]->username);
        $this->assertEquals('securepassword', $response->_embedded->users[0]->password);

        $this->assertEquals('Role1', $response->_embedded->users[0]->userRoles[0]->role->name);
        $this->assertEquals('Sulu', $response->_embedded->users[0]->userRoles[0]->role->system);
        $this->assertEquals('Role2', $response->_embedded->users[0]->userRoles[1]->role->name);
        $this->assertEquals('Sulu', $response->_embedded->users[0]->userRoles[1]->role->system);
    }

    public function testGetUserAndRolesByContactNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/users?contactId=1234'
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }

    public function testGetUserAndRolesWithoutParam()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/users'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, count($response->_embedded->users));
        $this->assertEquals('admin', $response->_embedded->users[0]->username);
        $this->assertEquals('securepassword', $response->_embedded->users[0]->password);
        $this->assertEquals('de', $response->_embedded->users[0]->locale);
    }

    public function testPutWithRemovedRoles()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            array(
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
                'userRoles' => array(
                    array(
                        'id' => $this->user1->getId(),
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                    array(
                        'id' => 2,
                        'role' => array(
                            'id' => $this->role2->getId(),
                        ),
                        'locales' => array('en'),
                    ),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);

        $client->request(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            array(
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
                'userRoles' => array(
                    array(
                        'id' => $this->user1->getId(),
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);

        $this->assertEquals(1, sizeof($response->userRoles));
    }

    public function testPostWithoutPassword()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/users',
            array(
                'username' => 'manager',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
                'userRoles' => array(
                    array(
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                    array(
                        'role' => array(
                            'id' => $this->role2->getId(),
                        ),
                        'locales' => array('en'),
                    ),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(0, $response->code);
        $this->assertEquals('The "SuluSecurityBundle:User"-entity requires a "password"-argument', $response->message);
    }

    public function testPostWithEmptyPassword()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/users',
            array(
                'username' => 'manager',
                'password' => '',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
                'userRoles' => array(
                    array(
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                    array(
                        'role' => array(
                            'id' => $this->role2->getId(),
                        ),
                        'locales' => array('en'),
                    ),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1002, $response->code);
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testPutWithoutPassword()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            array(
                'username' => 'manager',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
                'userRoles' => array(
                    array(
                        'id' => $this->user1->getId(),
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                    array(
                        'id' => 2,
                        'role' => array(
                            'id' => $this->role2->getId(),
                        ),
                        'locales' => array('en'),
                    ),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(0, $response->code);
        $this->assertEquals('The "SuluSecurityBundle:User"-entity requires a "password"-argument', $response->message);
    }

    public function testPutWithEmptyPassword()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            array(
                'username' => 'manager',
                'password' => '',
                'locale' => 'en',
                'contact' => array(
                    'id' => $this->contact1->getId(),
                ),
                'userRoles' => array(
                    array(
                        'id' => $this->user1->getId(),
                        'role' => array(
                            'id' => $this->role1->getId(),
                        ),
                        'locales' => array('de', 'en'),
                    ),
                    array(
                        'id' => 2,
                        'role' => array(
                            'id' => $this->role2->getId(),
                        ),
                        'locales' => array('en'),
                    ),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('securepassword', $response->password);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);
    }

    public function testEnableUser()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/users/' . $this->user2->getId() . '?action=enable'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(true, $response->enabled);
    }
}
