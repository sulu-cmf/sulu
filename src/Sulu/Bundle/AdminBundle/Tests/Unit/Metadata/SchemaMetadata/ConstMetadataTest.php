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
    public function provideGetter()
    {
        return [
            ['title', true],
            ['article', false],
        ];
    }

    /**
     * @dataProvider provideGetter
     */
    public function testGetter($name, $mandatory)
    {
        $property = new ConstMetadata($name, $mandatory, null);
        $this->assertEquals($name, $property->getName());
        $this->assertEquals($mandatory, $property->isMandatory());
    }

    public function provideToJsonSchema()
    {
        return [
            ['title', 'Homepage', ['name' => 'title', 'const' => 'Homepage']],
            ['article', 'Hello World', ['name' => 'article', 'const' => 'Hello World']],
            ['article', null, null],
        ];
    }

    /**
     * @dataProvider provideToJsonSchema
     */
    public function testToJsonSchema($name, $const, $expectedSchema)
    {
        $property = new ConstMetadata($name, false, $const);
        $jsonSchema = $property->toJsonSchema();

        $this->assertEquals($jsonSchema, $expectedSchema);
    }
}
