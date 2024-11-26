<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\SmartContent;

use PHPCR\ItemNotFoundException;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Content\SmartContent\ContentDataItem;
use Sulu\Component\Content\SmartContent\PageDataProvider;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\DatasourceItem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PageDataProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param array|null $initValue
     *
     * @return ContentQueryBuilderInterface
     */
    private function getContentQueryBuilder($initValue = null)
    {
        $mock = $this->prophesize(ContentQueryBuilderInterface::class);

        if (null !== $initValue) {
            $mock->init($initValue)->shouldBeCalled();
        }

        return $mock->reveal();
    }

    /**
     * @param int $limit
     * @param int $page
     * @param array $result
     *
     * @return ContentQueryExecutorInterface
     */
    private function getContentQueryExecutor($limit = -1, $page = 1, $result = [], $permission = 64)
    {
        $mock = $this->prophesize(ContentQueryExecutorInterface::class);

        $mock->execute(
            'sulu_io',
            ['en'],
            Argument::type(ContentQueryBuilderInterface::class),
            true,
            -1,
            $limit > -1 ? $limit + 1 : null,
            $limit > -1 ? $limit * ($page - 1) : null,
            false,
            $permission
        )->willReturn($result);

        return $mock->reveal();
    }

    /**
     * @param array $pages
     *
     * @return DocumentManagerInterface
     */
    private function getDocumentManager($pages = [])
    {
        $mock = $this->prophesize(DocumentManagerInterface::class);

        foreach ($pages as $uuid => $value) {
            $mock->find($uuid, 'en')->willReturn($value);
        }

        return $mock->reveal();
    }

    /**
     * @return LazyLoadingValueHolderFactory
     */
    private function getProxyFactory()
    {
        $writer = new FileWriterGeneratorStrategy(new FileLocator(\sys_get_temp_dir()));
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy($writer);

        return new LazyLoadingValueHolderFactory($configuration);
    }

    private function getSession($throw = false)
    {
        $mock = $this->prophesize(SessionInterface::class);

        if ($throw) {
            $mock->getNodeByIdentifier(Argument::any())->willThrow(ItemNotFoundException::class);
        }

        return $mock->reveal();
    }

    public function getMetadataProvider(): MetadataProviderInterface
    {
        /** @var FormMetadataProvider|ObjectProphecy $formMetadataProvider */
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);

        return $formMetadataProvider->reveal();
    }

    public function testGetConfiguration(): void
    {
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            false,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $configuration = $provider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
    }

    public function testEnabledAudienceTargeting(): void
    {
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            false,
            ['view' => 64],
            true,
            $this->getMetadataProvider(),
        );

        $configuration = $provider->getConfiguration();

        $this->assertTrue($configuration->hasAudienceTargeting());
    }

    public function testDisabledAudienceTargeting(): void
    {
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            false,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $configuration = $provider->getConfiguration();

        $this->assertFalse($configuration->hasAudienceTargeting());
    }

    public function testGetTypesConfiguration(): void
    {
        /** @var TokenStorageInterface|ObjectProphecy $tokenStorage */
        $tokenStorage = $this->prophesize(TokenStorageInterface::class);

        /** @var TokenInterface|ObjectProphecy $token */
        $token = $this->prophesize(TokenInterface::class);
        /** @var UserInterface|ObjectProphecy $user */
        $user = $this->prophesize(UserInterface::class);

        $tokenStorage->getToken()
            ->shouldBeCalled()
            ->willReturn($token->reveal());
        $token->getUser()
            ->shouldBeCalled()
            ->willReturn($user);
        $user->getLocale()
            ->shouldBeCalled()
            ->willReturn('en');

        $formMetadata1 = new FormMetadata();
        $formMetadata1->setName('template-1');
        $formMetadata1->setTitle('translated-template-1');

        $formMetadata2 = new FormMetadata();
        $formMetadata2->setName('template-2');
        $formMetadata2->setTitle('translated-template-2');

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('template-1', $formMetadata1);
        $typedFormMetadata->addForm('template-2', $formMetadata2);

        /** @var FormMetadataProvider|ObjectProphecy $formMetadataProvider */
        $formMetadataProvider = $this->prophesize(FormMetadataProvider::class);

        $formMetadataProvider->getMetadata('page', 'en', [])
            ->shouldBeCalled()
            ->willReturn($typedFormMetadata);

        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            false,
            ['view' => 64],
            false,
            $formMetadataProvider->reveal(),
            $tokenStorage->reveal()
        );

        $configuration = $provider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);

        $this->assertCount(2, $configuration->getTypes());
        $this->assertSame('template-1', $configuration->getTypes()[0]->getValue());
        $this->assertSame('translated-template-1', $configuration->getTypes()[0]->getName());
        $this->assertSame('template-2', $configuration->getTypes()[1]->getValue());
        $this->assertSame('translated-template-2', $configuration->getTypes()[1]->getName());
    }

    public function testGetDefaultParameter(): void
    {
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            false,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $parameter = $provider->getDefaultPropertyParameter();

        foreach ($parameter as $p) {
            $this->assertInstanceOf(PropertyParameter::class, $p);
        }

        $this->assertArrayHasKey('properties', $parameter);
    }

    public function testResolveDataItemsNoDataSource(): void
    {
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            false,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $result = $provider->resolveDataItems(
            ['excluded' => ['123-123-123']],
            ['properties' => ['my-properties' => true]],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            2,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals([], $result->getItems());
    }

    public function testResolveDataItemsNoResult(): void
    {
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(
                [
                    'config' => ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
                    'properties' => ['my-properties' => true],
                    'excluded' => ['123-123-123'],
                    'published' => false,
                ]
            ),
            $this->getContentQueryExecutor(2, 2, []),
            $this->getDocumentManager(),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            true,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $result = $provider->resolveDataItems(
            ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            2,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals([], $result->getItems());
        $this->assertFalse($result->getHasNextPage());
    }

    public function testResolveDataItemsHasNextPage(): void
    {
        $data = [
            ['id' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'],
            ['id' => '123-123-456', 'title' => 'My-Page-1', 'path' => '/my-page-1'],
            ['id' => '123-123-789', 'title' => 'My-Page-2', 'path' => '/my-page-2'],
        ];

        $document1 = $this->prophesize(BasePageDocument::class);
        $document2 = $this->prophesize(BasePageDocument::class);

        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(
                [
                    'config' => ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
                    'properties' => ['my-properties' => true],
                    'excluded' => ['123-123-123'],
                    'published' => false,
                ]
            ),
            $this->getContentQueryExecutor(2, 1, $data),
            $this->getDocumentManager(['123-123-123' => $document1->reveal(), '123-123-456' => $document2->reveal()]),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            true,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $result = $provider->resolveDataItems(
            ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            1,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $items = $result->getItems();
        $this->assertEquals($data[0]['id'], $items[0]->getId());
        $this->assertTrue($items[0]->getResource()->initializeProxy());
        $this->assertEquals($document1->reveal(), $items[0]->getResource()->getWrappedValueHolderValue());
        $this->assertEquals($data[1]['id'], $items[1]->getId());
        $this->assertTrue($items[1]->getResource()->initializeProxy());
        $this->assertEquals($document2->reveal(), $items[1]->getResource()->getWrappedValueHolderValue());
        $this->assertTrue($result->getHasNextPage());
    }

    public function testResolveDataItemsOnlyPublished(): void
    {
        $data = [
            ['id' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'],
            ['id' => '123-123-456', 'title' => 'My-Page-1', 'path' => '/my-page-1'],
            ['id' => '123-123-789', 'title' => 'My-Page-2', 'path' => '/my-page-2'],
        ];

        $document1 = $this->prophesize(BasePageDocument::class);
        $document2 = $this->prophesize(BasePageDocument::class);

        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(
                [
                    'config' => ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
                    'properties' => ['my-properties' => true],
                    'excluded' => ['123-123-123'],
                    'published' => true,
                ]
            ),
            $this->getContentQueryExecutor(2, 1, $data),
            $this->getDocumentManager(['123-123-123' => $document1->reveal(), '123-123-456' => $document2->reveal()]),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            false,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $result = $provider->resolveDataItems(
            ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            1,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $items = $result->getItems();
        $this->assertEquals($data[0]['id'], $items[0]->getId());
        $this->assertTrue($items[0]->getResource()->initializeProxy());
        $this->assertEquals($document1->reveal(), $items[0]->getResource()->getWrappedValueHolderValue());
        $this->assertEquals($data[1]['id'], $items[1]->getId());
        $this->assertTrue($items[1]->getResource()->initializeProxy());
        $this->assertEquals($document2->reveal(), $items[1]->getResource()->getWrappedValueHolderValue());
        $this->assertTrue($result->getHasNextPage());
    }

    public function testResolveResourceItems(): void
    {
        $data = [
            ['id' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'],
            ['id' => '123-123-456', 'title' => 'My-Page-1', 'path' => '/my-page-1'],
            ['id' => '123-123-789', 'title' => 'My-Page-2', 'path' => '/my-page-2'],
        ];

        $document1 = $this->prophesize(BasePageDocument::class);
        $document2 = $this->prophesize(BasePageDocument::class);

        $referenceStore = new ReferenceStore();
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(
                [
                    'config' => ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
                    'properties' => ['my-properties' => true],
                    'excluded' => ['123-123-123'],
                    'published' => false,
                ]
            ),
            $this->getContentQueryExecutor(2, 1, $data),
            $this->getDocumentManager(['123-123-123' => $document1->reveal(), '123-123-456' => $document2->reveal()]),
            $this->getProxyFactory(),
            $this->getSession(),
            $referenceStore,
            true,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $result = $provider->resolveResourceItems(
            ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            1,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        /** @var ContentDataItem[] $items */
        $items = $result->getItems();

        $this->assertEquals($data[0]['id'], $items[0]->getId());
        $this->assertEquals($data[0]['path'], $items[0]['path']);
        $this->assertTrue($items[0]->getResource()->initializeProxy());
        $this->assertEquals($document1->reveal(), $items[0]->getResource()->getWrappedValueHolderValue());

        $this->assertEquals($data[1]['id'], $items[1]->getId());
        $this->assertEquals($data[1]['path'], $items[1]['path']);
        $this->assertTrue($items[1]->getResource()->initializeProxy());
        $this->assertEquals($document2->reveal(), $items[1]->getResource()->getWrappedValueHolderValue());

        $this->assertTrue($result->getHasNextPage());
        $this->assertEquals(['123-123-123', '123-123-456'], $referenceStore->getAll());
    }

    public function testResolveResourceItemsWithoutPathParameter(): void
    {
        $data = [
            ['id' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'],
            ['id' => '123-123-456', 'title' => 'My-Page-1', 'path' => '/my-page-1'],
            ['id' => '123-123-789', 'title' => 'My-Page-2', 'path' => '/my-page-2'],
        ];

        $document1 = $this->prophesize(BasePageDocument::class);
        $document2 = $this->prophesize(BasePageDocument::class);

        $referenceStore = new ReferenceStore();
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(
                [
                    'config' => ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
                    'properties' => ['my-properties' => true],
                    'excluded' => ['123-123-123'],
                    'published' => false,
                ]
            ),
            $this->getContentQueryExecutor(2, 1, $data),
            $this->getDocumentManager(['123-123-123' => $document1->reveal(), '123-123-456' => $document2->reveal()]),
            $this->getProxyFactory(),
            $this->getSession(),
            $referenceStore,
            true,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
            null,
            ['path' => false]
        );

        $result = $provider->resolveResourceItems(
            ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            1,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        /** @var ContentDataItem[] $items */
        $items = $result->getItems();

        $this->assertEquals($data[0]['id'], $items[0]->getId());
        $this->assertNull($items[2]['path'] ?? null);

        $this->assertEquals($data[1]['id'], $items[1]->getId());
        $this->assertNull($items[1]['path'] ?? null);

        $this->assertTrue($result->getHasNextPage());
        $this->assertEquals(['123-123-123', '123-123-456'], $referenceStore->getAll());
    }

    public function testResolveDataItemsNoPagination(): void
    {
        $data = [
            ['id' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'],
            ['id' => '123-123-456', 'title' => 'My-Page-1', 'path' => '/my-page-1'],
            ['id' => '123-123-789', 'title' => 'My-Page-2', 'path' => '/my-page-2'],
        ];

        $document1 = $this->prophesize(BasePageDocument::class);
        $document2 = $this->prophesize(BasePageDocument::class);
        $document3 = $this->prophesize(BasePageDocument::class);

        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(
                [
                    'config' => ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
                    'properties' => ['my-properties' => true],
                    'excluded' => ['123-123-123'],
                    'published' => false,
                ]
            ),
            $this->getContentQueryExecutor(-1, null, $data),
            $this->getDocumentManager(
                ['123-123-123' => $document1->reveal(), '123-123-456' => $document2->reveal(), '123-123-789' => $document3->reveal()]
            ),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            true,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $result = $provider->resolveDataItems(
            ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en']
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        /** @var ContentDataItem[] $items */
        $items = $result->getItems();
        $this->assertEquals($data[0]['id'], $items[0]->getId());
        $this->assertTrue($items[0]->getResource()->initializeProxy());
        $this->assertEquals($document1->reveal(), $items[0]->getResource()->getWrappedValueHolderValue());
        $this->assertEquals($data[1]['id'], $items[1]->getId());
        $this->assertTrue($items[1]->getResource()->initializeProxy());
        $this->assertEquals($document2->reveal(), $items[1]->getResource()->getWrappedValueHolderValue());
        $this->assertEquals($data[2]['id'], $items[2]->getId());
        $this->assertTrue($items[2]->getResource()->initializeProxy());
        $this->assertEquals($document3->reveal(), $items[2]->getResource()->getWrappedValueHolderValue());
        $this->assertFalse($result->getHasNextPage());
    }

    public function testResolveDataItemsWithDeletedDataSource(): void
    {
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory(),
            $this->getSession(true),
            new ReferenceStore(),
            true,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $result = $provider->resolveDataItems(
            ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            10
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertCount(0, $result->getItems());
    }

    public function testResolveDatasource(): void
    {
        $data = ['id' => '123-123-123', 'title' => 'My-Page', 'url' => '/my-page'];

        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(
                ['ids' => [$data['id']], 'properties' => ['my-properties' => true], 'published' => false]
            ),
            $this->getContentQueryExecutor(0, 1, [$data], null),
            $this->getDocumentManager([$data['id'] => $data]),
            $this->getProxyFactory(),
            $this->getSession(),
            new ReferenceStore(),
            false,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $result = $provider->resolveDatasource(
            $data['id'],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en']
        );

        $this->assertInstanceOf(DatasourceItem::class, $result);
        $this->assertEquals($data['id'], $result->getId());
        $this->assertEquals($data['title'], $result->getTitle());
        $this->assertEquals($data['url'], $result->getPath());
        $this->assertNull($result->getImage());
    }

    public function testContentDataItem(): void
    {
        $data = ['id' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'];
        $resource = new \stdClass();
        $item = new ContentDataItem($data, $resource);

        $this->assertEquals($data['id'], $item->getId());
        $this->assertEquals($data['title'], $item->getTitle());
        $this->assertEquals($resource, $item->getResource());

        $this->assertNull($item->getImage());
    }

    public function testResolveResourceItemsExcluded(): void
    {
        $data = [
            ['id' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'],
            ['id' => '123-123-456', 'title' => 'My-Page-1', 'path' => '/my-page-1'],
            ['id' => '123-123-789', 'title' => 'My-Page-2', 'path' => '/my-page-2'],
        ];

        $document1 = $this->prophesize(BasePageDocument::class);
        $document2 = $this->prophesize(BasePageDocument::class);

        $documents = [$document1->reveal(), $document2->reveal()];

        $referenceStore = new ReferenceStore();
        $referenceStore->add('123-456-789');
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(
                [
                    'config' => ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
                    'properties' => ['my-properties' => true],
                    'excluded' => ['123-123-123', '123-456-789'],
                    'published' => false,
                ]
            ),
            $this->getContentQueryExecutor(2, 1, $data),
            $this->getDocumentManager(['123-123-123' => $document1->reveal(), '123-123-456' => $document2->reveal()]),
            $this->getProxyFactory(),
            $this->getSession(),
            $referenceStore,
            true,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $result = $provider->resolveResourceItems(
            ['dataSource' => '123-123-123', 'excluded' => ['123-123-123']],
            [
                'properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection'),
                'exclude_duplicates' => new PropertyParameter('exclude_duplicates', true),
            ],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            1,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        /** @var ContentDataItem[] $items */
        $items = $result->getItems();
        for ($i = 0, $length = \count($items); $i < $length; ++$i) {
            $this->assertEquals($data[$i]['id'], $items[$i]->getId());
            $this->assertEquals($data[$i], $items[$i]->jsonSerialize());

            $this->assertTrue($items[$i]->getResource()->initializeProxy());
            $this->assertEquals($documents[$i], $items[$i]->getResource()->getWrappedValueHolderValue());
        }

        $this->assertTrue($result->getHasNextPage());
        $this->assertEquals(['123-456-789', '123-123-123', '123-123-456'], $referenceStore->getAll());
    }

    public function testResolveResourceItemsNullDataSource(): void
    {
        $referenceStore = new ReferenceStore();
        $provider = new PageDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory(),
            $this->getSession(),
            $referenceStore,
            true,
            ['view' => 64],
            false,
            $this->getMetadataProvider(),
        );

        $result = $provider->resolveResourceItems(
            ['dataSource' => null],
            [
                'properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection'),
                'exclude_duplicates' => new PropertyParameter('exclude_duplicates', true),
            ],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            1,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);

        $this->assertEquals([], $result->getItems());
    }
}
