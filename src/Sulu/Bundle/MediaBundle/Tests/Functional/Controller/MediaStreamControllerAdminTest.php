<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManager;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManager;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class MediaStreamControllerAdminTest extends SuluTestCase
{
    use ProphecyTrait;

    public function testDownloadActionCheckPermissionCalled(): void
    {
        $this->initDatabase();

        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'file-without-extension');

        // teardown needed to set a mocked service
        $this->tearDown();
        $client = $this->createAuthenticatedClient();

        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->checkPermission(Argument::that(function(SecurityCondition $securityCondition) use ($media) {
            $this->assertSame(MediaAdmin::SECURITY_CONTEXT, $securityCondition->getSecurityContext());
            $this->assertSame(null, $securityCondition->getLocale());
            $this->assertSame(Collection::class, $securityCondition->getObjectType());
            $this->assertSame($media->getCollection(), $securityCondition->getObjectId());

            return true;
        }), PermissionTypes::VIEW)
            ->shouldBeCalled();

        self::getContainer()->set('sulu_security.security_checker', $securityChecker->reveal());

        $client->request('GET', $media->getAdminUrl());
        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);
    }

    public function testDownloadActionCheckPermissionDenied(): void
    {
        $client = $this->createAuthenticatedClient([], [
            'PHP_AUTH_USER' => 'secured_user',
            'PHP_AUTH_PW' => 'secured_user',
        ]);

        $this->initDatabase();
        $this->createTestUser('secured_user', false);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'file-without-extension');

        $client->request('GET', $media->getAdminUrl());
        $response = $client->getResponse();

        $this->assertHttpStatusCode(403, $response);
    }

    public function testDownloadActionCheckPermissionAllowed(): void
    {
        $client = $this->createAuthenticatedClient([], [
            'PHP_AUTH_USER' => 'secured_user',
            'PHP_AUTH_PW' => 'secured_user',
        ]);

        $this->initDatabase();
        $this->createTestUser('secured_user', true);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'file-without-extension');

        $client->request('GET', $media->getAdminUrl());
        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);
    }

    private function createTestUser(string $username, bool $hasPermission): void
    {
        $contact = new Contact();
        $this->getEntityManager()->persist($contact);
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');

        $user = new User();
        $this->getEntityManager()->persist($user);
        $user->setUsername($username);
        $user->setContact($contact);

        $user->setSalt('');

        $passwordHasherFactory = self::getContainer()->get('security.password_hasher_factory');
        $hasher = $passwordHasherFactory->getPasswordHasher($user);
        $password = $hasher->hash($username);

        $user->setPassword($password);
        $user->setLocale('en');

        $role = new Role();
        $this->getEntityManager()->persist($role);
        $role->setName('Secure Test Role');
        $role->setSystem(Admin::SULU_ADMIN_SECURITY_SYSTEM);
        $role->setAnonymous(false);

        $userRole = new UserRole();
        $this->getEntityManager()->persist($userRole);
        $user->addUserRole($userRole);
        $userRole->setUser($user);
        $userRole->setRole($role);
        $userRole->setLocale('["en"]');

        if ($hasPermission) {
            $permission = new Permission();
            $this->getEntityManager()->persist($permission);
            $permission->setRole($role);
            $permission->setContext(MediaAdmin::SECURITY_CONTEXT);
            $permission->setPermissions(64);
            $role->addPermission($permission);
        }
    }

    private function initDatabase(): void
    {
        $this->purgeDatabase();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    private function createUploadedFile(string $path): UploadedFile
    {
        /** @var string $mimeType */
        $mimeType = \mime_content_type($path);

        return new UploadedFile($path, \basename($path), $mimeType);
    }

    private function createCollection(string $title = 'Test'): int
    {
        $collection = $this->getCollectionManager()->save(
            [
                'title' => $title,
                'locale' => 'en',
                'type' => ['id' => 1],
            ],
            1
        );

        return $collection->getId();
    }

    private function createMedia(string $path, string $title): Media
    {
        return $this->getMediaManager()->save(
            $this->createUploadedFile($path),
            [
                'title' => $title,
                'collection' => $this->createCollection(),
                'locale' => 'en',
            ],
            null
        );
    }

    private function getMediaManager(): MediaManager
    {
        return $this->getContainer()->get('sulu_media.media_manager');
    }

    private function getCollectionManager(): CollectionManager
    {
        return $this->getContainer()->get('sulu_media.collection_manager');
    }

    private function createMediaFile(string $name, string $fileName = 'photo.jpeg'): string
    {
        $filePath = \sys_get_temp_dir() . '/' . $name;
        \copy(__DIR__ . '/../../Fixtures/files/' . $fileName, $filePath);

        return $filePath;
    }
}
