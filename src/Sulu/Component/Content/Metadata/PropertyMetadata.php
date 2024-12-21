<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

/**
 * Metadata for a property. Contains both UI and model metadata.
 *
 * @deprecated use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata instead
 */
class PropertyMetadata extends ItemMetadata
{
    /**
     * Type of this property (e.g. "text_line", "smart_content").
     *
     * @var string
     */
    protected $type;

    /**
     * Placeholders for property.
     *
     * @var array
     */
    protected $placeholders;

    /**
     * If the property should be available in different localizations.
     *
     * @var bool
     */
    protected $localized = false;

    /**
     * If the property is required.
     *
     * @var bool
     */
    protected $required = false;

    /**
     * The number of grid columns the property should use in the admin interface.
     *
     * @var int
     */
    protected $colSpan = 12;

    /**
     * The number of grid columns the property should have space after.
     *
     * @var int
     */
    protected $spaceAfter = null;

    /**
     * The CSS class the property should use in the admin interface.
     *
     * @var string
     */
    protected $cssClass = null;

    /**
     * @var int
     */
    protected $minOccurs = null;

    /**
     * @var mixed
     */
    protected $maxOccurs = null;

    /**
     * @var string
     */
    protected $onInvalid;

    /**
     * @var StructureMetadata
     */
    protected $structure;

    /**
     * @var ComponentMetadata[]
     */
    public $components = [];

    /**
     * @var string
     */
    public $defaultComponentName;

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function setCssClass(?string $cssClass = null): self
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function getStructure(): StructureMetadata
    {
        return $this->structure;
    }

    public function setStructure(StructureMetadata $structure): self
    {
        $this->structure = $structure;

        return $this;
    }

    public function getMinOccurs(): ?int
    {
        return $this->minOccurs;
    }

    public function setMinOccurs(?int $minOccurs = null): self
    {
        if ($minOccurs) {
            $this->minOccurs = $minOccurs;
        }

        return $this;
    }

    public function getMaxOccurs(): ?int
    {
        return $this->maxOccurs;
    }

    public function setMaxOccurs(?int $maxOccurs = null): self
    {
        if ($maxOccurs) {
            $this->maxOccurs = $maxOccurs;
        }

        return $this;
    }

    /**
     * @deprecated - use getType
     */
    public function getContentTypeName()
    {
        return $this->type;
    }

    /**
     * @deprecated
     */
    public function getIsBlock()
    {
        return false;
    }

    public function getColSpan(): int
    {
        return $this->colSpan;
    }

    public function setColSpan(int $colSpan): self
    {
        $this->colSpan = $colSpan;

        return $this;
    }

    public function getSpaceAfter(): ?int
    {
        return $this->spaceAfter;
    }

    public function setSpaceAfter(?int $spaceAfter = null): self
    {
        $this->spaceAfter = $spaceAfter;

        return $this;
    }

    public function getPlaceholder($locale): string
    {
        return $this->placeholders[$locale];
    }

    public function setPlaceholders(array $placeholders): self
    {
        $this->placeholders = $placeholders;

        return $this;
    }

    public function getPlaceholders(): ?array
    {
        return $this->placeholders;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->minOccurs !== $this->maxOccurs;
    }

    public function isLocalized(): bool
    {
        return $this->localized;
    }

    public function setLocalized(bool $localized): self
    {
        $this->localized = $localized;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getOnInvalid(): ?string
    {
        return $this->onInvalid;
    }

    public function setOnInvalid(?string $onInvalid)
    {
        $this->onInvalid = $onInvalid;
    }

    /**
     * Return the default component name.
     *
     * @return string
     */
    public function getDefaultComponentName()
    {
        return $this->defaultComponentName;
    }

    /**
     * Return the components.
     *
     * @return ComponentMetadata[]
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * @param string $name
     *
     * @return ComponentMetadata|null
     */
    public function getComponentByName($name)
    {
        foreach ($this->components as $component) {
            if ($component->getName() == $name) {
                return $component;
            }
        }

        return null;
    }

    /**
     * Add a new component.
     */
    public function addComponent(ComponentMetadata $component)
    {
        $this->components[] = $component;
    }

    public function __clone()
    {
        $components = [];
        foreach ($this->components as $component) {
            $components[] = clone $component;
        }
        $this->components = $components;
    }
}
