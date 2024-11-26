<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\Tests\Unit\SmartContent;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Media\SmartContent\MediaDataItem;
use Sulu\Component\Media\SmartContent\MediaDataProvider;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\DatasourceItem;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Security as WebspaceSecurity;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaDataProviderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<DataProviderRepositoryInterface>
     */
    private $dataProviderRepository;

    /**
     * @var ObjectProphecy<CollectionManagerInterface>
     */
    private $collectionManager;

    /**
     * @var ObjectProphecy<ArraySerializerInterface>
     */
    private $serializer;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $referenceStore;

    /**
     * @var MediaDataProvider
     */
    private $mediaDataProvider;

    /**
     * @var ObjectProphecy<Security|SymfonyCoreSecurity>
     */
    private $security;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    public function setUp(): void
    {
        $this->dataProviderRepository = $this->prophesize(DataProviderRepositoryInterface::class);
        $this->collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $this->serializer = $this->prophesize(ArraySerializerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->security = $this->prophesize(\class_exists(Security::class) ? Security::class : SymfonyCoreSecurity::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->mediaDataProvider = new MediaDataProvider(
            $this->dataProviderRepository->reveal(),
            $this->collectionManager->reveal(),
            $this->serializer->reveal(),
            $this->requestStack->reveal(),
            $this->referenceStore->reveal(),
            $this->security->reveal(),
            $this->requestAnalyzer->reveal(),
            ['view' => 64],
            false,
            $this->prophesize(EntityManagerInterface::class)->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
        );
    }

    public function testGetConfiguration(): void
    {
        $configuration = $this->mediaDataProvider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
    }

    public function testEnabledAudienceTargeting(): void
    {
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new MediaDataProvider(
            $this->dataProviderRepository->reveal(),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal(),
            null,
            $this->requestAnalyzer->reveal(),
            ['view' => 64],
            true
        );

        $configuration = $provider->getConfiguration();

        $this->assertTrue($configuration->hasAudienceTargeting());
    }

    public function testDisabledAudienceTargeting(): void
    {
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new MediaDataProvider(
            $this->dataProviderRepository->reveal(),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal(),
            null,
            $this->requestAnalyzer->reveal(),
            ['view' => 64],
            false
        );

        $configuration = $provider->getConfiguration();

        $this->assertFalse($configuration->hasAudienceTargeting());
    }

    public function testGetTypesConfiguration(): void
    {
        /** @var EntityManagerInterface|ObjectProphecy $entityManager */
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        /** @var ObjectRepository|ObjectProphecy $mediaTypeRepository */
        $mediaTypeRepository = $this->prophesize(ObjectRepository::class);

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $entityManager->getRepository(MediaType::class)
            ->shouldBeCalled()
            ->willReturn($mediaTypeRepository->reveal());

        $mediaType1 = new MediaType();
        $mediaType1->setId(1);
        $mediaType1->setName('image');

        $mediaType2 = new MediaType();
        $mediaType2->setId(2);
        $mediaType2->setName('audio');

        $mediaTypeRepository->findAll()
            ->shouldBeCalled()
            ->willReturn([$mediaType1, $mediaType2]);

        /** @var TranslatorInterface|ObjectProphecy $translator */
        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans('sulu_media.audio', [], 'admin')
            ->shouldBeCalled()
            ->willReturn('translated_audio');
        $translator->trans('sulu_media.image', [], 'admin')
            ->shouldBeCalled()
            ->willReturn('translated_image');

        $provider = new MediaDataProvider(
            $this->dataProviderRepository->reveal(),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal(),
            null,
            $this->requestAnalyzer->reveal(),
            ['view' => 64],
            false,
            $entityManager->reveal(),
            $translator->reveal()
        );

        $configuration = $provider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);

        $this->assertCount(2, $configuration->getTypes());
        $this->assertSame(1, $configuration->getTypes()[0]->getValue());
        $this->assertSame('translated_image', $configuration->getTypes()[0]->getName());
        $this->assertSame(2, $configuration->getTypes()[1]->getValue());
        $this->assertSame('translated_audio', $configuration->getTypes()[1]->getName());
    }

    public function testGetDefaultParameter(): void
    {
        $parameter = $this->mediaDataProvider->getDefaultPropertyParameter();

        $this->assertEquals(
            [
                'mimetype_parameter' => new PropertyParameter('mimetype_parameter', 'mimetype', 'string'),
                'type_parameter' => new PropertyParameter('type_parameter', 'type', 'string'),
            ],
            $parameter
        );
    }

    public static function dataItemsDataProvider()
    {
        $medias = [
            self::createMedia(1, 'Test-1'),
            self::createMedia(2, 'Test-2'),
            self::createMedia(3, 'Test-3'),
        ];

        $dataItems = [];
        foreach ($medias as $media) {
            $dataItems[] = self::createDataItem($media);
        }

        return [
            [['dataSource' => 42, 'tags' => [1]], null, 1, 3, $medias, false, $dataItems],
            [['dataSource' => 42, 'tags' => [1]], null, 1, 2, $medias, true, \array_slice($dataItems, 0, 2)],
            [['dataSource' => 42, 'tags' => [1]], 5, 1, 2, $medias, true, \array_slice($dataItems, 0, 2)],
            [['dataSource' => 42, 'tags' => [1]], 1, 1, 2, \array_slice($medias, 0, 1), false, \array_slice($dataItems, 0, 1)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataItemsDataProvider')]
    public function testResolveDataItems($filters, $limit, $page, $pageSize, $repositoryResult, $hasNextPage, $items): void
    {
        $this->dataProviderRepository
             ->findByFilters(
                 $filters,
                 $page,
                 $pageSize,
                 $limit,
                 'en',
                 ['webspace' => 'sulu_io', 'locale' => 'en'],
                 null,
                 null
             )
            ->willReturn($repositoryResult);

        $result = $this->mediaDataProvider->resolveDataItems(
            $filters,
            [],
            ['webspace' => 'sulu_io', 'locale' => 'en'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertEquals($items, $result->getItems());
    }

    public function testResolveDataItemsWithoutSecurity(): void
    {
        $mediaDataProvider = new MediaDataProvider(
            $this->dataProviderRepository->reveal(),
            $this->collectionManager->reveal(),
            $this->serializer->reveal(),
            $this->requestStack->reveal(),
            $this->referenceStore->reveal(),
            null,
            $this->requestAnalyzer->reveal(),
            ['view' => 64]
        );

        $medias = [
            self::createMedia(1, 'Test-1'),
            self::createMedia(2, 'Test-2'),
            self::createMedia(3, 'Test-3'),
        ];

        $dataItems = [];
        foreach ($medias as $media) {
            $dataItems[] = self::createDataItem($media);
        }

        $this->dataProviderRepository
             ->findByFilters(
                 ['dataSource' => 42, 'tags' => [1]],
                 1,
                 3,
                 null,
                 'en',
                 ['webspace' => 'sulu_io', 'locale' => 'en'],
                 null,
                 null
             )
            ->willReturn($medias);

        $result = $mediaDataProvider->resolveDataItems(
            ['dataSource' => 42, 'tags' => [1]],
            [],
            ['webspace' => 'sulu_io', 'locale' => 'en'],
            null,
            1,
            3
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        $this->assertEquals(false, $result->getHasNextPage());
        $this->assertEquals($dataItems, $result->getItems());
    }

    public static function resourceItemsDataProvider()
    {
        $medias = [
            self::createMedia(1, 'Test-1'),
            self::createMedia(2, 'Test-2'),
            self::createMedia(3, 'Test-3'),
        ];

        $user = new User();

        $resourceItems = [];
        foreach ($medias as $media) {
            $resourceItems[] = self::createResourceItem($media);
        }

        return [
            [['dataSource' => 42, 'tags' => [1]], null, 1, 3, $medias, false, $user, $resourceItems],
            [['dataSource' => 42, 'tags' => [1]], null, 1, 2, $medias, true, null, \array_slice($resourceItems, 0, 2)],
            [['dataSource' => 42, 'tags' => [1]], 5, 1, 2, $medias, true, null, \array_slice($resourceItems, 0, 2)],
            [['dataSource' => 42, 'tags' => [1]], 1, 1, 2, \array_slice($medias, 0, 1), false, null, \array_slice($resourceItems, 0, 1)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('resourceItemsDataProvider')]
    public function testResolveResourceItems(
        $filters,
        $limit,
        $page,
        $pageSize,
        $repositoryResult,
        $hasNextPage,
        $user,
        $items
    ): void {
        $webspace = new Webspace();
        $security = new WebspaceSecurity();
        $security->setSystem('website');
        $security->setPermissionCheck(true);
        $webspace->setSecurity($security);
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $serializeCallback = function(Media $media) {
            return $this->serialize($media);
        };

        $this->serializer->serialize(Argument::type(Media::class), Argument::type(SerializationContext::class))
            ->will(
                function($args) use ($serializeCallback) {
                    return $serializeCallback($args[0]);
                }
            );

        $this->security->getUser()->willReturn($user);

        $this->dataProviderRepository
             ->findByFilters(
                 $filters,
                 $page,
                 $pageSize,
                 $limit,
                 'en',
                 ['webspace' => 'sulu_io', 'locale' => 'en'],
                 $user,
                 64
             )
            ->willReturn($repositoryResult);

        $result = $this->mediaDataProvider->resolveResourceItems(
            $filters,
            [],
            ['webspace' => 'sulu_io', 'locale' => 'en'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertEquals($items, $result->getItems());
    }

    public function testResolveDataSource(): void
    {
        $collection = $this->prophesize(Collection::class);
        $collection->getId()->willReturn(1);
        $collection->getTitle()->willReturn('test');

        $this->collectionManager->getById('1', 'de')->willReturn($collection->reveal());
        $result = $this->mediaDataProvider->resolveDatasource('1', [], ['locale' => 'de']);

        $this->assertInstanceOf(DatasourceItem::class, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals('test', $result->getTitle());
    }

    private static function createMedia($id, $title, $tags = []): Media
    {
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setTitle($title);

        $fileVersion = new FileVersion();
        foreach ($tags as $tag) {
            $fileVersion->addTag($tag);
        }

        $entity = new MediaEntity();
        self::setPrivateProperty($entity, 'id', $id);

        $media = new Media($entity, 'de');
        self::setPrivateProperty($media, 'localizedMeta', $fileVersionMeta);
        self::setPrivateProperty($media, 'fileVersion', $fileVersion);

        return $media;
    }

    private static function createDataItem(Media $media)
    {
        return new MediaDataItem($media);
    }

    private static function createResourceItem(Media $media)
    {
        return new ArrayAccessItem($media->getId(), self::serialize($media), $media);
    }

    private static function serialize(Media $media)
    {
        return [
            'id' => $media->getId(),
            'title' => $media->getTitle(),
            'tags' => \array_map(
                function($tag) {
                    return $tag->getName();
                },
                $media->getTags()
            ),
        ];
    }
}
