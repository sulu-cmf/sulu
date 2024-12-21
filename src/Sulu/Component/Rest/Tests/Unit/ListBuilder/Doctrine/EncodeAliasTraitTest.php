<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Doctrine;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Rest\ListBuilder\Doctrine\EncodeAliasTrait;

class EncodeAliasTraitTest extends TestCase
{
    use EncodeAliasTrait;

    public static function encodeAliasDataProvider()
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

    #[\PHPUnit\Framework\Attributes\DataProvider('encodeAliasDataProvider')]
    public function testEncodeAlias(string $value, string $expected): void
    {
        $this->assertEquals($expected, $this->encodeAlias($value));
    }
}
