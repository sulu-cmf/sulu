<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Security\Authentication\RoleInterface;

class MediaRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Collection[]
     */
    private $collections;

    /**
     * @var MediaType[]
     */
    private $mediaTypes = [];

    /**
     * @var CollectionType[]
     */
    private $collectionTypes = [];

    /**
     * @var SystemStoreInterface
     */
    private $systemStore;

    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->setUpMedia();

        $this->systemStore = $this->getContainer()->get('sulu_security.system_store');
        $this->mediaRepository = $this->getContainer()->get('sulu.repository.media');
    }

    protected function setUpMedia(): void
    {
        // Create Media Type
        $documentType = new MediaType();
        $documentType->setName('document');
        $documentType->setDescription('This is a document');

        $imageType = new MediaType();
        $imageType->setName('image');
        $imageType->setDescription('This is an image');

        $videoType = new MediaType();
        $videoType->setName('video');
        $videoType->setDescription('This is a video');

        $this->mediaTypes['image'] = $imageType;
        $this->mediaTypes['video'] = $videoType;

        // Create Collection Type
        $defaultCollectionType = new CollectionType();
        $defaultCollectionType->setName('Default Collection Type');
        $defaultCollectionType->setKey('collection.default');
        $defaultCollectionType->setDescription('Default Collection Type');

        $systemCollectionType = new CollectionType();
        $systemCollectionType->setName('System Collection Type');
        $systemCollectionType->setKey(SystemCollectionManagerInterface::COLLECTION_TYPE);
        $systemCollectionType->setDescription('System Collection Type');

        $this->collectionTypes['default'] = $defaultCollectionType;
        $this->collectionTypes['system'] = $systemCollectionType;

        $tagRepository = $this->getContainer()->get('sulu.repository.tag');

        // create some tags
        $tag1 = $tagRepository->createNew();
        $tag1->setName('Tag 1');

        $tag2 = $tagRepository->createNew();
        $tag2->setName('Tag 2');

        $this->em->persist($defaultCollectionType);
        $this->em->persist($systemCollectionType);
        $this->em->persist($tag1);
        $this->em->persist($tag2);
        $this->em->persist($documentType);
        $this->em->persist($imageType);
        $this->em->persist($videoType);
    }

    private function createCollection(string $collectionType)
    {
        $collection = new Collection();
        $collection->setType($this->collectionTypes[$collectionType]);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test System Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');
        $collectionMeta->setCollection($collection);

        $collection->addMeta($collectionMeta);

        $this->em->persist($collection);
        $this->em->persist($collectionMeta);

        return $collection;
    }

    private function createAccessControl($entity, Role $role, int $permissions): void
    {
        $accessControl = new AccessControl();
        $accessControl->setPermissions($permissions);
        $accessControl->setEntityId($entity->getId());
        $accessControl->setEntityClass(\get_class($entity));
        $accessControl->setRole($role);
        $this->em->persist($accessControl);
    }

    protected function createMedia($name, $title, $collection, $type = 'image')
    {
        $media = new Media();
        $media->setType($this->mediaTypes[$type]);

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName($name . '.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1937-04-20'));
        $fileVersion->setCreated(new \DateTime('1937-04-20'));
        $fileVersion->setStorageOptions(['segment' => 1, 'fileName' => $name . '.jpeg']);
        if (!\file_exists(__DIR__ . '/../../uploads/media/1')) {
            \mkdir(__DIR__ . '/../../uploads/media/1', 0777, true);
        }
        \copy($this->getImagePath(), __DIR__ . '/../../uploads/media/1/' . $name . '.jpeg');

        // create meta
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setLocale('en-gb');
        $fileVersionMeta->setTitle($title);
        $fileVersionMeta->setDescription('decription');
        $fileVersionMeta->setFileVersion($fileVersion);

        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setDefaultMeta($fileVersionMeta);

        $file->addFileVersion($fileVersion);

        $media->addFile($file);
        $media->setCollection($collection);

        $this->em->persist($media);
        $this->em->persist($file);
        $this->em->persist($fileVersionMeta);
        $this->em->persist($fileVersion);

        $formatOptions = new FormatOptions();
        $formatOptions->setFormatKey('my-format');
        $formatOptions->setFileVersion($fileVersion);
        $formatOptions->setCropHeight(10);
        $formatOptions->setCropWidth(20);
        $formatOptions->setCropX(30);
        $formatOptions->setCropY(40);
        $fileVersion->addFormatOptions($formatOptions);

        $this->em->persist($formatOptions);

        return $media;
    }

    private function createUser(?RoleInterface $role = null)
    {
        $user = new User();
        $user->setUsername('test');
        $user->setPassword('test');
        $user->setSalt('test');
        $user->setLocale('en');

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $user->setContact($contact);

        if ($role) {
            $userRole = new UserRole();
            $userRole->setLocale('en');
            $userRole->setUser($user);
            $userRole->setRole($role);
            $this->em->persist($userRole);
            $user->addUserRole($userRole);
        }

        $this->em->persist($contact);
        $this->em->persist($user);

        return $user;
    }

    private function createRole(string $system = 'Sulu')
    {
        $role = new Role();
        $role->setName('Role');
        $role->setSystem($system);

        $this->em->persist($role);

        return $role;
    }

    /**
     * @return string
     */
    private function getImagePath()
    {
        return __DIR__ . '/../../Fixtures/files/photo.jpeg';
    }

    public function testFindMedia(): void
    {
        $collection = $this->createCollection('default');

        $this->em->flush();

        $media1 = $this->createMedia('test-1', 'test-1', $collection);
        $media2 = $this->createMedia('test-2', 'test-2', $collection);
        $media3 = $this->createMedia('test-3', 'test-3', $collection);
        $media4 = $this->createMedia('test-4', 'test-4', $collection);

        $this->em->flush();

        $result = $this->mediaRepository->findMedia();

        $this->assertCount(4, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());
        $this->assertEquals($media3->getId(), $result[2]->getId());
        $this->assertEquals($media4->getId(), $result[3]->getId());
    }

    public function testFindMediaPagination(): void
    {
        $collection = $this->createCollection('default');

        $this->em->flush();

        $media1 = $this->createMedia('test-1', 'test-1', $collection);
        $media2 = $this->createMedia('test-2', 'test-2', $collection);
        $media3 = $this->createMedia('test-3', 'test-3', $collection);
        $media4 = $this->createMedia('test-4', 'test-4', $collection);

        $this->em->flush();

        $result = $this->mediaRepository->findMedia([], 3, 0);

        $this->assertCount(3, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());
        $this->assertEquals($media3->getId(), $result[2]->getId());

        $result = $this->mediaRepository->findMedia([], 3, 3);
        $this->assertCount(1, $result);
        $this->assertEquals($media4->getId(), $result[0]->getId());

        $result = $this->mediaRepository->findMedia([], 3, 6);
        $this->assertCount(0, $result);

        $this->assertEquals(4, $this->mediaRepository->count([]));
    }

    public function testFindMediaSearch(): void
    {
        $collection = $this->createCollection('default');

        $this->em->flush();

        $media1 = $this->createMedia('test-1', 'A', $collection);
        $media2 = $this->createMedia('test-2', 'AA', $collection);
        $media3 = $this->createMedia('test-3', 'AAA', $collection);
        $media4 = $this->createMedia('test-4', 'AB', $collection);

        $this->em->flush();

        $result = $this->mediaRepository->findMedia(['search' => 'AA']);

        $this->assertCount(2, $result);
        $this->assertEquals($media2->getId(), $result[0]->getId());
        $this->assertEquals($media3->getId(), $result[1]->getId());

        $this->assertEquals(2, $this->mediaRepository->count(['search' => 'AA']));
    }

    public function testFindMediaSearchPagination(): void
    {
        $collection = $this->createCollection('default');

        $this->em->flush();

        $media1 = $this->createMedia('test-1', 'A', $collection);
        $media2 = $this->createMedia('test-2', 'AA', $collection);
        $media3 = $this->createMedia('test-3', 'AAA', $collection);
        $media4 = $this->createMedia('test-4', 'AAAA', $collection);

        $this->em->flush();

        $result = $this->mediaRepository->findMedia(['search' => 'AA'], 2, 0);

        $this->assertCount(2, $result);
        $this->assertEquals($media2->getId(), $result[0]->getId());
        $this->assertEquals($media3->getId(), $result[1]->getId());

        $result = $this->mediaRepository->findMedia(['search' => 'AA'], 2, 2);

        $this->assertCount(1, $result);
        $this->assertEquals($media4->getId(), $result[0]->getId());

        $this->assertEquals(3, $this->mediaRepository->count(['search' => 'AA']));
    }

    public function testFindMediaTypes(): void
    {
        $collection = $this->createCollection('default');

        $this->em->flush();

        $media1 = $this->createMedia('test-1', 'test-1', $collection, 'video');
        $media2 = $this->createMedia('test-2', 'test-2', $collection, 'image');
        $media3 = $this->createMedia('test-3', 'test-3', $collection, 'video');
        $media4 = $this->createMedia('test-4', 'test-4', $collection, 'image');

        $this->em->flush();

        $result = $this->mediaRepository->findMedia(['types' => ['video']]);

        $this->assertCount(2, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media3->getId(), $result[1]->getId());

        $result = $this->mediaRepository->findMedia(['types' => ['image']]);

        $this->assertCount(2, $result);
        $this->assertEquals($media2->getId(), $result[0]->getId());
        $this->assertEquals($media4->getId(), $result[1]->getId());

        $result = $this->mediaRepository->findMedia(['types' => ['image', 'video']]);

        $this->assertCount(4, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());
        $this->assertEquals($media3->getId(), $result[2]->getId());
        $this->assertEquals($media4->getId(), $result[3]->getId());

        $result = $this->mediaRepository->findMedia(['types' => ['asdf']]);

        $this->assertCount(0, $result);

        $this->assertEquals(2, $this->mediaRepository->count(['types' => ['image']]));
        $this->assertEquals(2, $this->mediaRepository->count(['types' => ['video']]));
        $this->assertEquals(4, $this->mediaRepository->count(['types' => ['image', 'video']]));
        $this->assertEquals(0, $this->mediaRepository->count(['types' => ['asdf']]));
    }

    public function testFindMediaTypesPagination(): void
    {
        $collection = $this->createCollection('default');

        $this->em->flush();

        $media1 = $this->createMedia('test-1', 'test-1', $collection, 'video');
        $media2 = $this->createMedia('test-2', 'test-2', $collection, 'video');
        $media3 = $this->createMedia('test-3', 'test-3', $collection, 'video');
        $media4 = $this->createMedia('test-4', 'test-4', $collection, 'image');

        $this->em->flush();

        $result = $this->mediaRepository->findMedia(['types' => ['video']], 2, 0);

        $this->assertCount(2, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());

        $result = $this->mediaRepository->findMedia(['types' => ['video']], 2, 2);

        $this->assertCount(1, $result);
        $this->assertEquals($media3->getId(), $result[0]->getId());

        $result = $this->mediaRepository->findMedia(['types' => ['video']], 2, 4);

        $this->assertCount(0, $result);

        $this->assertEquals(3, $this->mediaRepository->count(['types' => ['video']]));
    }

    public function testFindMediaByCollection(): void
    {
        $collection1 = $this->createCollection('default');
        $collection2 = $this->createCollection('default');

        $this->em->flush();

        $media1 = $this->createMedia('test-1', 'test-1', $collection2);
        $media2 = $this->createMedia('test-2', 'test-2', $collection2);
        $media3 = $this->createMedia('test-3', 'test-3', $collection2);
        $media4 = $this->createMedia('test-4', 'test-4', $collection1);

        $this->em->flush();

        $result = $this->mediaRepository->findMedia(['collection' => $collection2->getId()]);

        $this->assertCount(3, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());
        $this->assertEquals($media3->getId(), $result[2]->getId());

        $result = $this->mediaRepository->findMedia(['collection' => $collection1->getId()]);

        $this->assertCount(1, $result);
        $this->assertEquals($media4->getId(), $result[0]->getId());

        $this->assertEquals(3, $this->mediaRepository->count(['collection' => $collection2->getId()]));
        $this->assertEquals(1, $this->mediaRepository->count(['collection' => $collection1->getId()]));
    }

    public function testFindMediaByCollectionPagination(): void
    {
        $collection1 = $this->createCollection('default');
        $collection2 = $this->createCollection('default');

        $this->em->flush();

        $media1 = $this->createMedia('test-1', 'test-1', $collection2);
        $media2 = $this->createMedia('test-2', 'test-2', $collection2);
        $media3 = $this->createMedia('test-3', 'test-3', $collection2);
        $media4 = $this->createMedia('test-4', 'test-4', $collection1);

        $this->em->flush();

        $result = $this->mediaRepository->findMedia(['collection' => $collection2->getId()], 2, 0);

        $this->assertCount(2, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());

        $result = $this->mediaRepository->findMedia(['collection' => $collection2->getId()], 2, 2);

        $this->assertCount(1, $result);
        $this->assertEquals($media3->getId(), $result[0]->getId());

        $result = $this->mediaRepository->findMedia(['collection' => $collection2->getId()], 2, 4);

        $this->assertCount(0, $result);

        $this->assertEquals(3, $this->mediaRepository->count(['collection' => $collection2->getId()]));
        $this->assertEquals(1, $this->mediaRepository->count(['collection' => $collection1->getId()]));
    }

    public function testFindMediaWithSystemCollections(): void
    {
        $collection1 = $this->createCollection('default');
        $collection2 = $this->createCollection('system');

        $this->em->flush();

        $this->createMedia('test-1', 'test-1', $collection1);
        $this->createMedia('test-2', 'test-2', $collection2);

        $this->em->flush();

        $this->assertCount(2, $this->mediaRepository->findMedia());
        $this->assertCount(1, $this->mediaRepository->findMedia(['systemCollections' => false]));
    }

    public function testFindMediaWithSystemCollectionsAndTypes(): void
    {
        $collection1 = $this->createCollection('default');
        $collection2 = $this->createCollection('system');
        $this->createMedia('test-1', 'test-1', $collection1);
        $this->createMedia('test-2', 'test-2', $collection2);

        $this->em->flush();

        $this->assertCount(2, $this->mediaRepository->findMedia());
        $this->assertCount(1, $this->mediaRepository->findMedia(['systemCollections' => false, 'types' => ['image']]));
    }

    public function testFindMediaByIds(): void
    {
        $collection = $this->createCollection('default');

        $this->em->flush();

        $media1 = $this->createMedia('test-1', 'test-1', $collection, 'video');
        $media2 = $this->createMedia('test-2', 'test-2', $collection, 'video');
        $media3 = $this->createMedia('test-3', 'test-3', $collection, 'video');
        $media4 = $this->createMedia('test-4', 'test-4', $collection, 'image');

        $this->em->flush();

        $result = $this->mediaRepository->findMedia(['ids' => [$media1->getId(), $media3->getId()]]);

        $this->assertCount(2, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media3->getId(), $result[1]->getId());
    }

    public function testFindMediaForUserWithoutPermissions(): void
    {
        $user = $this->createUser();

        $collection = $this->createCollection('default');

        $media1 = $this->createMedia('test-1', 'test-1', $collection, 'video');
        $media2 = $this->createMedia('test-2', 'test-2', $collection, 'video');
        $media3 = $this->createMedia('test-3', 'test-3', $collection, 'video');
        $media4 = $this->createMedia('test-4', 'test-4', $collection, 'image');

        $this->em->flush();

        $result = $this->mediaRepository->findMedia(
            ['ids' => [$media1->getId(), $media3->getId()]],
            null,
            null,
            $user,
            64
        );

        $this->assertCount(2, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media3->getId(), $result[1]->getId());
    }

    public function testFindMediaForUserWithViewPermissions(): void
    {
        $this->systemStore->setSystem('Sulu');

        $role = $this->createRole();
        $user = $this->createUser($role);

        $collection1 = $this->createCollection('default');
        $collection2 = $this->createCollection('default');
        $collection3 = $this->createCollection('default');

        $this->em->flush();

        $this->createAccessControl($collection1, $role, 0);
        $this->createAccessControl($collection3, $role, 64);

        $media1 = $this->createMedia('test-1', 'test-1', $collection1, 'video');
        $media2 = $this->createMedia('test-2', 'test-2', $collection3, 'video');
        $media3 = $this->createMedia('test-3', 'test-3', $collection1, 'video');
        $media4 = $this->createMedia('test-4', 'test-4', $collection2, 'image');

        $this->em->flush();

        $result = $this->mediaRepository->findMedia(
            ['ids' => [$media1->getId(), $media2->getId(), $media3->getId(), $media4->getId()]],
            null,
            null,
            $user,
            64
        );

        $this->assertCount(2, $result);
        $this->assertEquals($media2->getId(), $result[0]->getId());
        $this->assertEquals($media4->getId(), $result[1]->getId());
    }

    public function testFindMediaWithRestrictedViewPermissionsInOtherSystem(): void
    {
        // regression test for https://github.com/sulu/sulu/discussions/6804

        $this->systemStore->setSystem('Sulu');
        $role = $this->createRole('Other');
        $user = $this->createUser();

        $collection1 = $this->createCollection('default');
        $collection2 = $this->createCollection('default');

        $this->em->flush();

        $this->createAccessControl($collection1, $role, 0);

        $media1 = $this->createMedia('test-1', 'test-1', $collection1, 'video');
        $media2 = $this->createMedia('test-2', 'test-2', $collection2, 'image');

        $this->em->flush();

        $result = $this->mediaRepository->findMedia(
            ['ids' => [$media1->getId(), $media2->getId()]],
            null,
            null,
            $user,
            64
        );

        $this->assertCount(2, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());
    }

    public function testFindMediaDisplayInfo(): void
    {
        $collection = $this->createCollection('default');

        $media1 = $this->createMedia('test-1', 'test-1-title', $collection, 'image');
        $media2 = $this->createMedia('test-2', 'test-2-title', $collection, 'image');
        $media3 = $this->createMedia('test-3', 'test-3-title', $collection, 'video');
        $media4 = $this->createMedia('test-4', 'test-4-title', $collection, 'video');

        $this->em->flush();

        $file = $media1->getFiles()[0];
        $fileVersion = $file->getFileVersions()[0];
        $fileVersion2 = clone $fileVersion;
        $fileVersion2->setVersion(2);
        static::getEntityManager()->persist($fileVersion2);
        $file->addFileVersion($fileVersion2);
        $file->setVersion(2);

        $this->em->flush();
        $this->em->clear();

        $result = $this->mediaRepository->findMediaDisplayInfo([$media1->getId(), $media3->getId()], 'en-gb');
        $this->assertEquals(2, \count($result));
        $this->assertEquals(5, \count($result[0]));
        $this->assertEquals(5, \count($result[1]));

        $resultMedia1 = $result[0];
        if ($media1->getId() === ($result[1]['id'] ?? null)) { // the order is not fix, we need to find the correct one first
            $resultMedia1 = $result[1];
        }
        $this->assertEquals($media1->getId(), $resultMedia1['id']);
        $this->assertEquals(2, $resultMedia1['version']);
        $this->assertEquals('test-1.jpeg', $resultMedia1['name']);
        $this->assertEquals('test-1-title', $resultMedia1['title']);
        $this->assertEquals('test-1-title', $resultMedia1['defaultTitle']);
    }

    public function testFindMediaDisplayInfoWithIncorrectIds(): void
    {
        $collection = $this->createCollection('default');

        $media1 = $this->createMedia('test-1', 'test-1-title', $collection, 'image');
        $media2 = $this->createMedia('test-2', 'test-2-title', $collection, 'image');
        $media3 = $this->createMedia('test-3', 'test-3-title', $collection, 'video');
        $media4 = $this->createMedia('test-4', 'test-4-title', $collection, 'video');

        $result = $this->mediaRepository->findMediaDisplayInfo([-1], 'en-gb');

        $this->assertNotNull($result);
        $this->assertEquals(0, \count($result));
    }

    public function testCount(): void
    {
        $collection = $this->createCollection('default');
        $systemCollection = $this->createCollection('system');
        $this->em->flush();

        $this->createMedia('test-1', 'test-1', $collection);
        $this->createMedia('test-2', 'test-2', $systemCollection);

        $this->em->flush();

        $this->assertEquals(2, $this->mediaRepository->count([]));
        $this->assertEquals(1, $this->mediaRepository->count(['systemCollections' => false]));
    }

    public function testGetMediaByIdForRendering(): void
    {
        $collection = $this->createCollection('default');

        $this->em->flush();

        $media = $this->createMedia('media-with-format-options', 'Media with format options', $collection);

        $this->em->flush();

        $retrievedMedia = $this->mediaRepository->findMediaByIdForRendering($media->getId(), 'my-format');
        $this->assertNotNull($retrievedMedia);

        $files = $retrievedMedia->getFiles();
        $this->assertEquals($media->getId(), $retrievedMedia->getId());
        $this->assertEquals(1, \count($files));
        $this->assertEquals(1, \count($files->get(0)->getFileVersions()));

        /** @var FileVersion $fileVersion */
        $fileVersion = $files->get(0)->getFileVersions()->get(0);

        $this->assertEquals(1, \count($fileVersion->getFormatOptions()));

        /** @var FormatOptions $formatOptions */
        $formatOptions = $fileVersion->getFormatOptions()->get('my-format');

        $this->assertEquals('my-format', $formatOptions->getFormatKey());
        $this->assertEquals($fileVersion->getId(), $formatOptions->getFileVersion()->getId());
        $this->assertEquals(10, $formatOptions->getCropHeight());
        $this->assertEquals(20, $formatOptions->getCropWidth());
        $this->assertEquals(30, $formatOptions->getCropX());
        $this->assertEquals(40, $formatOptions->getCropY());
    }
}
