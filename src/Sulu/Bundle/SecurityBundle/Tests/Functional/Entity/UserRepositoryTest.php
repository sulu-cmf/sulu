<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class UserRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        // email
        $emailType = new EmailType();
        $emailType->setName('Private');
        $this->em->persist($emailType);

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);
        $this->em->persist($email);

        $email2 = new Email();
        $email2->setEmail('maria.musterfrau@muster.at');
        $email2->setEmailType($emailType);
        $this->em->persist($email2);

        // Contact

        $contact1 = new Contact();
        $contact1->setFirstName('Max');
        $contact1->setLastName('Muster');
        $contact1->addEmail($email);
        $this->em->persist($contact1);

        $contact2 = new Contact();
        $contact2->setFirstName('Maria');
        $contact2->setLastName('Musterfrau');
        $contact2->addEmail($email2);
        $this->em->persist($contact2);

        $this->em->flush();

        $role1 = new Role();
        $role1->setName('Role1');
        $role1->setSystem('Sulu');
        $this->em->persist($role1);

        $role2 = new Role();
        $role2->setName('Role2');
        $role2->setSystem('Test');
        $this->em->persist($role2);

        // User 1
        $user = new User();
        $user->setUsername('admin');
        $user->setEmail('user1@test.com');
        $user->setPassword('securepassword');
        $user->setPasswordResetToken('mySuperSecretToken');
        $user->setSalt('salt');
        $user->setLocale('de');
        $user->setContact($contact1);
        $this->em->persist($user);

        // User 2
        $user2 = new User();
        $user2->setUsername('test');
        $user2->setEmail('user2@test.com');
        $user2->setPassword('securepassword');
        $user2->setSalt('salt');
        $user2->setLocale('de');
        $user2->setContact($contact2);
        $this->em->persist($user2);

        $this->em->flush();

        $userRole1 = new UserRole();
        $userRole1->setRole($role1);
        $userRole1->setUser($user);
        $userRole1->setLocale(\json_encode(['de', 'en']));
        $this->em->persist($userRole1);

        $userRole2 = new UserRole();
        $userRole2->setRole($role2);
        $userRole2->setUser($user2);
        $userRole2->setLocale(\json_encode(['de', 'en']));
        $this->em->persist($userRole2);

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

        $this->em->flush();
    }

    public function testFindUsersById(): void
    {
        $user1 = $this->prepareUser('Sulu1', 'max', 'max');
        $user2 = $this->prepareUser('Sulu2', 'erika', 'erika');
        $user3 = $this->prepareUser('Sulu3', 'john', 'john');

        $userRepository = $this->client->getContainer()->get('sulu_security.user_repository');

        $users = $userRepository->findUsersById([$user1->getId(), $user2->getId()]);

        $this->assertCount(2, $users);

        $userIds = \array_map(function($user) {
            return $user->getId();
        }, $users);

        $this->assertContains($user1->getId(), $userIds);
        $this->assertContains($user2->getId(), $userIds);
        $this->assertNotContains($user3->getId(), $userIds);
    }

    public function testFindUserByEmail(): void
    {
        $this->prepareUser('Sulu', 'sulu', 'sulu');

        /** @var UserRepository $userRepository */
        $userRepository = $this->client->getContainer()->get('sulu_security.user_repository');

        $user = $userRepository->findUserByEmail('user2@test.com');

        $this->assertEquals('user2@test.com', $user->getEmail());
        $this->assertEquals('test', $user->getUserIdentifier());
    }

    public function testFindUserWithSecurityByIdentifier(): void
    {
        $this->prepareUser('Sulu', 'sulu', 'sulu');

        /** @var UserRepository $userRepository */
        $userRepository = $this->client->getContainer()->get('sulu_security.user_repository');

        $userByMail = $userRepository->findUserByIdentifier('user2@test.com');
        $userByUsername = $userRepository->findUserByIdentifier('test');

        $this->assertEquals('user2@test.com', $userByMail->getEmail());
        $this->assertEquals('test', $userByMail->getUserIdentifier());
        $this->assertEquals('user2@test.com', $userByUsername->getEmail());
        $this->assertEquals('test', $userByUsername->getUserIdentifier());
    }

    public function testFindUserByToken(): void
    {
        $this->prepareUser('Sulu', 'sulu', 'sulu');

        /** @var UserRepository $userRepository */
        $userRepository = $this->client->getContainer()->get('sulu_security.user_repository');

        $user = $userRepository->findUserByToken('mySuperSecretToken');

        $this->assertEquals('user1@test.com', $user->getEmail());
        $this->assertEquals('admin', $user->getUserIdentifier());
    }

    public function testFindUserBySystem(): void
    {
        $this->prepareUser('Sulu Role 2', 'sulu', 'sulu');
        $this->prepareUser('Client Role', 'client', 'client', true, false, 'Client');

        /** @var UserRepository $userRepository */
        $userRepository = $this->client->getContainer()->get('sulu_security.user_repository');

        /** @var User[] $suluUsers */
        $suluUsers = $userRepository->findUserBySystem('Sulu');
        $this->assertCount(2, $suluUsers);
        $this->assertTrue('admin' === $suluUsers[0]->getUsername() || 'admin' === $suluUsers[1]->getUsername());
        $this->assertTrue('sulu' === $suluUsers[0]->getUsername() || 'sulu' === $suluUsers[1]->getUsername());

        /** @var User[] $clientUsers */
        $clientUsers = $userRepository->findUserBySystem('Client');
        $this->assertCount(1, $clientUsers);
        $this->assertEquals('client', $clientUsers[0]->getUsername());
    }

    private function prepareUser($roleName, $username, $password, $enabled = true, $locked = false, $system = 'Sulu')
    {
        $emailType = new EmailType();
        $emailType->setName('Private');
        $this->em->persist($emailType);

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);
        $this->em->persist($email);

        $contact1 = new Contact();
        $contact1->setFirstName('Max');
        $contact1->setLastName('Muster');
        $contact1->addEmail($email);
        $this->em->persist($contact1);

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($password);
        $user->setSalt('salt');
        $user->setLocale('de');
        $user->setContact($contact1);
        $user->setEnabled($enabled);
        $user->setLocked($locked);
        $this->em->persist($user);

        $role = new Role();
        $role->setName($roleName);
        $role->setSystem($system);
        $this->em->persist($role);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale('');
        $this->em->persist($userRole);

        $this->em->flush();

        return $user;
    }
}
