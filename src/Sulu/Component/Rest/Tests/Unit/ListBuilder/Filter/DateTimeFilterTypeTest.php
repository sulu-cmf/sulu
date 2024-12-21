<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Filter;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Filter\DateTimeFilterType;
use Sulu\Component\Rest\ListBuilder\Filter\InvalidFilterTypeOptionsException;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class DateTimeFilterTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var DateTimeFilterType
     */
    private $dateTimeFilterType;

    /**
     * @var ObjectProphecy<ListBuilderInterface>
     */
    private $listBuilder;

    public function setUp(): void
    {
        $this->dateTimeFilterType = new DateTimeFilterType();
        $this->listBuilder = $this->prophesize(ListBuilderInterface::class);
    }

    public static function provideFilter()
    {
        return [
            ['created', ['from' => '2020-02-05 12:15', 'to' => '2020-02-07 13:15'], ['2020-02-05 12:15:00', '2020-02-07 13:15:59']],
            ['changed', ['from' => '2013-08-01 00:00', 'to' => '2020-02-10 00:00'], ['2013-08-01 00:00:00', '2020-02-10 00:00:59']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideFilter')]
    public function testFilter($fieldName, $value, $expected): void
    {
        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);

        $this->dateTimeFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), $value);

        $this->listBuilder
            ->where($fieldDescriptor->reveal(), $expected[0], ListBuilderInterface::WHERE_COMPARATOR_GREATER_THAN)
            ->shouldBeCalled();

        $this->listBuilder
            ->where($fieldDescriptor->reveal(), $expected[1], ListBuilderInterface::WHERE_COMPARATOR_LESS)
            ->shouldBeCalled();
    }

    public static function provideFilterFromOnly()
    {
        return [
            ['created', ['from' => '2020-02-05 12:15'], '2020-02-05 12:15:00'],
            ['changed', ['from' => '2013-08-01 00:00'], '2013-08-01 00:00:00'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideFilterFromOnly')]
    public function testFilterFromOnly($fieldName, $value, $expected): void
    {
        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);

        $this->dateTimeFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), $value);

        $this->listBuilder
            ->where($fieldDescriptor->reveal(), $expected, ListBuilderInterface::WHERE_COMPARATOR_GREATER)
            ->shouldBeCalled();
    }

    public static function provideFilterToOnly()
    {
        return [
            ['created', ['to' => '2020-02-05 12:15'], '2020-02-05 12:15:59'],
            ['changed', ['to' => '2013-08-01 00:00'], '2013-08-01 00:00:59'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideFilterToOnly')]
    public function testFilterToOnly($fieldName, $value, $expected): void
    {
        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);

        $this->dateTimeFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), $value);

        $this->listBuilder
            ->where($fieldDescriptor->reveal(), $expected, ListBuilderInterface::WHERE_COMPARATOR_LESS)
            ->shouldBeCalled();
    }

    public static function provideFilterWithInvalidOptions()
    {
        return [
            [[]],
            ['Test'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideFilterWithInvalidOptions')]
    public function testFilterWithInvalidOptions($value): void
    {
        $this->expectException(InvalidFilterTypeOptionsException::class);

        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);
        $this->dateTimeFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), $value);
    }
}
