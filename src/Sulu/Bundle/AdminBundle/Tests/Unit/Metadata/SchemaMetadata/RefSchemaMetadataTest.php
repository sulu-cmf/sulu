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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\RefSchemaMetadata;

class RefSchemaMetadataTest extends TestCase
{
    public function testToJsonSchema(): void
    {
        $schema = new RefSchemaMetadata('#/definitions/test1');

        $this->assertEquals([
            '$ref' => '#/definitions/test1',
        ], $schema->toJsonSchema());
    }
}
