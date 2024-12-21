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
use Sulu\Component\Rest\ListBuilder\Filter\TextFilterType;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class TextFilterTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var TextFilterType
     */
    private $textFilterType;

    /**
     * @var ObjectProphecy<ListBuilderInterface>
     */
    private $listBuilder;

    public function setUp(): void
    {
        $this->textFilterType = new TextFilterType();
        $this->listBuilder = $this->prophesize(ListBuilderInterface::class);
    }

    public static function provideFilter()
    {
        return [
            ['firstName', ['eq' => 'Max'], '=', 'Max'],
            ['lastName', ['eq' => 'Mustermann'], '=', 'Mustermann'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideFilter')]
    public function testFilter($fieldName, $value, $expectedOperator, $expectedValue): void
    {
        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);

        $this->textFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), $value);

        $this->listBuilder->where($fieldDescriptor->reveal(), $expectedValue, $expectedOperator)->shouldBeCalled();
    }

    public function testFilterWithInvalidOptions(): void
    {
        $this->expectException(InvalidFilterTypeOptionsException::class);

        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);
        $this->textFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), false);
    }

    public function testFilterWithInvalidOptionsArray(): void
    {
        $this->expectException(InvalidFilterTypeOptionsException::class);

        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);
        $this->textFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), ['nonsense' => 8]);
    }
}
