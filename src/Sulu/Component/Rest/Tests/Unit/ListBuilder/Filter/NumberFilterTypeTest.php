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
use Sulu\Component\Rest\ListBuilder\Filter\InvalidFilterTypeOptionsException;
use Sulu\Component\Rest\ListBuilder\Filter\NumberFilterType;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class NumberFilterTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var NumberFilterType
     */
    private $numberFilterType;

    /**
     * @var ObjectProphecy<ListBuilderInterface>
     */
    private $listBuilder;

    public function setUp(): void
    {
        $this->numberFilterType = new NumberFilterType();
        $this->listBuilder = $this->prophesize(ListBuilderInterface::class);
    }

    public static function provideFilter()
    {
        return [
            ['price', ['eq' => 6], '=', 6],
            ['priority', ['lt' => 8], '<', 8],
            ['version', ['gt' => 1], '>', 1],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideFilter')]
    public function testFilter($fieldName, $value, $expectedOperator, $expectedValue): void
    {
        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);
        $this->numberFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), $value);

        $this->listBuilder->where($fieldDescriptor->reveal(), $expectedValue, $expectedOperator)->shouldBeCalled();
    }

    public static function provideFilterWithInvalidOptions()
    {
        return [
            [''],
            [['so' => 6]],
            [['eq' => 'test']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideFilterWithInvalidOptions')]
    public function testFilterWithInvalidOptions($options): void
    {
        $this->expectException(InvalidFilterTypeOptionsException::class);

        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);
        $this->numberFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), $options);
    }
}
