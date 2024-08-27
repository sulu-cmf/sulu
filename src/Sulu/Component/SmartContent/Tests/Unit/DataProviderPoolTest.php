<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\SmartContent\Configuration\ProviderConfiguration;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderPool;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\DatasourceItem;
use Sulu\Component\SmartContent\DatasourceItemInterface;
use Sulu\Component\SmartContent\Exception\DataProviderAliasAlreadyExistsException;
use Sulu\Component\SmartContent\Exception\DataProviderNotExistsException;

class DataProviderPoolTest extends TestCase
{
    use ProphecyTrait;

    public static function createDataProvider(): DataProviderInterface
    {
        return new class() implements DataProviderInterface {
            public function getConfiguration(): ProviderConfigurationInterface
            {
                return new ProviderConfiguration();
            }

            public function getDefaultPropertyParameter(): array
            {
                return [];
            }

            public function resolveDataItems(
                array $filters,
                array $propertyParameter,
                array $options = [],
                $limit = null,
                $page = 1,
                $pageSize = null
            ): DataProviderResult {
                return new DataProviderResult([], false);
            }

            public function resolveResourceItems(
                array $filters,
                array $propertyParameter,
                array $options = [],
                $limit = null,
                $page = 1,
                $pageSize = null
            ): DataProviderResult {
                return new DataProviderResult([], false);
            }

            public function resolveDatasource(
                $datasource,
                array $propertyParameter,
                array $options,
            ): DatasourceItemInterface {
                return new DatasourceItem('', '', '', '');
            }
        };
    }

    /**
     * @return iterable<array{
     *     0: DataProviderPool,
     *     1: array<array{alias: string, provider: DataProviderInterface}>,
     *     2: array<string, DataProviderInterface>,
     *     3?: class-string<\Throwable>,
     * }>
     */
    public static function addProvider(): iterable
    {
        $pool1 = new DataProviderPool(true);
        $pool2 = new DataProviderPool(true);

        $provider1 = self::createDataProvider();
        $provider2 = self::createDataProvider();
        $provider3 = self::createDataProvider();

        $pool2->add('test-1', $provider1);

        return [
            [
                $pool1,
                [
                    ['alias' => 'test', 'provider' => $provider1],
                ],
                [
                    'test' => $provider1,
                ],
            ],
            [
                $pool1,
                [
                    ['alias' => 'test', 'provider' => $provider1],
                    ['alias' => 'test', 'provider' => $provider2],
                ],
                [
                    'test' => $provider1,
                ],
                DataProviderAliasAlreadyExistsException::class,
            ],
            [
                $pool2,
                [
                    ['alias' => 'test-2', 'provider' => $provider2],
                    ['alias' => 'test-3', 'provider' => $provider3],
                ],
                [
                    'test-1' => $provider1,
                    'test-2' => $provider2,
                    'test-3' => $provider3,
                ],
            ],
        ];
    }

    /**
     * @dataProvider addProvider
     *
     * @param array<array{alias: string, provider: DataProviderInterface}> $providers
     * @param array<string, DataProviderInterface> $expectedProviders
     * @param class-string<\Throwable>|null $exceptionName
     */
    public function testAdd(DataProviderPool $pool, array $providers, array $expectedProviders, ?string $exceptionName = null): void
    {
        if ($exceptionName) {
            $this->expectException($exceptionName);
        }

        foreach ($providers as $item) {
            $pool->add($item['alias'], $item['provider']);
        }

        $this->assertEquals($expectedProviders, $pool->getAll());
    }

    public static function existsProvider()
    {
        $pool = new DataProviderPool(true);
        $pool->add('test', self::createDataProvider());

        return [
            [$pool, 'test', true],
            [$pool, 'test-1', false],
        ];
    }

    /**
     * @dataProvider existsProvider
     */
    public function testExists(DataProviderPool $pool, string $alias, bool $expected): void
    {
        $this->assertEquals($expected, $pool->exists($alias));
    }

    public static function getProvider()
    {
        $pool = new DataProviderPool(true);
        $provider = self::createDataProvider();
        $pool->add('test', $provider);

        return [
            [
                $pool,
                'test',
                $provider,
            ],
            [
                $pool,
                'test-1',
                null,
                DataProviderNotExistsException::class,
            ],
        ];
    }

    /**
     * @dataProvider getProvider
     *
     * @param class-string<\Throwable>|null $exceptionName
     */
    public function testGet(DataProviderPool $pool, string $alias, ?DataProviderInterface $expectedProvider, ?string $exceptionName = null): void
    {
        if ($exceptionName) {
            $this->expectException($exceptionName);
        }

        $this->assertEquals($expectedProvider, $pool->get($alias));
    }

    /**
     * @return iterable<array{
     *     0: DataProviderPool,
     *     1: array<string, DataProviderInterface>,
     * }>
     */
    public static function getAllProvider()
    {
        $pool1 = new DataProviderPool(true);
        $pool2 = new DataProviderPool(true);
        $provider1 = self::createDataProvider();
        $provider2 = self::createDataProvider();
        $pool1->add('test-1', $provider1);
        $pool1->add('test-2', $provider2);

        return [
            [
                $pool1,
                [
                    'test-1' => $provider1,
                    'test-2' => $provider2,
                ],
            ],
            [
                $pool2,
                [],
            ],
        ];
    }

    /**
     * @dataProvider getAllProvider
     *
     * @param array<string, DataProviderInterface> $expectedProviders
     */
    public function testGetAll(DataProviderPool $pool, array $expectedProviders): void
    {
        $this->assertEquals($expectedProviders, $pool->getAll());
    }
}
