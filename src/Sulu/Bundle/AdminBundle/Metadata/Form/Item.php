<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\Form;

use JMS\Serializer\Annotation as Serializer;

abstract class Item
{
    /**
     * @var string
     *
     * @Serializer\Exclude()
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $disabledCondition;

    /**
     * @var string
     */
    protected $visibleCondition;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var null|int
     */
    protected $size;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getDisabledCondition(): ?string
    {
        return $this->disabledCondition;
    }

    public function setDisabledCondition(?string $disabledCondition): void
    {
        $this->disabledCondition = $disabledCondition;
    }

    public function getVisibleCondition(): ?string
    {
        return $this->visibleCondition;
    }

    public function setVisibleCondition(?string $visibleCondition): void
    {
        $this->visibleCondition = $visibleCondition;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size = null): void
    {
        $this->size = $size;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
