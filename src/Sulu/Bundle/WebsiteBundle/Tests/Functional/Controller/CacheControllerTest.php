<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Controller;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class CacheControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = static::createAuthenticatedClient([], [
            'PHP_AUTH_USER' => 'cache-user',
            'PHP_AUTH_PW' => 'cache-user',
        ]);
        static::purgeDatabase();
        $entityManager = static::getEntityManager();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername('cache-user');
        $user->setContact($contact);
        $user->setLocale('en');
        $user->setSalt('');

        $passwordHasherFactory = self::getContainer()->get('security.password_hasher_factory');
        $hasher = $passwordHasherFactory->getPasswordHasher($user);
        $password = $hasher->hash('cache-user');

        $user->setPassword($password);
        $entityManager->persist($user);

        $role = new Role();
        $role->setName('Cache User Role');
        $role->setSystem(Admin::SULU_ADMIN_SECURITY_SYSTEM);
        $entityManager->persist($role);

        $suluWebspacePermission = new Permission();
        $suluWebspacePermission->setContext(PageAdmin::getPageSecurityContext('sulu_io'));
        $suluWebspacePermission->setPermissions(127);
        $suluWebspacePermission->setRole($role);
        $role->addPermission($suluWebspacePermission);
        $entityManager->persist($suluWebspacePermission);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setLocale('["en","de"]');
        $userRole->setUser($user);
        $user->addUserRole($userRole);
        $entityManager->persist($userRole);

        $entityManager->flush();
    }

    public function testClearWebspaceWithPermissions(): void
    {
        $this->client->request('DELETE', '/sulu_website/cache?webspaceKey=sulu_io');

        static::assertHttpStatusCode(204, $this->client->getResponse());
    }

    public function testClearWebspaceWithoutPermissions(): void
    {
        $this->client->request('DELETE', '/sulu_website/cache?webspaceKey=test_io');

        static::assertHttpStatusCode(403, $this->client->getResponse());
    }

    public function testClearAllWebspacseWithoutPermissions(): void
    {
        $this->client->request('DELETE', '/sulu_website/cache');

        static::assertHttpStatusCode(403, $this->client->getResponse());
    }
}
