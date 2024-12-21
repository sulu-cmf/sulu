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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ArrayMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

class ArrayMetadataTest extends TestCase
{
    public static function provideToJsonSchema()
    {
        return [
            [
                new SchemaMetadata(),
                null,
                null,
                null,
                [
                    'type' => 'array',
                    'items' => [
                        'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
                    ],
                ],
            ],
            [
                new SchemaMetadata([new PropertyMetadata('test1', true), new PropertyMetadata('test2', false)]),
                1,
                null,
                true,
                ['type' => 'array', 'items' => ['required' => ['test1'], 'type' => 'object'], 'minItems' => 1, 'uniqueItems' => true],
            ],
            [
                new SchemaMetadata([new PropertyMetadata('test1', true), new PropertyMetadata('test2', true)]),
                2,
                3,
                false,
                ['type' => 'array', 'items' => ['required' => ['test1', 'test2'], 'type' => 'object'], 'minItems' => 2, 'maxItems' => 3, 'uniqueItems' => false],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideToJsonSchema')]
    public function testToJsonSchema($schemaMetadata, $minItems, $maxItems, $uniqueItems, $expectedSchema): void
    {
        $property = new ArrayMetadata($schemaMetadata, $minItems, $maxItems, $uniqueItems);
        $jsonSchema = $property->toJsonSchema();

        $this->assertEquals($expectedSchema, $jsonSchema);
    }
}
