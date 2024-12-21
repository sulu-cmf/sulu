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
use Sulu\Component\Util\SortUtils;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SortUtilsTest extends TestCase
{
    public static function provideSortObjects()
    {
        return [
            // ascending
            [
                [
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'aaa'),
                    new FoobarTestClass('aaa', 'aaa'),
                ],
                'getField1',
                'asc',
                'field1', ['aaa', 'bbb', 'ccc'],
            ],

            // descending
            [
                [
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'aaa'),
                    new FoobarTestClass('aaa', 'aaa'),
                ],
                'getField1',
                'desc',
                'field1', ['ccc', 'bbb', 'aaa'],
            ],

            // sort on a different method
            [
                [
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'bbb'),
                    new FoobarTestClass('aaa', 'ccc'),
                ],
                'getField2',
                'desc',
                'field1', ['aaa', 'bbb', 'ccc'],
            ],

            // sort by two methods
            [
                [
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'aaa'),
                    new FoobarTestClass('aaa', 'aaa'),
                ],
                ['getField2', 'getField1'],
                'asc',
                'field1', ['aaa', 'bbb', 'ccc'],
            ],

            // check sorting numerical strings
            [
                [
                    new FoobarTestClass('15', 'aaa'),
                    new FoobarTestClass('100', 'bbb'),
                    new FoobarTestClass('200', 'ccc'),
                ],
                'getField1',
                'asc',
                'field1', ['15', '100', '200'],
            ],

            // check array
            [
                [
                    ['foo' => 'zzz', 'bar' => 'aaa'],
                    ['foo' => 'xxx', 'bar' => 'aaa'],
                    ['foo' => 'yyy', 'bar' => 'aaa'],
                ],
                '[foo]',
                'asc',
                '[foo]', ['xxx', 'yyy', 'zzz'],
            ],

            // multi dimensional array
            [
                [
                    ['foo' => '1', 'baz' => ['bar' => 'bbb']],
                    ['foo' => '2', 'baz' => ['bar' => 'aaa']],
                    ['foo' => '3', 'baz' => ['bar' => 'ccc']],
                ],
                '[baz][bar]',
                'asc',
                '[foo]', ['2', '1', '3'],
            ],

            // multi dimensional array missing key asc
            [
                [
                    ['foo' => '1', 'baz' => ['bar' => 'bbb']],
                    ['foo' => '2', 'baz' => ['sad' => 'aaa']],
                ],
                '[baz][bar]',
                'asc',
                '[foo]', ['2', '1'],
            ],

            // multi dimensional array missing key desc
            [
                [
                    ['foo' => '1', 'baz' => ['bar' => 'bbb']],
                    ['foo' => '2', 'baz' => ['sad' => 'aaa']],
                ],
                '[baz][bar]',
                'desc',
                '[foo]', ['1', '2'],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSortObjects')]
    public function testSortObjects($data, $methodName, $direction, $checkField, $expectedOrder): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $res = SortUtils::multisort($data, $methodName, $direction);

        foreach ($expectedOrder as $expected) {
            $object = \array_shift($res);
            $this->assertEquals($expected, $accessor->getValue($object, $checkField));
        }
    }

    public function testSortArrayObject(): void
    {
        $collection = new \ArrayObject([
            new FoobarTestClass('value2', 'value2'),
            new FoobarTestClass('value1', 'value2'),
        ]);

        $res = SortUtils::multisort($collection, 'getField1');

        $this->assertEquals('value1', $res[0]->field1);
        $this->assertEquals('value2', $res[0]->field2);
    }

    public function testSortMissingField(): void
    {
        $this->expectException(NoSuchPropertyException::class);
        $collection = new \ArrayObject([
            (object) ['value2', 'value2'],
            (object) ['value1', 'value2'],
        ]);

        SortUtils::multisort($collection, 'somefink');
    }

    public function testSortLocaleAwareSimpleArray(): void
    {
        $array = ['D', 'A', 'Ê', 'E', 'Ä', 'M'];
        $result = SortUtils::sortLocaleAware($array, 'de');

        if (!\class_exists(\Collator::class)) {
            $this->assertSame(['A', 'D', 'E', 'M', 'Ä', 'Ê'], $result);
        } else {
            $this->assertSame(['A', 'Ä', 'D', 'E', 'Ê', 'M'], $result);
        }
    }

    public function testSortLocaleAwareDeepArray(): void
    {
        $array = [
            ['value' => 'D'],
            ['value' => 'A'],
            ['value' => 'Ê'],
            ['value' => 'E'],
            ['value' => 'Ä'],
            ['value' => 'M'],
        ];

        $result = SortUtils::sortLocaleAware($array, 'de', fn ($item) => $item['value']);

        if (!\class_exists(\Collator::class)) {
            $this->assertSame([
                ['value' => 'A'],
                ['value' => 'D'],
                ['value' => 'E'],
                ['value' => 'M'],
                ['value' => 'Ä'],
                ['value' => 'Ê'],
            ], $result);
        } else {
            $this->assertSame([
                ['value' => 'A'],
                ['value' => 'Ä'],
                ['value' => 'D'],
                ['value' => 'E'],
                ['value' => 'Ê'],
                ['value' => 'M'],
            ], $result);
        }
    }
}

class FoobarTestClass
{
    public function __construct(public string $field1, public string $field2)
    {
    }

    public function getField1(): string
    {
        return $this->field1;
    }

    public function getField2(): string
    {
        return $this->field2;
    }
}
