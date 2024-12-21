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
 * This field descriptor can be used to group-concatenate a joined (1:n) field descriptor.
 */
#[ExclusionPolicy('all')]
class DoctrineGroupConcatFieldDescriptor extends AbstractDoctrineFieldDescriptor
{
    public function __construct(
        /**
         * The field descriptor which will be group concatenated.
         */
        private DoctrineFieldDescriptorInterface $fieldDescriptor,
        string $name,
        ?string $translation = null,
        private string $glue = ',',
        string $visibility = FieldDescriptorInterface::VISIBILITY_YES,
        string $searchability = FieldDescriptorInterface::SEARCHABILITY_NEVER,
        string $type = '',
        bool $sortable = true,
        private bool $distinct = false,
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
        return 'GROUP_CONCAT(' . ($this->distinct ? 'DISTINCT ' : '') . $this->fieldDescriptor->getSelect() . ' SEPARATOR \'' . $this->glue . '\')';
    }

    public function getWhere()
    {
        return $this->fieldDescriptor->getSelect();
    }

    public function getJoins()
    {
        return $this->fieldDescriptor->getJoins();
    }
}
