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

class SchemaMetadata implements SchemaMetadataInterface
{
    /**
     * @var PropertiesMetadata
     */
    private $propertiesMetadata;

    /**
     * @var AnyOfsMetadata
     */
    private $anyOfsMetadata;

    /**
     * @var AllOfsMetadata
     */
    private $allOfsMetadata;

    /**
     * @var array<string, SchemaMetadataInterface>
     */
    private $definitions = [];

    /**
     * @param PropertyMetadata[] $properties
     * @param SchemaMetadataInterface[] $anyOfs
     * @param SchemaMetadataInterface[] $allOfs
     */
    public function __construct(array $properties = [], array $anyOfs = [], array $allOfs = [])
    {
        $this->propertiesMetadata = new PropertiesMetadata($properties);
        $this->anyOfsMetadata = new AnyOfsMetadata($anyOfs);
        $this->allOfsMetadata = new AllOfsMetadata($allOfs);
    }

    public function merge(self $schema): self
    {
        return new self([], [], [$this, $schema]);
    }

    public function addDefinition(string $name, SchemaMetadataInterface $definition): self
    {
        $this->definitions[$name] = $definition;

        return $this;
    }

    public function toJsonSchema(): array
    {
        $definitionSchema = [];
        foreach ($this->definitions as $name => $definition) {
            $definitionSchema[$name] = $definition->toJsonSchema();
        }

        $propertiesSchema = $this->propertiesMetadata->toJsonSchema();

        /*
         * This is necessary to remove a warning from ajv (because of strict mode,
         * `properties` or `required` keyword must not appear without {"type": "object"})
         */
        if (!empty($propertiesSchema)) {
            $propertiesSchema = \array_merge(
                ['type' => 'object'],
                $propertiesSchema
            );
        }

        $jsonSchema = \array_merge(
            [],
            $propertiesSchema,
            $this->anyOfsMetadata->toJsonSchema(),
            $this->allOfsMetadata->toJsonSchema()
        );

        /*
         * If the schema is empty, there should be at least one property, because otherwise the admin ui treats an
         * empty schema object as array instead of an object and would break
         */
        if (empty($jsonSchema)) {
            $jsonSchema = [
                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
            ];
        }

        if (0 < \count($definitionSchema)) {
            $jsonSchema['definitions'] = $definitionSchema;
        }

        return $jsonSchema;
    }
}
