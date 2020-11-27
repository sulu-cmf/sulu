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

class PropertyMetadata
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $mandatory;

    /**
     * @var array<string, mixed>|null
     */
    private $jsonSchema;

    public function __construct(string $name, bool $mandatory, ?array $jsonSchema = null)
    {
        $this->name = $name;
        $this->mandatory = $mandatory;
        $this->jsonSchema = $jsonSchema;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toJsonSchema(): ?array
    {
        return $this->jsonSchema;
    }
}
