<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Doctrine;

use Sulu\Component\Rest\ListBuilder\Doctrine\EncodeAliasTrait;

class EncodeAliasTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EncodeAliasTrait
     */
    private $encodeAlias;

    public function setup()
    {
        $this->encodeAlias = $this->getMockForTrait(EncodeAliasTrait::class);
    }

    public function encodeAliasDataProvider()
    {
        return [
            [
                'TestBundle:Example.id',
                'TestBundle_Example.id',
            ],
            [
                'TestBundle:Example.id = "TestBundle:Example" AND TestBundle:Example.id',
                'TestBundle_Example.id = "TestBundle:Example" AND TestBundle_Example.id',
            ],
            [
                'TestBundle\\Entity\\Example.id = "TestBundle\\Entity\\Example"',
                'TestBundle_Entity_Example.id = "TestBundle\\Entity\\Example"',
            ],
        ];
    }

    /**
     * @dataProvider encodeAliasDataProvider
     */
    public function testEncodeAlias($value, $expected)
    {
        $method = new \ReflectionMethod(get_class($this->encodeAlias), 'encodeAlias');
        $method->setAccessible(true);
        $this->assertEquals($expected, $method->invoke($this->encodeAlias, $value));
    }
}
