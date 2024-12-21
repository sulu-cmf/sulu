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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

class SchemaMetadataTest extends TestCase
{
    /**
     * It is absolutely necessary that no empty array is returned, because an empty array would be serialized as array
     * instead of an object in JSON, which would cause the JsonSchema library in the frontend to crash.
     */
    public function testEmptyJsonSchemaReturningNonEmptyArray(): void
    {
        $schema = new SchemaMetadata();

        $this->assertEquals([
            'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
        ], $schema->toJsonSchema());
    }

    public function testWithDefinitions(): void
    {
        $schema = new SchemaMetadata();

        $schema->addDefinition('test1', new SchemaMetadata(
            [
                new PropertyMetadata('title2', true),
            ]
        ));

        $schema->addDefinition('test2', new SchemaMetadata(
            [
                new PropertyMetadata('title2', true),
            ]
        ));

        $this->assertEquals([
            'definitions' => [
                'test1' => [
                    'required' => ['title2'],
                    'type' => 'object',
                ],
                'test2' => [
                    'required' => ['title2'],
                    'type' => 'object',
                ],
            ],
            'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
        ], $schema->toJsonSchema());
    }

    public function testNestedJsonSchema(): void
    {
        $schema = new SchemaMetadata(
            [
                new PropertyMetadata('title', true),
            ],
            [],
            [
                new SchemaMetadata(
                    [],
                    [
                        new SchemaMetadata(
                            [
                                new PropertyMetadata('nodeType', false, new ConstMetadata(2)),
                            ]
                        ),
                        new SchemaMetadata(
                            [
                                new PropertyMetadata('nodeType', false, new ConstMetadata(4)),
                            ]
                        ),
                    ]
                ),
                new SchemaMetadata(
                    [
                        new PropertyMetadata('article', true),
                    ]
                ),
            ]
        );

        $this->assertEquals(
            [
                'required' => ['title'],
                'allOf' => [
                    [
                        'anyOf' => [
                            [
                                'properties' => [
                                    'nodeType' => [
                                        'const' => 2,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                            [
                                'properties' => [
                                    'nodeType' => [
                                        'const' => 4,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                    [
                        'required' => ['article'],
                        'type' => 'object',
                    ],
                ],
                'type' => 'object',
            ],
            $schema->toJsonSchema()
        );
    }
}
