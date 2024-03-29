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

use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

/**
 * Extends the default FieldDescriptor with the property sort field to configure it.
 * Default is the name.
 */
class ElasticSearchFieldDescriptor extends FieldDescriptor
{
    public static function create(string $name, ?string $translation = null): ElasticSearchFieldDescriptorBuilder
    {
        return new ElasticSearchFieldDescriptorBuilder($name, $translation);
    }

    /**
     * @var string
     */
    private $sortField;

    /**
     * @var string
     */
    private $searchField;

    public function __construct(
        string $name,
        ?string $sortField = null,
        ?string $translation = null,
        string $visibility = FieldDescriptorInterface::VISIBILITY_YES,
        string $searchability = FieldDescriptorInterface::SEARCHABILITY_NEVER,
        string $type = '',
        bool $sortable = true,
        string $searchField = ''
    ) {
        $this->sortField = $sortField ? $sortField : $name;
        $this->searchField = $searchField;

        parent::__construct(
            $name,
            $translation,
            $visibility,
            $searchability,
            $type,
            $sortable
        );
    }

    public function getSortField(): string
    {
        return $this->sortField;
    }

    public function getSearchField(): string
    {
        return $this->searchField;
    }
}
