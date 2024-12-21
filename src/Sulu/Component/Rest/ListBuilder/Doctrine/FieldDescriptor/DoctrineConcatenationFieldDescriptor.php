<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor;

use JMS\Serializer\Annotation\ExclusionPolicy;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

/**
 * This field descriptor can be used to concatenate multiple field descriptors.
 */
#[ExclusionPolicy('all')]
class DoctrineConcatenationFieldDescriptor extends AbstractDoctrineFieldDescriptor
{
    /**
     * @param DoctrineFieldDescriptorInterface[] $fieldDescriptors the field descriptors which will be concatenated
     */
    public function __construct(
        private array $fieldDescriptors,
        string $name,
        ?string $translation = null,
        private string $glue = ' ',
        string $visibility = FieldDescriptorInterface::VISIBILITY_YES,
        string $searchability = FieldDescriptorInterface::SEARCHABILITY_NEVER,
        string $type = '',
        bool $sortable = true,
        string $width = FieldDescriptorInterface::WIDTH_AUTO
    ) {
        parent::__construct(
            $name,
            $translation,
            $visibility,
            $searchability,
            $type,
            $sortable,
            $width
        );
    }

    /**
     * Returns the select statement for this field without the alias.
     *
     * @return string
     */
    public function getSelect()
    {
        $concat = null;

        foreach ($this->fieldDescriptors as $fieldDescriptor) {
            if (null == $concat) {
                $concat = $fieldDescriptor->getSelect();
            } else {
                $concat = 'CONCAT(' . $concat . ', CONCAT(\'' . $this->glue . '\', ' . $fieldDescriptor->getSelect() . '))';
            }
        }

        return $concat;
    }

    /**
     * Returns all the joins required for this field.
     *
     * @return DoctrineJoinDescriptor[]
     */
    public function getJoins()
    {
        $joins = [];

        foreach ($this->fieldDescriptors as $fieldDescriptor) {
            $joins = \array_merge($joins, $fieldDescriptor->getJoins());
        }

        return $joins;
    }
}
