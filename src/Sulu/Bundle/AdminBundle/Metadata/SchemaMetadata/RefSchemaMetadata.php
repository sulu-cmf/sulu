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

class RefSchemaMetadata implements SchemaMetadataInterface
{
    public function __construct(
        private string $ref,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toJsonSchema(): array
    {
        return [
            '$ref' => $this->ref,
        ];
    }
}
