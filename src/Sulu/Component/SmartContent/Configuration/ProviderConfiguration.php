<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Configuration;

use Sulu\Component\Content\Compat\PropertyParameter;

/**
 * Provides configuration for smart-content.
 */
class ProviderConfiguration implements ProviderConfigurationInterface
{
    private ?string $datasourceResourceKey = null;

    private ?string $datasourceListKey = null;

    private ?string $datasourceAdapter = null;

    private bool $audienceTargeting = false;

    private bool $tags = false;

    /**
     * @var PropertyParameter[]
     */
    private array $types = [];

    private bool $categories = false;

    /**
     * @var PropertyParameter[]
     */
    private array $sorting = [];

    private bool $limit = false;

    private bool $presentAs = false;

    private bool $paginated = false;

    private ?string $view = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $resultToView = null;

    public function hasDatasource(): bool
    {
        return null !== $this->datasourceResourceKey && false !== $this->datasourceResourceKey;
    }

    public function getDatasourceResourceKey(): ?string
    {
        return $this->datasourceResourceKey;
    }

    public function setDatasourceResourceKey(string $datasourceResourceKey)
    {
        $this->datasourceResourceKey = $datasourceResourceKey;
    }

    public function setDatasourceListKey(string $datasourceListKey)
    {
        $this->datasourceListKey = $datasourceListKey;
    }

    public function getDatasourceListKey(): string
    {
        return $this->datasourceListKey;
    }

    public function getDatasourceAdapter(): ?string
    {
        return $this->datasourceAdapter;
    }

    public function setDatasourceAdapter(string $datasourceAdapter)
    {
        $this->datasourceAdapter = $datasourceAdapter;
    }

    public function hasAudienceTargeting(): bool
    {
        return $this->audienceTargeting;
    }

    public function setAudienceTargeting(bool $audienceTargeting)
    {
        $this->audienceTargeting = $audienceTargeting;
    }

    public function hasTags(): bool
    {
        return $this->tags;
    }

    public function setTags(bool $tags)
    {
        $this->tags = $tags;
    }

    /**
     * @return null|PropertyParameter[]
     */
    public function getTypes(): ?array
    {
        return $this->types;
    }

    public function hasTypes(): bool
    {
        return \count($this->types) > 0;
    }

    /**
     * @param array<array<string, string | array<string, string>> $types
     */
    public function setTypes(array $types)
    {
        $this->types = $types;
    }

    public function hasCategories(): bool
    {
        return $this->categories;
    }

    public function setCategories(bool $categories)
    {
        $this->categories = $categories;
    }

    public function getSorting(): ?array
    {
        return $this->sorting;
    }

    public function hasSorting(): bool
    {
        return \count($this->sorting) > 0;
    }

    public function setSorting(array $sorting)
    {
        $this->sorting = $sorting;
    }

    public function hasLimit(): bool
    {
        return $this->limit;
    }

    public function setLimit(bool $limit)
    {
        $this->limit = $limit;
    }

    public function hasPresentAs(): bool
    {
        return $this->presentAs;
    }

    public function setPresentAs(bool $presentAs)
    {
        $this->presentAs = $presentAs;
    }

    public function hasPagination(): bool
    {
        return $this->paginated;
    }

    public function setPaginated(bool $paginated)
    {
        $this->paginated = $paginated;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    public function setView(?string $view)
    {
        $this->view = $view;
    }

    public function getResultToView(): ?array
    {
        return $this->resultToView;
    }

    public function setResultToView(?array $resultToView)
    {
        $this->resultToView = $resultToView;
    }
}
