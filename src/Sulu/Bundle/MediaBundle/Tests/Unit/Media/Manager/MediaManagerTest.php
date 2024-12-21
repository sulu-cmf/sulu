<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\Manager;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaCreatedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaModifiedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaRemovedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaVersionAddedEvent;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMediaTypeException;
use Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidatorInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManager;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\MediaPropertiesProviderInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Security\Authentication\UserInterface as SuluUserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class MediaManagerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var ObjectProphecy<MediaRepositoryInterface>
     */
    private $mediaRepository;

    /**
     * @var ObjectProphecy<CollectionRepository>
     */
    private $collectionRepository;

    /**
     * @var ObjectProphecy<UserRepositoryInterface>
     */
    private $userRepository;

    /**
     * @var ObjectProphecy<CategoryRepositoryInterface>
     */
    private $categoryRepository;

    /**
     * @var ObjectProphecy<TargetGroupRepositoryInterface>
     */
    private $targetGroupRepository;

    /**
     * @var ObjectProphecy<EntityManager>
     */
    private $em;

    /**
     * @var ObjectProphecy<StorageInterface>
     */
    private $storage;

    /**
     * @var ObjectProphecy<FileValidatorInterface>
     */
    private $validator;

    /**
     * @var ObjectProphecy<FormatManagerInterface>
     */
    private $formatManager;

    /**
     * @var ObjectProphecy<TagManagerInterface>
     */
    private $tagManager;

    /**
     * @var ObjectProphecy<TypeManagerInterface>
     */
    private $typeManager;

    /**
     * @var ObjectProphecy<PathCleanupInterface>
     */
    private $pathCleaner;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $domainEventCollector;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private $tokenStorage;

    /**
     * @var ObjectProphecy<SecurityCheckerInterface>
     */
    private $securityChecker;

    /**
     * @var ObjectProphecy<MediaPropertiesProviderInterface>
     */
    private $mediaPropertiesProvider;

    /**
     * @var ObjectProphecy<CategoryManagerInterface>
     */
    private $categoryManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);
        $this->collectionRepository = $this->prophesize(CollectionRepository::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $this->targetGroupRepository = $this->prophesize(TargetGroupRepositoryInterface::class);
        $this->em = $this->prophesize(EntityManager::class);
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->validator = $this->prophesize(FileValidatorInterface::class);
        $this->formatManager = $this->prophesize(FormatManagerInterface::class);
        $this->tagManager = $this->prophesize(TagManagerInterface::class);
        $this->categoryManager = $this->prophesize(CategoryManagerInterface::class);
        $this->typeManager = $this->prophesize(TypeManagerInterface::class);
        $this->pathCleaner = $this->prophesize(PathCleanupInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->mediaPropertiesProvider = $this->prophesize(MediaPropertiesProviderInterface::class);

        $this->mediaManager = new MediaManager(
            $this->mediaRepository->reveal(),
            $this->collectionRepository->reveal(),
            $this->userRepository->reveal(),
            $this->categoryRepository->reveal(),
            $this->em->reveal(),
            $this->storage->reveal(),
            $this->validator->reveal(),
            $this->formatManager->reveal(),
            $this->tagManager->reveal(),
            $this->typeManager->reveal(),
            $this->pathCleaner->reveal(),
            $this->domainEventCollector->reveal(),
            $this->tokenStorage->reveal(),
            $this->securityChecker->reveal(),
            [
                $this->mediaPropertiesProvider->reveal(),
            ],
            '/download/{id}/media/{slug}',
            $this->targetGroupRepository->reveal()
        );
    }

    /**
     * @param int[] $ids
     * @param Media[] $media
     * @param Media[] $result
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideGetByIds')]
    public function testGetByIds(array $ids, ?SuluUserInterface $user, ?int $permissions, array $media, array $result): void
    {
        /** @var TokenInterface|ObjectProphecy $token */
        $token = $this->prophesize(TokenInterface::class);

        if (!$permissions) {
            $this->tokenStorage->getToken()->shouldNotBeCalled();
            $token->getUser()->shouldNotBeCalled();
        } else {
            $token->getUser()->shouldBeCalled()->willReturn($user);
            $this->tokenStorage->getToken()->shouldBeCalled()->willReturn($token->reveal());
        }

        $this->mediaRepository->findMedia(
            ['pagination' => false, 'ids' => $ids],
            null,
            null,
            $permissions ? $user : null,
            $permissions
        )->shouldBeCalled()->willReturn($media);
        $this->formatManager->getFormats(Argument::cetera())->willReturn(null);
        $medias = $this->mediaManager->getByIds($ids, 'en', $permissions);

        $count = \count($medias);
        for ($i = 0; $i < $count; ++$i) {
            $this->assertEquals($result[$i]->getId(), $medias[$i]->getId());
        }
    }

    public function testGetWithoutPermission(): void
    {
        $this->tokenStorage->getToken()->shouldNotBeCalled();
        $this->mediaRepository->findMedia(Argument::cetera())->willReturn([])->shouldBeCalled();
        $this->mediaRepository->count(Argument::cetera())->shouldBeCalled();

        $this->mediaManager->get(1);
    }

    public function testGetWithoutToken(): void
    {
        $this->tokenStorage->getToken()->willReturn(null);
        $this->mediaRepository->findMedia(Argument::cetera())->willReturn([])->shouldBeCalled();
        $this->mediaRepository->count(Argument::cetera())->shouldBeCalled();

        $this->mediaManager->get(1, [], null, null, 64);
    }

    public function testGetWithoutSuluUser(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user->reveal());

        $this->tokenStorage->getToken()->willReturn($token);
        $this->mediaRepository->findMedia([], null, null, null, 64)->willReturn([])->shouldBeCalled();
        $this->mediaRepository->count(Argument::cetera())->shouldBeCalled();

        $this->mediaManager->get('de', [], null, null, 64);
    }

    public function testGetWithSuluUser(): void
    {
        $user = $this->prophesize(SuluUserInterface::class);
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user->reveal());

        $this->tokenStorage->getToken()->willReturn($token->reveal());
        $this->mediaRepository->findMedia([], null, null, $user->reveal(), 64)->willReturn([])->shouldBeCalled();
        $this->mediaRepository->count(Argument::cetera())->shouldBeCalled();

        $this->mediaManager->get('de', [], null, null, 64);
    }

    public function testDeleteWithSecurity(): void
    {
        $collection = $this->prophesize(Collection::class);
        $collection->getId()->willReturn(2);
        $media = $this->prophesize(Media::class);
        $media->getId()->willReturn(1);
        $media->getCollection()->willReturn($collection);
        $media->getFiles()->willReturn([]);

        $this->mediaRepository->findMediaById(1)->willReturn($media);
        $this->securityChecker->checkPermission(
            new SecurityCondition('sulu.media.collections', null, Collection::class, 2),
            'delete'
        )->shouldBeCalled();

        $this->mediaManager->delete(1, true);
    }

    public function testDelete(): void
    {
        $collection = $this->prophesize(Collection::class);
        $collection->getId()->willReturn(2);

        $file = $this->prophesize(File::class);
        $fileVersion = $this->prophesize(FileVersion::class);
        $file->getFileVersions()->willReturn([$fileVersion->reveal()]);
        $file->getLatestFileVersion()->willReturn($fileVersion->reveal());
        $fileVersion->getId()->willReturn(1);
        $fileVersion->getName()->willReturn('test');
        $fileVersion->getMimeType()->willReturn('image/png');
        $fileVersion->getStorageOptions()->willReturn(['segment' => '01', 'fileName' => 'test.jpg']);

        $fileVersionMeta = $this->prophesize(FileVersionMeta::class);
        $fileVersionMeta->getTitle()->willReturn('Test image');
        $fileVersionMeta->getLocale()->willReturn('en');
        $fileVersion->getMeta()->willReturn([$fileVersionMeta->reveal()]);
        $fileVersion->getDefaultMeta()->willReturn($fileVersionMeta->reveal());

        $formatOptions = $this->prophesize(FormatOptions::class);
        $fileVersion->getFormatOptions()->willReturn([$formatOptions->reveal()]);

        $media = $this->prophesize(Media::class);
        $media->getCollection()->willReturn($collection);
        $media->getFiles()->willReturn([$file->reveal()]);
        $media->getId()->willReturn(1);

        $this->formatManager->purge(
            1,
            'test',
            'image/png'
        )->shouldBeCalled();

        $this->mediaRepository->findMediaById(1)->willReturn($media);
        $this->securityChecker->checkPermission(
            new SecurityCondition('sulu.media.collections', null, Collection::class, 2),
            'delete'
        )->shouldBeCalled();

        $this->storage->remove(['segment' => '01', 'fileName' => 'test.jpg'])->shouldBeCalled();
        $this->em->detach($fileVersion->reveal())->shouldBeCalled();
        $this->em->detach($file->reveal())->shouldBeCalled();
        $this->em->remove($fileVersionMeta->reveal())->shouldBeCalled();
        $this->em->detach($formatOptions->reveal())->shouldBeCalled();
        $this->em->remove($media->reveal())->shouldBeCalled();

        $this->domainEventCollector->collect(Argument::type(MediaRemovedEvent::class))->shouldBeCalled();

        $this->em->flush()->shouldBeCalled();

        $this->mediaManager->delete(1, true);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSpecialCharacterFileName')]
    public function testSpecialCharacterFileName(string $fileName, string $cleanUpArgument, string $cleanUpResult, string $extension): void
    {
        /** @var UploadedFile|ObjectProphecy $uploadedFile */
        $uploadedFile = $this->prophesize(UploadedFile::class)->willBeConstructedWith([__DIR__ . \DIRECTORY_SEPARATOR . 'test.txt', 1, null, null, 1, true]);
        $uploadedFile->getClientOriginalName()->willReturn($fileName);
        $uploadedFile->getPathname()->willReturn('');
        $uploadedFile->getSize()->willReturn('123');
        $uploadedFile->getMimeType()->willReturn('img');

        $user = $this->prophesize(User::class)->willImplement(UserInterface::class);
        $this->userRepository->findUserById(1)->willReturn($user);

        $this->mediaRepository->createNew()->willReturn(new Media());

        $this->storage->save('', $cleanUpResult . $extension)->shouldBeCalled();
        $this->mediaPropertiesProvider
            ->provide($uploadedFile->reveal())
            ->willReturn([])
            ->shouldBeCalled();

        $this->pathCleaner->cleanup(Argument::exact($cleanUpArgument))->shouldBeCalled()->willReturn($cleanUpResult);

        $this->domainEventCollector->collect(Argument::type(MediaCreatedEvent::class))->shouldBeCalled();

        $media = $this->mediaManager->save($uploadedFile->reveal(), ['locale' => 'en', 'title' => 'my title'], 1);

        $this->assertEquals($fileName, $media->getName());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSpecialCharacterUrl')]
    public function testSpecialCharacterUrl(int $id, string $filename, int $version, string $expected): void
    {
        $this->assertEquals($expected, $this->mediaManager->getUrl($id, $filename, $version));
    }

    public function testSaveWrongVersionType(): void
    {
        $this->expectException(InvalidMediaTypeException::class);

        $uploadedFile = $this->prophesize(UploadedFile::class)->willBeConstructedWith([__DIR__ . \DIRECTORY_SEPARATOR . 'test.txt', 1, null, null, 1, true]);
        $uploadedFile->getClientOriginalName()->willReturn('test.pdf');
        $uploadedFile->getPathname()->willReturn('');
        $uploadedFile->getSize()->willReturn('123');
        $uploadedFile->getMimeType()->willReturn('img');

        $media = $this->prophesize(Media::class);
        $media->setChanger(Argument::any())->willReturn(null);
        $media->setChanged(Argument::any())->willReturn(null);

        $mediaType = $this->prophesize(MediaType::class);
        $mediaType->getId()->willReturn(1);
        $media->getType()->willReturn($mediaType->reveal());

        $file = $this->prophesize(File::class);
        $file->getVersion()->willReturn(1);
        $file->setChanger(Argument::any())->willReturn(null);
        $file->setChanged(Argument::any())->willReturn(null);
        $media->getFiles()->willReturn([$file->reveal()]);

        $fileVersion = $this->prophesize(FileVersion::class);
        $fileVersion->getVersion()->willReturn(1);
        $file->getFileVersion(1)->willReturn($fileVersion->reveal());

        $this->typeManager->getMediaType('img')->willReturn(2);

        $this->mediaRepository->findMediaById(1)->willReturn($media);

        $this->mediaManager->save($uploadedFile->reveal(), ['id' => 1], 42);
    }

    public function testSaveWithChangedFocusPoint(): void
    {
        $media = $this->prophesize(Media::class);
        $media->getId()->willReturn(1);
        $media->getPreviewImage()->willReturn(null);
        $file = $this->prophesize(File::class);
        $fileVersion = $this->prophesize(FileVersion::class);
        $fileVersion->getName()->willReturn('test');
        $fileVersion->getStorageOptions()->willReturn([]);
        $fileVersion->getSubVersion()->willReturn(1);
        $fileVersion->getVersion()->willReturn(1);
        $fileVersion->getMimeType()->willReturn('image/jpeg');
        $fileVersion->getProperties()->willReturn([]);
        $fileVersion->getFocusPointX()->willReturn(null);
        $fileVersion->getFocusPointY()->willReturn(null);
        $fileVersionMeta = $this->prophesize(FileVersionMeta::class);
        $fileVersionMeta->getLocale()->willReturn('en');
        $fileVersion->getMeta()->willReturn([$fileVersionMeta]);
        $file->getFileVersion(1)->willReturn($fileVersion->reveal());
        $file->getFileVersions()->willReturn([$fileVersion->reveal()]);
        $file->getLatestFileVersion()->willReturn($fileVersion->reveal());
        $file->getVersion()->willReturn(1);
        $media->getFiles()->willReturn([$file->reveal()]);
        $this->mediaRepository->findMediaById(1)->willReturn($media);
        $this->formatManager->getFormats(Argument::cetera())->willReturn([]);

        $media->setChanger(Argument::any())->shouldBeCalled();
        $media->setChanged(Argument::any())->shouldBeCalled();
        $file->setChanger(Argument::any())->shouldBeCalled();
        $file->setChanged(Argument::any())->shouldBeCalled();
        $fileVersion->setProperties([])->shouldBeCalled();
        $fileVersion->setChanged(Argument::any())->shouldBeCalled();
        $fileVersion->setFocusPointX(1)->shouldBeCalled();
        $fileVersion->setFocusPointY(2)->shouldBeCalled();
        $fileVersion->increaseSubVersion()->shouldBeCalled();
        $this->formatManager->purge(1, 'test', 'image/jpeg')->shouldBeCalled();

        $this->domainEventCollector->collect(Argument::type(MediaModifiedEvent::class))->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(MediaVersionAddedEvent::class))->shouldNotBeCalled();

        $this->mediaManager->save(null, ['id' => 1, 'locale' => 'en', 'focusPointX' => 1, 'focusPointY' => 2], 1);
    }

    public function testSaveWithSameFocusPoint(): void
    {
        $media = $this->prophesize(Media::class);
        $media->getId()->willReturn(1);
        $media->getPreviewImage()->willReturn(null);
        $file = $this->prophesize(File::class);
        $fileVersion = $this->prophesize(FileVersion::class);
        $fileVersion->getName()->willReturn('test');
        $fileVersion->getStorageOptions()->willReturn([]);
        $fileVersion->getSubVersion()->willReturn(1);
        $fileVersion->getVersion()->willReturn(1);
        $fileVersion->getMimeType()->willReturn('image/jpeg');
        $fileVersion->getProperties()->willReturn([]);
        $fileVersion->getFocusPointX()->willReturn(1);
        $fileVersion->getFocusPointY()->willReturn(2);
        $fileVersionMeta = $this->prophesize(FileVersionMeta::class);
        $fileVersionMeta->getLocale()->willReturn('en');
        $fileVersion->getMeta()->willReturn([$fileVersionMeta]);
        $file->getFileVersion(1)->willReturn($fileVersion->reveal());
        $file->getFileVersions()->willReturn([$fileVersion->reveal()]);
        $file->getLatestFileVersion()->willReturn($fileVersion->reveal());
        $file->getVersion()->willReturn(1);
        $media->getFiles()->willReturn([$file->reveal()]);
        $this->mediaRepository->findMediaById(1)->willReturn($media);

        $media->setChanger(Argument::any())->shouldBeCalled();
        $media->setChanged(Argument::any())->shouldBeCalled();
        $file->setChanger(Argument::any())->shouldBeCalled();
        $file->setChanged(Argument::any())->shouldBeCalled();
        $fileVersion->setFocusPointX(1)->shouldBeCalled();
        $fileVersion->setFocusPointY(2)->shouldBeCalled();
        $fileVersion->setProperties([])->shouldBeCalled();
        $fileVersion->setChanged(Argument::any())->shouldBeCalled();
        $fileVersion->increaseSubVersion()->shouldNotBeCalled();

        $this->domainEventCollector->collect(Argument::type(MediaModifiedEvent::class))->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(MediaVersionAddedEvent::class))->shouldNotBeCalled();

        $this->mediaManager->save(null, ['id' => 1, 'locale' => 'en', 'focusPointX' => 1, 'focusPointY' => 2], 1);
    }

    public function testMediaPropertiesProvider(): void
    {
        $uploadedFile = $this->prophesize(UploadedFile::class)->willBeConstructedWith([__DIR__ . \DIRECTORY_SEPARATOR . 'test.txt', 1, null, null, 1, true]);
        $uploadedFile->getClientOriginalName()->willReturn('test.ogg');
        $uploadedFile->getPathname()->willReturn('');
        $uploadedFile->getSize()->willReturn('123');
        $uploadedFile->getMimeType()->willReturn('video/ogg');
        $this->mediaRepository->createNew()->willReturn(new Media());

        $this->storage->save(Argument::cetera())->willReturn([]);
        $this->mediaPropertiesProvider
            ->provide($uploadedFile->reveal())
            ->willReturn(['key' => 'value'])
            ->shouldBeCalled();

        $media = $this->mediaManager->save($uploadedFile->reveal(), ['locale' => 'en', 'title' => 'test'], null);
        $this->assertNotNull($media);
        $this->assertSame(['key' => 'value'], $media->getProperties());
    }

    /**
     * @return iterable<array{
     *     0: int[],
     *     1: User|null,
     *     2: int|null,
     *     3: Media[],
     *     4: Media[],
     * }>
     */
    public static function provideGetByIds(): iterable
    {
        $media1 = static::createMedia(1);
        $media2 = static::createMedia(2);
        $media3 = static::createMedia(3);

        $user = new User();

        return [
            [[1, 2, 3], null, null, [$media1, $media2, $media3], [$media1, $media2, $media3]],
            [[2, 1, 3], $user, 64, [$media1, $media2, $media3], [$media2, $media1, $media3]],
            [[4, 1, 2], null, null, [$media1, $media2], [$media1, $media2]],
        ];
    }

    /**
     * @return iterable<array{
     *     0: string,
     *     1: string,
     *     2: string,
     *     3: string
     * }>
     */
    public static function provideSpecialCharacterFileName()
    {
        return [
            ['aäüßa', 'aäüßa', 'aaeuesa', ''],
            ['aäüßa.mp4', 'aäüßa', 'aaeuesa', '.mp4'],
        ];
    }

    /**
     * @return iterable<array{
     *     0: int,
     *     1: string,
     *     2: int,
     *     3: string
     * }>
     */
    public static function provideSpecialCharacterUrl()
    {
        return [
            [1, 'aäüßa.mp4', 2, '/download/1/media/a%C3%A4%C3%BC%C3%9Fa.mp4?v=2'],
            [1, 'aäüßa', 2, '/download/1/media/a%C3%A4%C3%BC%C3%9Fa?v=2'],
            [2, 'Sulu & Enterprise.doc', 2, '/download/2/media/Sulu%20%26%20Enterprise.doc?v=2'],
        ];
    }

    protected static function createMedia(int $id): Media
    {
        $media = new Media();
        self::setPrivateProperty($media, 'id', $id);

        $file = new File();
        $fileVersion = new FileVersion();
        $fileVersion->setName('Media' . $id);
        $file->addFileVersion($fileVersion);
        $media->addFile($file);

        return $media;
    }
}
