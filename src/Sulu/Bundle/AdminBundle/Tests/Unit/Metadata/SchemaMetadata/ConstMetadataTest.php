<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\SchemaMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ConstMetadata;

class ConstMetadataTest extends TestCase
{
    public static function provideToJsonSchema()
    {
        return [
            ['Homepage', ['const' => 'Homepage']],
            ['Hello World', ['const' => 'Hello World']],
            [null, ['const' => null]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideToJsonSchema')]
    public function testToJsonSchema($const, $expectedSchema): void
    {
        $property = new ConstMetadata($const);
        $jsonSchema = $property->toJsonSchema();

        $this->assertEquals($jsonSchema, $expectedSchema);
    }
}
