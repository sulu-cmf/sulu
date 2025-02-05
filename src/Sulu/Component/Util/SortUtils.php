<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Sorting utilities.
 */
final class SortUtils
{
    /**
     * Cannot instantiate this class.
     */
    private function __construct()
    {
    }

    /**
     * Sort the given array of arrays/objects using paths.
     *
     * e.g.
     *
     *      $data = array(
     *          array('foobar' => 'b'),
     *          array('foobar' => 'a'),
     *      );
     *
     *      SortUtils::multisort($data, '[foobar]', 'asc');
     *
     *      echo $data[0]; // "a"
     *
     * You can also use method names:
     *
     *      SortUtils::multisort($data, 'getFoobar', 'asc');
     *
     * Or sort on multidimensional arrays:
     *
     *      SortUtils::multisort($data, 'foobar.bar.getFoobar', 'asc');
     *
     * And you can sort on multiple paths:
     *
     *      SortUtils::multisort($data, array('foo', 'bar'), 'asc');
     *
     * The path is any path accepted by the property access component:
     *
     * @see http://symfony.com/doc/current/components/property_access/introduction.html
     *
     * @template T of mixed
     * @template TKey of array-key
     *
     * @param iterable<TKey, T> $values
     * @param string|array $paths Path or paths on which to sort on
     * @param string $direction Direction to sort in (either ASC or DESC)
     *
     * @return array<TKey, T>
     */
    public static function multisort($values, $paths, $direction = 'ASC')
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $values = (array) $values;
        $paths = (array) $paths;

        \usort($values, function($a, $b) use ($accessor, $paths) {
            foreach ($paths as $i => $path) {
                $aOrder = $accessor->getValue($a, $path);
                $bOrder = $accessor->getValue($b, $path);

                if (\is_string($aOrder)) {
                    $aOrder = \strtolower($aOrder);
                }

                if (\is_string($bOrder)) {
                    $bOrder = \strtolower($bOrder);
                }

                if ($aOrder == $bOrder) {
                    if (\count($paths) == ($i + 1)) {
                        return 0;
                    } else {
                        continue;
                    }
                }

                return ($aOrder < $bOrder) ? -1 : 1;
            }
        });

        if ('DESC' == \strtoupper($direction)) {
            $values = \array_reverse($values);
        }

        return $values;
    }

    /**
     * Sorts the items of the iterable by a locale aware function.
     *
     * The values for comparison are casted to string, before they are compared. The sorted items are returned by a new array.
     *
     * If the intl extension is not loaded, the comparison falls back to binary comparison.
     *
     * @template T of mixed
     * @template TKey of array-key
     *
     * @param iterable<TKey, T> $list
     * @param null|callable $getComparableValue callback to get the value from each item that will be compared
     *
     * @return array<TKey, T>
     *
     * @throws \InvalidArgumentException if the comparison of the values failed
     */
    public static function sortLocaleAware(iterable $list, string $locale, ?callable $getComparableValue = null): array
    {
        $array = \is_array($list) ? $list : \iterator_to_array($list);
        $isList = \array_is_list($array);

        if (null === $getComparableValue) {
            $getComparableValue = fn ($item) => $item;
        }

        // Collator class requires intl extension
        $collator = \class_exists(\Collator::class) ? new \Collator($locale) : null;

        $sortMethod = $isList ? '\usort' : '\uasort';

        $sortMethod($array, function(mixed $itemA, mixed $itemB) use ($collator, $getComparableValue) {
            $valueA = (string) $getComparableValue($itemA);
            $valueB = (string) $getComparableValue($itemB);

            if ($collator) {
                $result = $collator->compare($valueA, $valueB);
            } else {
                $result = \strcmp($valueA, $valueB);
            }

            if (false === $result) {
                throw new \InvalidArgumentException('Comparison of the strings "%s" and "%s" failed.');
            }

            return $result;
        });

        return $array;
    }
}
