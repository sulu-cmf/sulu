<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\ListMetadata;

use JMS\Serializer\Annotation as Serializer;
use Sulu\Bundle\AdminBundle\Metadata\AbstractMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\Exception\FieldMetadataNotFoundException;

class ListMetadata extends AbstractMetadata
{
    /**
     * @var FieldMetadata[]
     */
    #[Serializer\Inline]
    protected $fields;

    /**
     * @return FieldMetadata[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param FieldMetadata[] $fields
     */
    public function setFields($fields): void
    {
        $this->fields = $fields;
    }

    public function addField(FieldMetadata $field): void
    {
        $this->fields[$field->getName()] = $field;
    }

    public function getField(string $fieldName): FieldMetadata
    {
        if (!isset($this->fields[$fieldName])) {
            throw new FieldMetadataNotFoundException($fieldName);
        }

        return $this->fields[$fieldName];
    }

    public function removeField(string $fieldName): void
    {
        unset($this->fields[$fieldName]);
    }
}
