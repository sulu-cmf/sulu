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
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\Configuration\Builder;

class BuilderTest extends TestCase
{
    public static function provideBoolean()
    {
        return [[true], [false]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBoolean')]
    public function testTags($enable): void
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableTags($enable));

        $configuration = $builder->getConfiguration();

        $this->assertEquals($enable, $configuration->hasTags());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBoolean')]
    public function testCategories($enable): void
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableCategories($enable));

        $configuration = $builder->getConfiguration();

        $this->assertEquals($enable, $configuration->hasCategories());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBoolean')]
    public function testLimit($enable): void
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableLimit($enable));

        $configuration = $builder->getConfiguration();

        $this->assertEquals($enable, $configuration->hasLimit());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBoolean')]
    public function testPresentAs($enable): void
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enablePresentAs($enable));

        $configuration = $builder->getConfiguration();

        $this->assertEquals($enable, $configuration->hasPresentAs());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPagination());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBoolean')]
    public function testPagination($enable): void
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enablePagination($enable));

        $configuration = $builder->getConfiguration();

        $this->assertEquals($enable, $configuration->hasPagination());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasPresentAs());
    }

    public function testSorting(): void
    {
        $expectedSorting = [
            new PropertyParameter('entity.id', 'Identification'),
            new PropertyParameter('entity.name', 'Name'),
            new PropertyParameter('entity.test', 'Test'),
        ];
        $sorting = [
            ['column' => 'entity.id', 'title' => 'Identification'],
            ['column' => 'entity.name', 'title' => 'Name'],
            ['column' => 'entity.test', 'title' => 'Test'],
        ];

        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableSorting($sorting));

        $configuration = $builder->getConfiguration();

        $this->assertTrue($configuration->hasSorting());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasDatasource());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasPagination());

        $this->assertEquals($expectedSorting, $configuration->getSorting());
    }

    public function testDatasource(): void
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableDatasource('collections', 'collections', 'column_list'));

        $configuration = $builder->getConfiguration();

        $this->assertTrue($configuration->hasDatasource());
        $this->assertFalse($configuration->hasSorting());
        $this->assertFalse($configuration->hasTags());
        $this->assertFalse($configuration->hasCategories());
        $this->assertFalse($configuration->hasLimit());
        $this->assertFalse($configuration->hasPresentAs());
        $this->assertFalse($configuration->hasPagination());

        $this->assertEquals('collections', $configuration->getDatasourceResourceKey());
        $this->assertEquals('collections', $configuration->getDatasourceListKey());
        $this->assertEquals('column_list', $configuration->getDatasourceAdapter());
    }

    public function testView(): void
    {
        $builder = Builder::create();

        $this->assertEquals($builder, $builder->enableView('edit_form', ['id' => 'id']));

        $configuration = $builder->getConfiguration();

        $this->assertEquals('edit_form', $configuration->getView());
        $this->assertEquals(['id' => 'id'], $configuration->getResultToView());
    }
}
