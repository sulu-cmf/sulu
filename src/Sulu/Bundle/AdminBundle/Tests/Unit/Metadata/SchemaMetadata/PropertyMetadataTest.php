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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

class PropertyMetadataTest extends TestCase
{
    public static function provideGetter()
    {
        return [
            ['title', true],
            ['article', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideGetter')]
    public function testGetter($name, $mandatory): void
    {
        $property = new PropertyMetadata($name, $mandatory);
        $this->assertEquals($name, $property->getName());
        $this->assertEquals($mandatory, $property->isMandatory());
    }

    public static function provideToJsonSchema()
    {
        return [
            ['title', false, null, null],
            ['article', false, new StringMetadata(), ['type' => 'string']],
            ['article', true, new SchemaMetadata(), [
                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
            ]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideToJsonSchema')]
    public function testToJsonSchema($name, $mandatory, $schemaMetadata, $expectedSchema): void
    {
        $property = new PropertyMetadata($name, $mandatory, $schemaMetadata);
        $jsonSchema = $property->toJsonSchema();

        $this->assertEquals($expectedSchema, $jsonSchema);
    }
}
