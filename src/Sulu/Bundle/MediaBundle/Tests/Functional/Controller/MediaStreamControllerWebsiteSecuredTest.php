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

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManager;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManager;
use Sulu\Bundle\MediaBundle\Tests\Application\SecuredKernel;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
class MediaStreamControllerWebsiteSecuredTest extends WebsiteTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected static function getKernelClass(): string
    {
        return SecuredKernel::class;
    }

    public function setUp(): void
    {
        if (\version_compare(SecuredKernel::VERSION, '5.4', '<')) { // @phpstan-ignore-line
            $this->markTestSkipped('Test is written for Symfony 5.4 or newer.');
        }

        $this->client = $this->createWebsiteClient();
        $this->purgeDatabase();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testDownloadWithCollectionPermissions(): void
    {
        $filePath = $this->createMediaFile('test.jpg');
        $media = $this->createMedia($filePath, 'file-without-extension');

        $anonymousRole = $this->getContainer()->get('sulu.repository.role')->createNew();
        $this->getContainer()->get('doctrine.orm.entity_manager')->persist($anonymousRole);
        $anonymousRole->setName('Anonymous');
        $anonymousRole->setAnonymous(true);
        $anonymousRole->setSystem('sulu_io');

        $allowedContact = new Contact();
        $this->getContainer()->get('doctrine.orm.entity_manager')->persist($allowedContact);
        $allowedContact->setFirstName('Allowed');
        $allowedContact->setLastName('Contact');

        $allowedUser = new User();
        $this->getEntityManager()->persist($allowedUser);
        $allowedUser->setUsername('allowed-user');
        $allowedUser->setContact($allowedContact);
        $allowedUser->setSalt('');

        $passwordHasherFactory = self::getContainer()->get('security.password_hasher_factory');
        $hasher = $passwordHasherFactory->getPasswordHasher($allowedUser);
        $password = $hasher->hash($allowedUser->getUsername());

        $allowedUser->setPassword($password);
        $allowedUser->setLocale('en');

        $allowedRole = $this->getContainer()->get('sulu.repository.role')->createNew();
        $this->getContainer()->get('doctrine.orm.entity_manager')->persist($allowedRole);
        $allowedRole->setName('Allowed Role');
        $allowedRole->setAnonymous(false);
        $allowedRole->setSystem('sulu_io');

        $allowedUserRole = new UserRole();
        $this->getContainer()->get('doctrine.orm.entity_manager')->persist($allowedUserRole);
        $allowedUserRole->setRole($allowedRole);
        $allowedUserRole->setUser($allowedUser);
        $allowedUserRole->setLocale(\json_encode(['de', 'en']) ?: '');
        $allowedUser->addUserRole($allowedUserRole);

        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $this->getContainer()->get('sulu_security.access_control_manager')->setPermissions(
            Collection::class,
            (string) $media->getCollection(),
            [
                $anonymousRole->getId() => [
                    'view' => false,
                ],
                $allowedRole->getId() => [
                    'view' => true,
                ],
            ]
        );

        $this->client->jsonRequest('GET', 'http://sulu.lo' . $media->getUrl());
        $unauthenticatedResponse = $this->client->getResponse();
        $this->assertHttpStatusCode(401, $unauthenticatedResponse);

        $this->client->jsonRequest('GET', 'http://sulu.lo' . $media->getUrl(), [], [
            'PHP_AUTH_USER' => 'allowed-user',
            'PHP_AUTH_PW' => 'allowed-user',
        ]);
        $authenticatedResponse = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $authenticatedResponse);
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
