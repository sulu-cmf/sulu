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

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecord;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class UserControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Contact
     */
    private $contact1;

    /**
     * @var Contact
     */
    private $contact2;

    /**
     * @var Contact
     */
    private $contact3;

    /**
     * @var Role
     */
    private $role1;

    /**
     * @var Role
     */
    private $role2;

    /**
     * @var User
     */
    private $user1;

    /**
     * @var User
     */
    private $user2;

    /**
     * @var User
     */
    private $user3;

    /**
     * @var Group
     */
    private $group1;

    /**
     * @var Group
     */
    private $group2;

    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var ObjectRepository<EventRecord>
     */
    private $eventRepository;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->eventRepository = $this->em->getRepository(EventRecord::class);
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

        $contact4 = new Contact();
        $contact4->setFirstName('Locked');
        $contact4->setLastName('User');
        $contact4->addEmail($email);
        $this->em->persist($contact4);
        $this->contact3 = $contact4;

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
        $user1 = new User();
        $user1->setUsername('admin');
        $user1->setEmail('admin@test.com');
        $user1->setPassword('securepassword');
        $user1->setSalt('salt');
        $user1->setLocale('de');
        $user1->setContact($contact2);
        $this->em->persist($user1);
        $this->user1 = $user1;

        // User 2
        $user2 = new User();
        $user2->setUsername('disabled');
        $user2->setEmail('disabled@test.com');
        $user2->setPassword('securepassword');
        $user2->setSalt('salt');
        $user2->setLocale('de');
        $user2->setContact($contact3);
        $user2->setEnabled(false);
        $this->em->persist($user2);
        $this->user2 = $user2;

        // User 3
        $user3 = new User();
        $user3->setUsername('locked');
        $user3->setEmail('locked@test.com');
        $user3->setPassword('securepassword');
        $user3->setSalt('salt');
        $user3->setLocale('de');
        $user3->setContact($contact4);
        $user3->setLocked(true);
        $this->em->persist($user3);
        $this->user3 = $user3;

        $this->em->flush();

        $userRole1 = new UserRole();
        $userRole1->setRole($role1);
        $userRole1->setUser($user1);
        $userRole1->setLocale(\json_encode(['de', 'en']));
        $this->em->persist($userRole1);

        $userRole2 = new UserRole();
        $userRole2->setRole($role2);
        $userRole2->setUser($user1);
        $userRole2->setLocale(\json_encode(['de', 'en']));
        $this->em->persist($userRole2);

        $userRole3 = new UserRole();
        $userRole3->setRole($role2);
        $userRole3->setUser($user1);
        $userRole3->setLocale(\json_encode(['de', 'en']));
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
        $this->em->clear();
    }

    public function testList()
    {
        $this->client->jsonRequest('GET', '/api/users?flat=true');

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, \count($response->_embedded->users));
        $this->assertEquals('admin', $response->_embedded->users[0]->username);
        $this->assertEquals('admin@test.com', $response->_embedded->users[0]->email);
        $this->assertObjectNotHasAttribute('password', $response->_embedded->users[0]);
        $this->assertEquals('de', $response->_embedded->users[0]->locale);
    }

    public function testGetById()
    {
        $this->client->jsonRequest('GET', '/api/users/' . $this->user1->getId());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('admin', $response->username);
        $this->assertEquals('admin@test.com', $response->email);
        $this->assertObjectNotHasAttribute('password', $response);
        $this->assertEquals('de', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('Max Muster', $response->fullName);
    }

    public function testGetByNotExistingId()
    {
        $this->client->jsonRequest('GET', '/api/users/1120');

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $this->assertStringContainsString('1120', $response->message);
    }

    public function testPost()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users?contactId=' . $this->contact1->getId(),
            [
                'username' => 'manager',
                'email' => 'manager@test.com',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'userRoles' => [
                    [
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => ['de', 'en'],
                    ],
                    [
                        'role' => [
                            'id' => $this->role2->getId(),
                        ],
                        'locales' => ['en'],
                    ],
                ],
                'userGroups' => [
                    [
                        'group' => [
                            'id' => $this->group1->getId(),
                        ],
                        'locales' => ['de', 'en'],
                    ],
                    [
                        'group' => [
                            'id' => $this->group2->getId(),
                        ],
                        'locales' => ['en'],
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

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

        $this->client->jsonRequest(
            'GET',
            '/api/users/' . $response->id
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        /** @var DomainEvent $event */
        $event = $this->eventRepository->findOneBy(['eventType' => 'created']);
        $this->assertSame((string) $response->id, $event->getResourceId());

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

    public function testPostWithEntireContactObject()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users',
            [
                'username' => 'manager',
                'email' => 'manager@test.com',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('manager@test.com', $response->email);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);

        $this->client->jsonRequest(
            'GET',
            '/api/users/' . $response->id
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('manager@test.com', $response->email);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);
    }

    public function testPostWithMissingUsername()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users',
            [
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'userRoles' => [
                    [
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => '["de"]',
                    ],
                    [
                        'role' => [
                            'id' => $this->role2->getId(),
                        ],
                        'locales' => '["de"]',
                    ],
                ],
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertStringContainsString('username', $response->message);
    }

    public function testPostWithMissingPassword()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users',
            [
                'username' => 'manager',
                'locale' => 'en',
                'userRoles' => [
                    [
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => '["de"]',
                    ],
                    [
                        'role' => [
                            'id' => $this->role2->getId(),
                        ],
                        'locales' => '["de"]',
                    ],
                ],
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertStringContainsString('password', $response->message);
    }

    public function testPostWithNotUniqueEmail()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users',
            [
                'username' => 'hikari',
                'email' => 'admin@test.com', //already used by admin
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
                'userRoles' => [
                    [
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => '["de"]',
                    ],
                ],
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertStringContainsString('email', \strtolower($response->message));
        $this->assertEquals(1004, $response->code);
    }

    public function testPostWithContactEmail()
    {
        // no user-email passed, but a unique contact-email
        // so the controller should use the contact-email as the user-email as well
        $this->client->jsonRequest(
            'POST',
            '/api/users',
            [
                'username' => 'hikari',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                    'emails' => [['email' => $this->contact1->getEmails()[0]->getEmail()]],
                ],
                'userRoles' => [
                    [
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => '["de"]',
                    ],
                ],
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('hikari', $response->username);
        $this->assertEquals('contact.unique@test.com', $response->email);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals($this->contact1->getEmails()[0]->getEmail(), $response->contact->emails[0]->email);
    }

    public function testDelete()
    {
        $this->client->jsonRequest('DELETE', '/api/users/' . $this->user2->getId());

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        /** @var DomainEvent $event */
        $event = $this->eventRepository->findOneBy(['eventType' => 'removed']);
        $this->assertSame((string) $this->user2->getId(), $event->getResourceId());

        $this->client->jsonRequest('GET', '/api/users/' . $this->user2->getId());

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testDeleteNotExisting()
    {
        $this->client->jsonRequest('DELETE', '/api/users/11235');

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPut()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            [
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
                'userRoles' => [
                    [
                        'id' => $this->user1->getId(),
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => ['de', 'en'],
                    ],
                    [
                        'id' => 2,
                        'role' => [
                            'id' => $this->role2->getId(),
                        ],
                        'locales' => ['en'],
                    ],
                ],
                'userGroups' => [
                    [
                        'group' => [
                            'id' => $this->group1->getId(),
                        ],
                        'locales' => ['de', 'en'],
                    ],
                    [
                        'group' => [
                            'id' => $this->group2->getId(),
                        ],
                        'locales' => ['en'],
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

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

        /** @var DomainEvent $event */
        $event = $this->eventRepository->findOneBy(['eventType' => 'modified']);
        $this->assertSame((string) $this->user1->getId(), $event->getResourceId());

        $this->client->jsonRequest(
            'GET',
            '/api/users/' . $this->user1->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());

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

    public function testPostNonUniqueUserame()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users',
            [
                'username' => 'admin',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertEquals(1001, $response->code);
        $this->assertEquals('The username "admin" is already assigned to another contact.', $response->detail);
    }

    public function testPutNonUniqueUsername()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users',
            [
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
            ]
        );

        $this->client->jsonRequest(
            'PUT',
            '/api/users/' . $this->user2->getId(),
            [
                'username' => 'admin',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertEquals(1001, $response->code);
        $this->assertEquals('The username "admin" is already assigned to another contact.', $response->detail);
    }

    public function testPatch()
    {
        $this->client->jsonRequest(
            'PATCH',
            '/api/users/' . $this->user1->getId(),
            [
                'locale' => 'en',
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('en', $response->locale);

        /** @var DomainEvent $event */
        $event = $this->eventRepository->findOneBy(['eventType' => 'modified']);
        $this->assertSame((string) $this->user1->getId(), $event->getResourceId());

        $this->client->jsonRequest(
            'PATCH',
            '/api/users/' . $this->user1->getId(),
            [
                'username' => 'newusername',
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('newusername', $response->username);

        $this->client->jsonRequest(
            'PATCH',
            '/api/users/' . $this->user1->getId(),
            [
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals($this->contact1->getId(), $response->contact->id);

        $this->client->jsonRequest(
            'GET',
            '/api/users/' . $this->user1->getId()
        );
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('en', $response->locale);
        $this->assertEquals('newusername', $response->username);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
    }

    public function testPatchNonUniqueUsername()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users',
            [
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
            ]
        );

        $this->client->jsonRequest(
            'PATCH',
            '/api/users/' . $this->user2->getId(),
            [
                'username' => 'admin',
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $this->assertEquals(1001, $response->code);
        $this->assertEquals('The username "admin" is already assigned to another contact.', $response->detail);
    }

    public function testPutWithMissingUsername()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            [
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
                'userRoles' => [
                    [
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => '["de"]',
                    ],
                    [
                        'role' => [
                            'id' => $this->role2->getId(),
                        ],
                        'locales' => '["de"]',
                    ],
                ],
            ]
        );
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertStringContainsString('username', $response->message);
    }

    public function testPutWithMissingPassword()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            [
                'username' => 'manager',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
                'userRoles' => [
                    [
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => ['de', 'en'],
                    ],
                    [
                        'role' => [
                            'id' => $this->role2->getId(),
                        ],
                        'locales' => ['en'],
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('manager', $response->username);

        $user = $this->getEntityManager()->find(User::class, $this->user1->getId());

        $this->assertEquals($this->user1->getSalt(), $user->getSalt());
        $this->assertEquals($this->user1->getPassword(), $user->getPassword());
    }

    public function testGetUserAndRolesByContact()
    {
        $this->client->jsonRequest(
            'GET',
            '/api/users?contactId=' . $this->contact2->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals($this->user1->getId(), $response->id);
        $this->assertEquals('admin', $response->username);
        $this->assertObjectNotHasAttribute('password', $response);

        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('Sulu', $response->userRoles[0]->role->system);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('Sulu', $response->userRoles[1]->role->system);
    }

    public function testGetUserAndRolesByContactNotExisting()
    {
        $this->client->jsonRequest(
            'GET',
            '/api/users?contactId=1234'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('{}', $this->client->getResponse()->getContent());
    }

    public function testGetUserAndRolesWithoutParam()
    {
        $this->client->jsonRequest(
            'GET',
            '/api/users'
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(4, \count($response->_embedded->users));
        $this->assertEquals('admin', $response->_embedded->users[0]->username);
        $this->assertObjectNotHasAttribute('password', $response->_embedded->users[0]);
        $this->assertEquals('de', $response->_embedded->users[0]->locale);
    }

    public function testCGetProperties()
    {
        $this->client->jsonRequest(
            'GET',
            '/api/users'
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $users = $response->_embedded->users;
        $user = $users[0];
        $contact = $user->contact;

        $this->assertObjectNotHasAttribute('account', $contact);
        $this->assertObjectNotHasAttribute('phones', $contact);
        $this->assertObjectNotHasAttribute('faxes', $contact);
        $this->assertObjectNotHasAttribute('position', $contact);
        $this->assertObjectNotHasAttribute('addresses', $contact);
        $this->assertObjectNotHasAttribute('notes', $contact);
        $this->assertObjectNotHasAttribute('tags', $contact);
        $this->assertObjectNotHasAttribute('medias', $contact);
        $this->assertObjectNotHasAttribute('categories', $contact);
        $this->assertObjectNotHasAttribute('urls', $contact);
        $this->assertObjectNotHasAttribute('bankAccounts', $contact);
    }

    public function testPutWithRemovedRoles()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            [
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
                'userRoles' => [
                    [
                        'id' => $this->user1->getId(),
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => ['de', 'en'],
                    ],
                    [
                        'id' => 2,
                        'role' => [
                            'id' => $this->role2->getId(),
                        ],
                        'locales' => ['en'],
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);

        $this->client->jsonRequest(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            [
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
                'userRoles' => [
                    [
                        'id' => $this->user1->getId(),
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => ['de', 'en'],
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);

        $this->assertEquals(1, \count($response->userRoles));
    }

    public function testPostWithEmptyPassword()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users',
            [
                'username' => 'manager',
                'password' => '',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
                'userRoles' => [
                    [
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => ['de', 'en'],
                    ],
                    [
                        'role' => [
                            'id' => $this->role2->getId(),
                        ],
                        'locales' => ['en'],
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1002, $response->code);
        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testPutWithoutPassword()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            [
                'username' => 'manager',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertObjectNotHasAttribute('password', $response);
    }

    public function testPutWithEmptyPassword()
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/users/' . $this->user1->getId(),
            [
                'username' => 'manager',
                'password' => '',
                'locale' => 'en',
                'contact' => [
                    'id' => $this->contact1->getId(),
                ],
                'userRoles' => [
                    [
                        'id' => $this->user1->getId(),
                        'role' => [
                            'id' => $this->role1->getId(),
                        ],
                        'locales' => ['de', 'en'],
                    ],
                    [
                        'id' => 2,
                        'role' => [
                            'id' => $this->role2->getId(),
                        ],
                        'locales' => ['en'],
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertObjectNotHasAttribute('password', $response);
        $this->assertEquals($this->contact1->getId(), $response->contact->id);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);

        $refreshedUser = $this->em->getRepository('SuluSecurityBundle:User')->find($this->user1->getId());
        $this->assertEquals($this->user1->getSalt(), $refreshedUser->getSalt());
    }

    public function testEnableUser()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users/' . $this->user2->getId() . '?action=enable'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var DomainEvent $event */
        $event = $this->eventRepository->findOneBy(['eventType' => 'enabled']);
        $this->assertSame((string) $this->user2->getId(), $event->getResourceId());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(true, $response->enabled);
    }

    public function testLockUser()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users/' . $this->user1->getId() . '?action=lock'
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(true, $response->locked);

        /** @var DomainEvent $event */
        $event = $this->eventRepository->findOneBy(['eventType' => 'locked']);
        $this->assertSame((string) $this->user1->getId(), $event->getResourceId());
    }

    public function testUnlockUser()
    {
        $this->client->jsonRequest(
            'POST',
            '/api/users/' . $this->user3->getId() . '?action=unlock'
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(false, $response->locked);

        /** @var DomainEvent $event */
        $event = $this->eventRepository->findOneBy(['eventType' => 'unlocked']);
        $this->assertSame((string) $this->user3->getId(), $event->getResourceId());
    }
}
