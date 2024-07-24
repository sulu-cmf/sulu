<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\ListBuilder;

use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

final class ElasticSearchFieldDescriptorBuilder
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $translation;

    /**
     * @var string
     */
    private $sortField;

    /**
     * @var string
     */
    private $visibility = FieldDescriptorInterface::VISIBILITY_YES;

    /**
     * @var string
     */
    private $searchability = FieldDescriptorInterface::SEARCHABILITY_NEVER;

    /**
     * @var string
     */
    private $type = 'string';

    /**
     * @var bool
     */
    private $sortable = false;

    /**
     * @var string
     */
    private $searchField = '';

    public function __construct(string $name, ?string $translation = null)
    {
        $this->name = $name;
        $this->translation = $translation;
    }

    public function setSortField(string $sortField)
    {
        $this->sortField = $sortField;
        $this->sortable = true;

        return $this;
    }

    public function setVisibility(string $visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function setSearchability(string $searchability)
    {
        $this->searchability = $searchability;

        return $this;
    }

    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    public function setSearchField(string $searchField): self
    {
        $this->searchField = $searchField;

        return $this;
    }

    public function build(): ElasticSearchFieldDescriptor
    {
        return new ElasticSearchFieldDescriptor(
            $this->name,
            $this->sortField,
            $this->translation,
            $this->visibility,
            $this->searchability,
            $this->type,
            $this->sortable,
            $this->searchField,
        );
    }
}
