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
    /**
     * @var SchemaMetadata
     */
    private $if;

    /**
     * @var SchemaMetadataInterface|null
     */
    private $then;

    /**
     * @var SchemaMetadataInterface|null
     */
    private $else;

    public function __construct(SchemaMetadata $if, ?SchemaMetadataInterface $then = null, ?SchemaMetadataInterface $else = null)
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
