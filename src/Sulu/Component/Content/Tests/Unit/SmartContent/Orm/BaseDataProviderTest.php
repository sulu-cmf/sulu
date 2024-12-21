<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\SmartContent\Orm;

use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\ItemInterface;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\ResourceItemInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Security as WebspaceSecurity;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;

class BaseDataProviderTest extends TestCase
{
    use ProphecyTrait;

    private TestBaseDataProvier $provider;

    /** @var ObjectProphecy<DataProviderRepositoryInterface> */
    private ObjectProphecy $repository;

    /** @var ObjectProphecy<ArraySerializerInterface> */
    private ObjectProphecy $serializer;

    public function setUp(): void
    {
        $this->repository = $this->prophesize(DataProviderRepositoryInterface::class);
        $this->serializer = $this->prophesize(ArraySerializerInterface::class);
        $this->provider = new TestBaseDataProvier($this->repository->reveal(), $this->serializer->reveal());
    }

    public function testGetDefaultPropertyParameter(): void
    {
        $this->assertEquals([], $this->provider->getDefaultPropertyParameter());
    }

    public static function configurationProvider()
    {
        return [
            [true, true, true, true, true, []],
            [true, false, true, true, true, []],
            [true, true, false, true, true, []],
            [true, true, true, false, true, []],
            [true, true, true, true, false, []],
            [true, true, true, true, true, ['asdf', 'qwertz']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('configurationProvider')]
    public function testInitConfiguration($tags, $categories, $limit, $presentAs, $paginated, $sorting): void
    {
        $repository = $this->prophesize(DataProviderRepositoryInterface::class);

        $class = new \ReflectionClass(BaseDataProvider::class);
        $method = $class->getMethod('initConfiguration');
        $method->setAccessible(true);

        /** @var ProviderConfigurationInterface $configuration */
        $configuration = $method->invokeArgs(
            $this->provider,
            [
                $tags,
                $categories,
                $limit,
                $presentAs,
                $paginated,
                $sorting,
            ]
        );

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
        $this->assertEquals(false, $configuration->hasDatasource());
        $this->assertEquals($tags, $configuration->hasTags());
        $this->assertEquals($categories, $configuration->hasCategories());
        $this->assertEquals($limit, $configuration->hasLimit());
        $this->assertEquals($presentAs, $configuration->hasPresentAs());
        $this->assertEquals($sorting, $configuration->getSorting());
        $this->assertEquals($paginated, $configuration->hasPagination());
        $this->assertEquals(\count($sorting) > 0, $configuration->hasSorting());
    }

    public function testResolveDataSource(): void
    {
        $this->assertNull($this->provider->resolveDatasource('', [], []));
    }

    public static function filtersProvider()
    {
        return [
            [
                [],
                null,
                1,
                null,
                [],
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
                false,
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
            ],
            [
                [],
                null,
                1,
                null,
                ['test' => 1],
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
                false,
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
            ],
            [
                ['tags' => []],
                null,
                1,
                null,
                [],
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
                false,
                [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]],
            ],
            [
                [],
                2,
                1,
                null,
                [],
                [['id' => 1], ['id' => 2]],
                false,
                [['id' => 1], ['id' => 2]],
            ],
            [
                [],
                null,
                1,
                2,
                [],
                [['id' => 1], ['id' => 2]],
                false,
                [['id' => 1], ['id' => 2]],
            ],
            [
                [],
                null,
                1,
                2,
                [],
                [['id' => 1], ['id' => 2], ['id' => 3]],
                true,
                [['id' => 1], ['id' => 2]],
            ],
            [
                [],
                null,
                2,
                2,
                [],
                [['id' => 2], ['id' => 3]],
                false,
                [['id' => 2], ['id' => 3]],
            ],
            [
                [],
                null,
                2,
                2,
                [],
                [['id' => 2], ['id' => 3], ['id' => 4]],
                true,
                [['id' => 2], ['id' => 3]],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filtersProvider')]
    public function testResolveFilters(
        array $filters,
        $limit,
        $page,
        $pageSize,
        $options,
        $repositoryResult,
        $hasNextPage,
        $items
    ): void {
        $this->repository->findByFilters($filters, $page, $pageSize, $limit, 'en', $options, null, null)
            ->shouldBeCalled()->willReturn($repositoryResult);

        $class = new \ReflectionClass(BaseDataProvider::class);
        $method = $class->getMethod('resolveFilters');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->provider, [$filters, 'en', $limit, $page, $pageSize, $options]);

        $this->assertEquals($items, $result[0]);
        $this->assertEquals($hasNextPage, $result[1]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filtersProvider')]
    public function testResolveDataItems(
        array $filters,
        $limit,
        $page,
        $pageSize,
        $options,
        $repositoryResult,
        $hasNextPage,
        $items
    ): void {
        $this->repository->findByFilters($filters, $page, $pageSize, $limit, 'en', [], null, null)
            ->shouldBeCalled()
            ->willReturn($repositoryResult);

        $this->provider->returnValue = $items;

        $result = $this->provider->resolveDataItems(
            $filters,
            [],
            ['locale' => 'en', 'webspace' => 'sulu_io'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertEquals($items, $result->getItems());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filtersProvider')]
    public function testResolveResourceItems(
        array $filters,
        $limit,
        $page,
        $pageSize,
        $options,
        $repositoryResult,
        $hasNextPage,
        $items
    ): void {
        $mockedItems = \array_map(
            function($item) {
                $mock = $this->prophesize(ResourceItemInterface::class);
                $mock->getId()->willReturn($item['id']);

                return $mock->reveal();
            },
            $repositoryResult
        );

        $this->repository->findByFilters($filters, $page, $pageSize, $limit, 'en', [], null, null)
            ->shouldBeCalled()
            ->willReturn($mockedItems);

        $this->serializer->serialize(
            Argument::type(ResourceItemInterface::class),
            Argument::type(SerializationContext::class)
        )->will(
            function($args) {
                return ['id' => $args[0]->getId()];
            }
        )->shouldBeCalled();

        $result = $this->provider->resolveResourceItems(
            $filters,
            [],
            ['locale' => 'en', 'webspace' => 'sulu_io'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertCount(\count($items), $result->getItems());

        for ($i = 0, $len = \count($items); $i < $len; ++$i) {
            $expected = $items[$i];
            $item = $result->getItems()[$i];

            $this->assertEquals($expected['id'], $item->getResource()->getId());
            $this->assertEquals($expected['id'], $item['id']);
        }
    }

    public function testResolveResourceItemsWithUser(): void
    {
        $user = $this->prophesize(UserInterface::class);

        $this->repository->findByFilters([], 1, null, -1, 'en', [], $user->reveal(), 64)->shouldBeCalled()->willReturn([]);

        $security = $this->prophesize(\class_exists(Security::class) ? Security::class : SymfonyCoreSecurity::class);
        $security->getUser()->willReturn($user->reveal());

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $webspace = new Webspace();
        $webspaceSecurity = new WebspaceSecurity();
        $webspaceSecurity->setSystem('website');
        $webspaceSecurity->setPermissionCheck(true);
        $webspace->setSecurity($webspaceSecurity);
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->provider = new TestBaseDataProvier(
            $this->repository->reveal(),
            $this->serializer->reveal(),
            null,
            $security->reveal(),
            $requestAnalyzer->reveal(),
            ['view' => 64],
        );

        $this->provider->resolveResourceItems(
            [],
            [],
            ['locale' => 'en', 'webspace' => 'sulu_io'],
            -1,
            1,
            null
        );
    }

    public function testResolveResourceItemsWithUserWithoutPermissionCheck(): void
    {
        $user = $this->prophesize(UserInterface::class);

        $this->repository->findByFilters([], 1, null, -1, 'en', [], null, null)->shouldBeCalled()->willReturn([]);

        $serializer = $this->prophesize(ArraySerializerInterface::class);

        $security = $this->prophesize(\class_exists(Security::class) ? Security::class : SymfonyCoreSecurity::class);
        $security->getUser()->willReturn($user->reveal());

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $webspace = new Webspace();
        $webspaceSecurity = new WebspaceSecurity();
        $webspaceSecurity->setSystem('website');
        $webspaceSecurity->setPermissionCheck(false);
        $webspace->setSecurity($webspaceSecurity);
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        $provider = new class(
            $this->repository->reveal(),
            $serializer->reveal(),
            null,
            $security->reveal(),
            $requestAnalyzer->reveal(),
            ['view' => 64],
        ) extends BaseDataProvider {
            /**
             * Decorates result as data item.
             *
             * @param object[] $data
             *
             * @return ItemInterface[]
             */
            protected function decorateDataItems(array $data)
            {
                return [];
            }
        };

        $provider->resolveResourceItems(
            [],
            [],
            ['locale' => 'en', 'webspace' => 'sulu_io'],
            -1,
            1,
            null
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filtersProvider')]
    public function testResolveResourceItemsWithReferenceStore(
        array $filters,
        $limit,
        $page,
        $pageSize,
        $options,
        $repositoryResult,
        $hasNextPage,
        $items
    ): void {
        $mockedItems = \array_map(
            function($item) {
                $mock = $this->prophesize(ResourceItemInterface::class);
                $mock->getId()->willReturn($item['id']);

                return $mock->reveal();
            },
            $repositoryResult
        );

        $this->repository->findByFilters($filters, $page, $pageSize, $limit, 'en', [], null, null)
            ->shouldBeCalled()
            ->willReturn($mockedItems);

        $this->serializer->serialize(
            Argument::type(ResourceItemInterface::class),
            Argument::type(SerializationContext::class)
        )->will(
            function($args) {
                return ['id' => $args[0]->getId()];
            }
        )->shouldBeCalled();

        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);
        foreach ($items as $item) {
            $referenceStore->add($item['id']);
        }

        $result = $this->provider->resolveResourceItems(
            $filters,
            [],
            ['locale' => 'en', 'webspace' => 'sulu_io'],
            $limit,
            $page,
            $pageSize
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals($hasNextPage, $result->getHasNextPage());
        $this->assertCount(\count($items), $result->getItems());

        for ($i = 0, $len = \count($items); $i < $len; ++$i) {
            $expected = $items[$i];
            $item = $result->getItems()[$i];

            $this->assertEquals($expected['id'], $item->getResource()->getId());
            $this->assertEquals($expected['id'], $item['id']);
        }
    }
}
