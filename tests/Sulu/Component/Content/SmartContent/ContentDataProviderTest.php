<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\SmartContent;

use Prophecy\Argument;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderResult;

class ContentDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array|null $initValue
     *
     * @return ContentQueryBuilderInterface
     */
    private function getContentQueryBuilder($initValue = null)
    {
        $mock = $this->prophesize(ContentQueryBuilderInterface::class);

        if ($initValue !== null) {
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
    private function getContentQueryExecutor($limit = -1, $page = 1, $result = [])
    {
        $mock = $this->prophesize(ContentQueryExecutorInterface::class);

        $mock->execute(
            'sulu_io',
            ['en'],
            Argument::type(ContentQueryBuilderInterface::class),
            true,
            -1,
            ($limit > -1 ? $limit + 1 : null),
            ($limit > -1 ? $limit * ($page - 1) : null)
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
        $mock = $this->prophesize(LazyLoadingValueHolderFactory::class);
        $lazyLoading = $this->prophesize(LazyLoadingInterface::class);

        $mock->createProxy(PageDocument::class, Argument::any())->will(
            function ($args) use ($lazyLoading) {
                $wrappedObject = 1;
                $initializer = 1;
                $args[1]($wrappedObject, $lazyLoading->reveal(), null, [], $initializer);

                return $wrappedObject;
            }
        );

        return $mock->reveal();
    }

    public function testGetConfiguration()
    {
        $provider = new ContentDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory()
        );

        $configuration = $provider->getConfiguration();

        $this->assertInstanceOf(ProviderConfigurationInterface::class, $configuration);
    }

    public function testGetDefaultParameter()
    {
        $provider = new ContentDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory()
        );

        $parameter = $provider->getDefaultPropertyParameter();

        foreach ($parameter as $p) {
            $this->assertInstanceOf(PropertyParameter::class, $p);
        }

        $this->assertArrayHasKey('properties', $parameter);
    }

    public function testResolveFiltersNoDataSource()
    {
        $provider = new ContentDataProvider(
            $this->getContentQueryBuilder(),
            $this->getContentQueryExecutor(),
            $this->getDocumentManager(),
            $this->getProxyFactory()
        );

        $result = $provider->resolveFilters(
            ['excluded' => '123-123-123'],
            ['properties' => ['my-properties' => true]],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            2,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals([], $result->getItems());
    }

    public function testResolveFiltersNoResult()
    {
        $provider = new ContentDataProvider(
            $this->getContentQueryBuilder(
                [
                    'config' => ['dataSource' => '123-123-123', 'excluded' => '123-123-123'],
                    'properties' => ['my-properties' => true],
                    'excluded' => '123-123-123',
                ]
            ),
            $this->getContentQueryExecutor(2, 2, []),
            $this->getDocumentManager(),
            $this->getProxyFactory()
        );

        $result = $provider->resolveFilters(
            ['dataSource' => '123-123-123', 'excluded' => '123-123-123'],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            2,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals([], $result->getItems());
        $this->assertFalse($result->getHasNextPage());
        $this->assertEmpty($result->getReferencedUuids());
    }

    public function testResolveFiltersHasNextPage()
    {
        $data = [
            ['uuid' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'],
            ['uuid' => '123-123-456', 'title' => 'My-Page-1', 'path' => '/my-page-1'],
            ['uuid' => '123-123-789', 'title' => 'My-Page-2', 'path' => '/my-page-2'],
        ];

        $provider = new ContentDataProvider(
            $this->getContentQueryBuilder(
                [
                    'config' => ['dataSource' => '123-123-123', 'excluded' => '123-123-123'],
                    'properties' => ['my-properties' => true],
                    'excluded' => '123-123-123',
                ]
            ),
            $this->getContentQueryExecutor(2, 1, $data),
            $this->getDocumentManager(['123-123-123' => $data[0], '123-123-456' => $data[1]]),
            $this->getProxyFactory()
        );

        $result = $provider->resolveFilters(
            ['dataSource' => '123-123-123', 'excluded' => '123-123-123'],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en'],
            5,
            1,
            2
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals(
            [new ContentDataItem($data[0], $data[0]), new ContentDataItem($data[1], $data[1])],
            $result->getItems()
        );
        $this->assertTrue($result->getHasNextPage());
        $this->assertEquals(['123-123-123', '123-123-456'], $result->getReferencedUuids());
    }

    public function testResolveFiltersNoPagination()
    {
        $data = [
            ['uuid' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'],
            ['uuid' => '123-123-456', 'title' => 'My-Page-1', 'path' => '/my-page-1'],
            ['uuid' => '123-123-789', 'title' => 'My-Page-2', 'path' => '/my-page-2'],
        ];

        $provider = new ContentDataProvider(
            $this->getContentQueryBuilder(
                [
                    'config' => ['dataSource' => '123-123-123', 'excluded' => '123-123-123'],
                    'properties' => ['my-properties' => true],
                    'excluded' => '123-123-123',
                ]
            ),
            $this->getContentQueryExecutor(-1, null, $data),
            $this->getDocumentManager(
                ['123-123-123' => $data[0], '123-123-456' => $data[1], '123-123-789' => $data[2]]
            ),
            $this->getProxyFactory()
        );

        $result = $provider->resolveFilters(
            ['dataSource' => '123-123-123', 'excluded' => '123-123-123'],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en']
        );

        $this->assertInstanceOf(DataProviderResult::class, $result);
        $this->assertEquals(
            [
                new ContentDataItem($data[0], $data[0]),
                new ContentDataItem($data[1], $data[1]),
                new ContentDataItem($data[2], $data[2]),
            ],
            $result->getItems()
        );
        $this->assertFalse($result->getHasNextPage());
        $this->assertEquals(['123-123-123', '123-123-456', '123-123-789'], $result->getReferencedUuids());
    }

    public function testResolveDatasource()
    {
        $data = ['uuid' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'];

        $provider = new ContentDataProvider(
            $this->getContentQueryBuilder(['ids' => [$data['uuid']], 'properties' => ['my-properties' => true]]),
            $this->getContentQueryExecutor(0, 1, [$data]),
            $this->getDocumentManager([$data['uuid'] => $data]),
            $this->getProxyFactory()
        );

        $provider->resolveDatasource(
            $data['uuid'],
            ['properties' => new PropertyParameter('properties', ['my-properties' => true], 'collection')],
            ['webspaceKey' => 'sulu_io', 'locale' => 'en']
        );
    }

    public function testContentDataItem()
    {
        $data = ['uuid' => '123-123-123', 'title' => 'My-Page', 'path' => '/my-page'];
        $resource = new \stdClass();
        $item = new ContentDataItem($data, $resource);

        $this->assertEquals($data['uuid'], $item->getId());
        $this->assertEquals($data['title'], $item->getTitle());
        $this->assertEquals($data['path'], $item->getFullQualifiedTitle());
        $this->assertEquals($resource, $item->getResource());

        $this->assertNull($item->getImage());
    }
}
