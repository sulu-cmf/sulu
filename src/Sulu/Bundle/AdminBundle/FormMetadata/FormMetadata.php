<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Component\Content\Metadata\PropertiesMetadata;

/**
 * @deprecated use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata instead.
 *
 * Represents metadata for a form structure.
 */
class FormMetadata extends PropertiesMetadata
{
    private ?string $key = null;

    private ?\Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata $schema = null;

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setSchema(SchemaMetadata $schema)
    {
        $this->schema = $schema;
    }

    public function getSchema(): ?SchemaMetadata
    {
        return $this->schema;
    }
}
