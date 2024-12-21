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
use Sulu\Component\Rest\ListBuilder\Filter\SelectFilterType;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class SelectFilterTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SelectFilterType
     */
    private $selectFilterType;

    /**
     * @var ObjectProphecy<ListBuilderInterface>
     */
    private $listBuilder;

    public function setUp(): void
    {
        $this->selectFilterType = new SelectFilterType();
        $this->listBuilder = $this->prophesize(ListBuilderInterface::class);
    }

    public static function provideFilter()
    {
        return [
            ['type', 'overview,homepage', ['overview', 'homepage']],
            ['status', 'active,inactive', ['active', 'inactive']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideFilter')]
    public function testFilter($fieldName, $value, $expected): void
    {
        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);
        $this->selectFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), $value);

        $this->listBuilder->in($fieldDescriptor->reveal(), $expected)->shouldBeCalled();
    }

    public function testFilterWithInvalidOptions(): void
    {
        $this->expectException(InvalidFilterTypeOptionsException::class);

        $fieldDescriptor = $this->prophesize(FieldDescriptor::class);
        $this->selectFilterType->filter($this->listBuilder->reveal(), $fieldDescriptor->reveal(), []);
    }
}
