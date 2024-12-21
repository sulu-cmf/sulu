<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Block;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Content\Compat\Block\BlockProperty;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Webspace\Webspace;

class BlockPropertyTest extends TestCase
{
    use ProphecyTrait;

    public function testSetValue(): void
    {
        $data = [['type' => 'test', 'title' => 'my title', 'description' => 'my description']];

        $blockProperty = new BlockProperty('block', [], 'test');
        $blockPropertyType = $this->prophesize(BlockPropertyType::class);
        $blockPropertyType->getName()->willReturn('test');
        $blockProperty->addType($blockPropertyType->reveal());
        $blockPropertyValue = $this->prophesize(PropertyValue::class);
        $blockPropertyValue->setValue($data)->shouldBeCalled();
        $blockPropertyValue->getValue()->willReturn(null);
        $blockProperty->setPropertyValue($blockPropertyValue->reveal());
        $childProperty1 = $this->prophesize(PropertyInterface::class);
        $childProperty1->getName()->willReturn('title');
        $childProperty1->setValue($data[0]['title'])->shouldBeCalled();
        $childProperty2 = $this->prophesize(PropertyInterface::class);
        $childProperty2->getName()->willReturn('description');
        $childProperty2->setValue($data[0]['description'])->shouldBeCalled();
        $blockPropertyType->getChildProperties()->willReturn([$childProperty1->reveal(), $childProperty2->reveal()]);

        $blockProperty->setValue($data);
    }

    public static function provideIsMultiple()
    {
        return [
            [null, null, true],
            [null, 5, true],
            [null, 1, true],
            [3, null, true],
            [3, 5, true],
            [3, 3, true],
            [1, 1, false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideIsMultiple')]
    public function testGetIsMultiple($minOccurs, $maxOccurs, $result): void
    {
        $blockProperty = new BlockProperty('block', [], 'test', false, false, $maxOccurs, $minOccurs);

        $this->assertEquals($result, $blockProperty->getIsMultiple());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSetInvalidValue')]
    public function testSetInvalidValue($value, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $blockProperty = new BlockProperty('block', [], 'test');
        $blockProperty->doSetValue($value);
    }

    public static function provideSetInvalidValue(): array
    {
        return [
            'invalid int' => [10, 'Expected block configuration but got "int" at property: "block"'],
            'invalid object' => [new Webspace(), 'Expected block configuration but got "Sulu\Component\Webspace\Webspace" at property: "block"'],
        ];
    }
}
