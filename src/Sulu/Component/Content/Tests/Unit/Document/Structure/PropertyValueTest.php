<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Structure;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Document\Structure\PropertyValue;

class PropertyValueTest extends TestCase
{
    public static function provideOffsetSetData()
    {
        return [
            [[], 'foo', 'bar', ['foo' => 'bar']],
            [['foo' => 'bar'], 'foo', 'baz', ['foo' => 'baz']],
            [['foo' => ['bar']], 'foo', 'baz', ['foo' => 'baz']],
            [['foo' => ['bar']], 'foo', ['baz'], ['foo' => ['baz']]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideOffsetSetData')]
    public function testOffsetSet($value, $setName, $setValue, $expected): void
    {
        $propertyValue = new PropertyValue('test', $value);

        $propertyValue[$setName] = $setValue;

        $this->assertEquals($propertyValue->getValue(), $expected);
    }
}
