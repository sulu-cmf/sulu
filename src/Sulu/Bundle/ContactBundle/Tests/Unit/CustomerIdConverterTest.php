<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Util;

use PHPUnit\Framework\TestCase;

class CustomerIdConverterTest extends TestCase
{
    public static function convertIdsToGroupedIdsProvider()
    {
        return [
            [[], [], []],
            [[], ['a' => [], 'c' => []], ['a' => [], 'c' => []]],
            [['a1', 'c1', 'c3', 'a15'], ['a' => [], 'c' => []], ['a' => [1, 15], 'c' => [1, 3]]],
            [
                ['a1', 'c1', 'c3', 'a15', 'b5'],
                ['a' => [], 'c' => [], 'd' => []],
                ['a' => [1, 15], 'b' => [5], 'c' => [1, 3], 'd' => []],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('convertIdsToGroupedIdsProvider')]
    public function testConvertIdsToGroupedIds(array $ids, array $default, $expected): void
    {
        $converter = new CustomerIdConverter();
        $result = $converter->convertIdsToGroupedIds($ids, $default);

        $this->assertEquals($expected, $result);
    }

    public static function convertGroupedIdsToIdsProvider()
    {
        return [
            [[], []],
            [['a' => [], 'c' => []], []],
            [['a' => [1, 15], 'c' => [1, 3]], ['a1', 'a15', 'c1', 'c3']],
            [
                ['a' => [1, 15], 'b' => [5], 'c' => [1, 3], 'd' => []],
                ['a1', 'a15', 'b5', 'c1', 'c3'],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('convertGroupedIdsToIdsProvider')]
    public function testConvertGroupedIdsToIds(array $groupedIds, array $expected): void
    {
        $converter = new CustomerIdConverter();
        $result = $converter->convertGroupedIdsToIds($groupedIds);

        $this->assertEquals($expected, $result);
    }
}
