<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Util\ArrayUtils;

class ArrayUtilsTest extends TestCase
{
    public static function provideData()
    {
        return [
            [
                ['my-test', 'other-test'],
                'item in ["my-test", "no-test"]',
                ['my-test'],
            ],
            [
                ['my-test', 'other-test'],
                'item in ["other-test", "no-test"]',
                [1 => 'other-test'],
            ],
            [
                ['a' => 'my-test', 'b' => 'other-test'],
                'item == "my-test" or key == "b"',
                ['a' => 'my-test', 'b' => 'other-test'],
            ],
            [
                ['a' => 'my-test', 'b' => 'other-test'],
                'item == itemValue or key == keyValue',
                ['a' => 'my-test', 'b' => 'other-test'],
                ['itemValue' => 'my-test', 'keyValue' => 'b'],
            ],
            [
                ['a' => 'my-test', 'b' => 'other-test'],
                'item == itemValue or key == keyValue',
                [],
                ['keyValue' => 'my-test', 'itemValue' => 'b'],
            ],
            [
                ['a' => 'my-test', 'b' => 'other-test'],
                'item == itemValue and key == keyValue',
                [],
                ['itemValue' => 'my-test', 'keyValue' => 'b'],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideData')]
    public function testFilter($collection, $expression, $expected, $context = []): void
    {
        $this->assertEquals($expected, ArrayUtils::filter($collection, $expression, $context));
    }
}
