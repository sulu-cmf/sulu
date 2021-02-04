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
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaDataProviderTest extends TestCase
{
    public function testGetConfiguration()
    {
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new MediaDataProvider(
            $this->getRepository(),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal()
        );

        $configuration = $provider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
    }

    public function testEnabledAudienceTargeting()
    {
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new MediaDataProvider(
            $this->getRepository(),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal(),
            true
        );

        $configuration = $provider->getConfiguration();

        $this->assertTrue($configuration->hasAudienceTargeting());
    }

    public function testDisabledAudienceTargeting()
    {
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new MediaDataProvider(
            $this->getRepository(),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal(),
            false
        );

        $configuration = $provider->getConfiguration();

        $this->assertFalse($configuration->hasAudienceTargeting());
    }

    public function testGetTypesConfiguration()
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
            $this->getRepository(),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal(),
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

    public function testGetDefaultParameter()
    {
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new MediaDataProvider(
            $this->getRepository(),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal()
        );

        $parameter = $provider->getDefaultPropertyParameter();

        $this->assertEquals(
            [
                'mimetype_parameter' => new PropertyParameter('mimetype_parameter', 'mimetype', 'string'),
                'type_parameter' => new PropertyParameter('type_parameter', 'type', 'string'),
            ],
            $parameter
        );
    }

    public function dataItemsDataProvider()
    {
        $medias = [
            $this->createMedia(1, 'Test-1')->reveal(),
            $this->createMedia(2, 'Test-2')->reveal(),
            $this->createMedia(3, 'Test-3')->reveal(),
        ];

        $dataItems = [];
        foreach ($medias as $media) {
            $dataItems[] = $this->createDataItem($media);
        }

        return [
            [['dataSource' => 42, 'tags' => [1]], null, 1, 3, $medias, false, $dataItems],
            [['dataSource' => 42, 'tags' => [1]], null, 1, 2, $medias, true, \array_slice($dataItems, 0, 2)],
            [['dataSource' => 42, 'tags' => [1]], 5, 1, 2, $medias, true, \array_slice($dataItems, 0, 2)],
            [['dataSource' => 42, 'tags' => [1]], 1, 1, 2, \array_slice($medias, 0, 1), false, \array_slice($dataItems, 0, 1)],
        ];
    }

    /**
     * @dataProvider dataItemsDataProvider
     */
    public function testResolveDataItems($filters, $limit, $page, $pageSize, $repositoryResult, $hasNextPage, $items)
    {
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new MediaDataProvider(
            $this->getRepository($filters, $page, $pageSize, $limit, $repositoryResult),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal()
        );

        $result = $provider->resolveDataItems(
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

    public function resourceItemsDataProvider()
    {
        $medias = [
            $this->createMedia(1, 'Test-1')->reveal(),
            $this->createMedia(2, 'Test-2')->reveal(),
            $this->createMedia(3, 'Test-3')->reveal(),
        ];

        $resourceItems = [];
        foreach ($medias as $media) {
            $resourceItems[] = $this->createResourceItem($media);
        }

        return [
            [['dataSource' => 42, 'tags' => [1]], null, 1, 3, $medias, false, $resourceItems],
            [['dataSource' => 42, 'tags' => [1]], null, 1, 2, $medias, true, \array_slice($resourceItems, 0, 2)],
            [['dataSource' => 42, 'tags' => [1]], 5, 1, 2, $medias, true, \array_slice($resourceItems, 0, 2)],
            [['dataSource' => 42, 'tags' => [1]], 1, 1, 2, \array_slice($medias, 0, 1), false, \array_slice($resourceItems, 0, 1)],
        ];
    }

    /**
     * @dataProvider resourceItemsDataProvider
     */
    public function testResolveResourceItems(
        $filters,
        $limit,
        $page,
        $pageSize,
        $repositoryResult,
        $hasNextPage,
        $items
    ) {
        $serializeCallback = function(Media $media) {
            return $this->serialize($media);
        };

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $serializer->serialize(Argument::type(Media::class), Argument::type(SerializationContext::class))
            ->will(
                function($args) use ($serializeCallback) {
                    return $serializeCallback($args[0]);
                }
            );

        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new MediaDataProvider(
            $this->getRepository($filters, $page, $pageSize, $limit, $repositoryResult),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal()
        );

        $result = $provider->resolveResourceItems(
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

    public function testResolveDataSource()
    {
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $collectionManager = $this->prophesize(CollectionManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $provider = new MediaDataProvider(
            $this->getRepository(),
            $collectionManager->reveal(),
            $serializer->reveal(),
            $requestStack->reveal(),
            $referenceStore->reveal()
        );

        $collection = $this->prophesize(Collection::class);
        $collection->getId()->willReturn(1);
        $collection->getTitle()->willReturn('test');

        $collectionManager->getById('1', 'de')->willReturn($collection->reveal());
        $result = $provider->resolveDatasource('1', [], ['locale' => 'de']);

        $this->assertInstanceOf(DatasourceItem::class, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals('test', $result->getTitle());
    }

    /**
     * @return DataProviderRepositoryInterface
     */
    private function getRepository(
        $filters = [],
        $page = null,
        $pageSize = 0,
        $limit = null,
        $result = [],
        $options = ['webspace' => 'sulu_io', 'locale' => 'en']
    ) {
        $mock = $this->prophesize(DataProviderRepositoryInterface::class);

        $mock->findByFilters($filters, $page, $pageSize, $limit, 'en', $options)->willReturn($result);

        return $mock->reveal();
    }

    private function createMedia($id, $title, $tags = [])
    {
        $media = $this->prophesize(Media::class);
        $media->getId()->willReturn($id);
        $media->getTitle()->willReturn($title);
        $media->getTags()->willReturn($tags);

        return $media;
    }

    private function createDataItem(Media $media)
    {
        return new MediaDataItem($media);
    }

    private function createResourceItem(Media $media)
    {
        return new ArrayAccessItem($media->getId(), $this->serialize($media), $media);
    }

    private function serialize(Media $media)
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
