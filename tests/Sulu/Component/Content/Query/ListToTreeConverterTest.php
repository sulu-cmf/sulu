<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Query;

class ListToTreeConverterTest extends \PHPUnit_Framework_TestCase
{
    private function createItem($path, $number)
    {
        return array('path' => $path, 'a' => $number);
    }

    public function testConvert()
    {
        $i = 0;
        $data = array(
            $this->createItem('/', $i++),       // 0
            $this->createItem('/a/a/a', $i++), // 1
            $this->createItem('/a/a', $i++), // 2
            $this->createItem('/a', $i++), // 3
            $this->createItem('/b/a', $i++), // 4
            $this->createItem('/b', $i++), // 5
            $this->createItem('/b/b', $i++), // 6
            $this->createItem('/c', $i++), // 7
        );

        $expected = array(
            array(
                'path' => '/',
                'a' => 0,
                'children' => array(
                    array(
                        'path' => '/a',
                        'a' => 3,
                        'children' => array(
                            array(
                                'path' => '/a/a',
                                'a' => 2,
                                'children' => array(
                                    array(
                                        'path' => '/a/a/a',
                                        'a' => 1,
                                        'children' => array()
                                    )
                                )
                            )
                        )
                    ),
                    array(
                        'path' => '/b',
                        'a' => 5,
                        'children' => array(
                            array(
                                'path' => '/b/a',
                                'a' => 4,
                                'children' => array()
                            ),
                            array(
                                'path' => '/b/b',
                                'a' => 6,
                                'children' => array()
                            )
                        )
                    ),
                    array(
                        'path' => '/c',
                        'a' => 7,
                        'children' => array()
                    )
                )
            ),
        );

        $converter = new ListToTreeConverter();
        $result = $converter->convert($data);

        $this->assertEquals($expected, $result);

    }
}
