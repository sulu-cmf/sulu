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
 * This class defines the necessary information for a field to resolve it within a Doctrine Query for the ListBuilder.
 */
#[ExclusionPolicy('all')]
class DoctrineCaseFieldDescriptor extends AbstractDoctrineFieldDescriptor
{
    public function __construct(
        string $name,
        private DoctrineDescriptor $case1,
        private DoctrineDescriptor $case2,
        ?string $translation = null,
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
        return \sprintf(
            '(CASE WHEN %s IS NOT NULL THEN %s ELSE %s END)',
            $this->case1->getSelect(),
            $this->case1->getSelect(),
            $this->case2->getSelect()
        );
    }

    public function getSearch()
    {
        return \sprintf(
            '%s LIKE :search OR (%s is NULL AND %s LIKE :search)',
            $this->case1->getSelect(),
            $this->case1->getSelect(),
            $this->case2->getSelect()
        );
    }

    public function getJoins()
    {
        return \array_merge($this->case1->getJoins(), $this->case2->getJoins());
    }
}
