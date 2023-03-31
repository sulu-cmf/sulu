<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

class IfThenElseMetadata implements SchemaMetadataInterface
{
    private \Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata $if;

    private ?\Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata $then = null;

    private ?\Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata $else = null;

    public function __construct(SchemaMetadata $if, ?SchemaMetadata $then = null, ?SchemaMetadata $else = null)
    {
        $this->if = $if;
        $this->then = $then;
        $this->else = $else;
    }

    public function toJsonSchema(): array
    {
        $jsonSchema = [
            'if' => $this->if->toJsonSchema(),
        ];

        if ($this->then) {
            $jsonSchema['then'] = $this->then->toJsonSchema();
        }

        if ($this->else) {
            $jsonSchema['else'] = $this->else->toJsonSchema();
        }

        return $jsonSchema;
    }
}
