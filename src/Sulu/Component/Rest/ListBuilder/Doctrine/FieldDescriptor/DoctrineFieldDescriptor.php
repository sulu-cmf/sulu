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
use Sulu\Component\Rest\ListBuilder\Doctrine\EncodeAliasTrait;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

/**
 * This class defines the necessary information for a field to resolve it within a Doctrine Query for the ListBuilder.
 */
#[ExclusionPolicy('all')]
class DoctrineFieldDescriptor extends AbstractDoctrineFieldDescriptor
{
    use EncodeAliasTrait;

    /**
     * @param DoctrineJoinDescriptor[] $joins the joins, which have to be made to get to the result
     */
    public function __construct(
        private string $fieldName,
        string $name,
        private string $entityName,
        ?string $translation = null,
        private array $joins = [],
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

    public function getSelect()
    {
        return $this->encodeAlias($this->entityName) . '.' . $this->getFieldName();
    }

    /**
     * Returns the alias for the field in the database.
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Returns the name of the entity this field is contained in.
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Returns all the joins which are necessary to access this field.
     *
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    public function compare(FieldDescriptorInterface $other)
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->getEntityName() === $other->getEntityName()
            && $this->getFieldName() === $other->getFieldName();
    }
}
