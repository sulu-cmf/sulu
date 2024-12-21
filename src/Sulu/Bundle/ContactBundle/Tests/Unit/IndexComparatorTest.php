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

class IndexComparatorTest extends TestCase
{
    /**
     * @return array
     */
    public static function usortProvider()
    {
        return [
            [[5, 4, 3, 1, 2], [1, 2, 3, 4, 5], [1, 2, 3, 4, 5], []],
            [[5, 4, 3, 1, 2, 7], [1, 2, 3, 5, 6], [1, 2, 3, 5], [4, 7]],
            [[5, 4, 3, 1, 2], [1, 2, 5], [1, 2, 5], [3, 4]],
            [[5, 1, 2], [1, 2, 3, 4, 5], [1, 2, 5], []],
            [['a5', 'b1', 'c2'], ['b1', 'c2', 'a5'], ['b1', 'c2', 'a5'], []],
            [['a5', 'c2'], ['b1', 'c2', 'a5'], ['c2', 'a5'], []],
            [['a5', 'b1', 'c2'], ['b1', 'a5'], ['b1', 'a5'], ['c2']],
            [['a5', 'b1', 'c2', 'a1'], ['b1', 'a5', 'd1'], ['b1', 'a5'], ['c2', 'a1']],
            [['a5', 'b1', 'c2', 11], ['b1', 11, 'c2', 'a5'], ['b1', 11, 'c2', 'a5'], []],
            [['a5', 'b1', 'c2', 11, 14], ['b1', 11, 'c2', 'a5'], ['b1', 11, 'c2', 'a5'], [14]],
            [['a5', 'b1', 'c2', 11, 14], ['b1', 11, 'c2', 'a5', 15], ['b1', 11, 'c2', 'a5'], [14]],
            [['a5', 'b1', 'c2', 11], ['b1', 11, 'c2', 'a5', 15], ['b1', 11, 'c2', 'a5'], []],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('usortProvider')]
    public function testUsortCallback(array $array, array $ids, $startsWith, $contains): void
    {
        $comparator = new IndexComparator();
        // the @ is necessary in case of a PHP bug https://bugs.php.net/bug.php?id=50688
        @\usort(
            $array,
            function($a, $b) use ($ids, $comparator) {
                return $comparator->compare($a, $b, $ids);
            }
        );

        if (!empty($contains)) {
            foreach ($contains as $id) {
                $this->assertContains($id, $array);
            }
        }

        $this->assertEquals($startsWith, \array_slice($array, 0, \count($startsWith)));
    }
}
