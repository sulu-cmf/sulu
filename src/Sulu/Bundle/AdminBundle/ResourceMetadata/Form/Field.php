<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata\Form;

class Field extends Item
{
    /**
     * @var Option[]
     */
    protected $options;

    /**
     * @var FieldType[]
     */
    protected $types;

    /**
     * @var bool
     */
    protected $required;

    /**
     * @var null|int
     */
    protected $spaceAfter;

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function addOption(Option $option): void
    {
        $this->options[$option->getName()] = $option;
    }

    /**
     * @return FieldType[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function addType(FieldType $type): void
    {
        $this->types[$type->getName()] = $type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    public function getSpaceAfter(): ?int
    {
        return $this->spaceAfter;
    }

    public function setSpaceAfter(int $spaceAfter = null): void
    {
        $this->spaceAfter = $spaceAfter;
    }
}
