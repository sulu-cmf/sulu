<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

class ListViewBuilder implements ListViewBuilderInterface
{
    use ViewBuilderTrait;
    use ListViewBuilderTrait;
    use TabViewChildBuilderTrait;

    public const TYPE = 'sulu_admin.list';

    public function __construct(string $name, string $path)
    {
        $this->view = new View($name, $path, static::TYPE);
    }

    public function setResourceKey(string $resourceKey): ListViewBuilderInterface
    {
        $this->setResourceKeyToView($this->view, $resourceKey);

        return $this;
    }

    public function setListKey(string $listKey): ListViewBuilderInterface
    {
        $this->setListKeyToView($this->view, $listKey);

        return $this;
    }

    public function setUserSettingsKey(string $userSettingsKey): ListViewBuilderInterface
    {
        $this->setUserSettingsKeyToView($this->view, $userSettingsKey);

        return $this;
    }

    public function setTitle(string $title): ListViewBuilderInterface
    {
        $this->setTitleToView($this->view, $title);

        return $this;
    }

    public function setTabTitle(string $tabTitle): ListViewBuilderInterface
    {
        $this->setTabTitleToView($this->view, $tabTitle);

        return $this;
    }

    public function setTabOrder(int $tabOrder): ListViewBuilderInterface
    {
        $this->setTabOrderToView($this->view, $tabOrder);

        return $this;
    }

    public function setTabPriority(int $tabPriority): ListViewBuilderInterface
    {
        $this->setTabPriorityToView($this->view, $tabPriority);

        return $this;
    }

    public function setTabCondition(string $tabCondition): ListViewBuilderInterface
    {
        $this->setTabConditionToView($this->view, $tabCondition);

        return $this;
    }

    public function addListAdapters(array $listAdapters): ListViewBuilderInterface
    {
        $this->addListAdaptersToView($this->view, $listAdapters);

        return $this;
    }

    /**
     * @param array<string> $locales
     */
    public function addLocales(array $locales): ListViewBuilderInterface
    {
        $this->addLocalesToView($this->view, $locales);

        return $this;
    }

    public function setDefaultLocale(string $locale): ListViewBuilderInterface
    {
        $this->setDefaultLocaleToView($this->view, $locale);

        return $this;
    }

    public function setItemDisabledCondition(string $itemDisabledCondition): ListViewBuilderInterface
    {
        $this->setItemDisabledConditionToView($this->view, $itemDisabledCondition);

        return $this;
    }

    /**
     * @param ToolbarAction[] $toolbarActions
     */
    public function addToolbarActions(array $toolbarActions): ListViewBuilderInterface
    {
        $this->addToolbarActionsToView($this->view, $toolbarActions);

        return $this;
    }

    public function addItemActions(array $itemActions): ListViewBuilderInterface
    {
        $this->addItemActionsToView($this->view, $itemActions);

        return $this;
    }

    public function setAddView(string $addView): ListViewBuilderInterface
    {
        $this->setAddViewToView($this->view, $addView);

        return $this;
    }

    public function setEditView(string $editView): ListViewBuilderInterface
    {
        $this->setEditViewToView($this->view, $editView);

        return $this;
    }

    public function setBackView(string $backView): ListViewBuilderInterface
    {
        $this->setBackViewToView($this->view, $backView);

        return $this;
    }

    public function enableSearching(): ListViewBuilderInterface
    {
        $this->setSearchableToView($this->view, true);

        return $this;
    }

    public function disableSearching(): ListViewBuilderInterface
    {
        $this->setSearchableToView($this->view, false);

        return $this;
    }

    public function enableSelection(): ListViewBuilderInterface
    {
        $this->setSelectableToView($this->view, true);

        return $this;
    }

    public function disableSelection(): ListViewBuilderInterface
    {
        $this->setSelectableToView($this->view, false);

        return $this;
    }

    public function enablePagination(): ListViewBuilderInterface
    {
        $this->setPaginatedToView($this->view, true);

        return $this;
    }

    public function disablePagination(): ListViewBuilderInterface
    {
        $this->setPaginatedToView($this->view, false);

        return $this;
    }

    public function enableTabGap(): ListViewBuilderInterface
    {
        $this->setDisableTabGapToView($this->view, false);

        return $this;
    }

    public function disableTabGap(): ListViewBuilderInterface
    {
        $this->setDisableTabGapToView($this->view, true);

        return $this;
    }

    public function enableColumnOptions(): ListViewBuilderInterface
    {
        $this->setHideColumnOptionsToView($this->view, false);

        return $this;
    }

    public function disableColumnOptions(): ListViewBuilderInterface
    {
        $this->setHideColumnOptionsToView($this->view, true);

        return $this;
    }

    public function enableFiltering(): ListViewBuilderInterface
    {
        $this->setFilterableToView($this->view, true);

        return $this;
    }

    public function disableFiltering(): ListViewBuilderInterface
    {
        $this->setFilterableToView($this->view, false);

        return $this;
    }

    public function addAdapterOptions(array $adapterOptions): ListViewBuilderInterface
    {
        $this->addAdapterOptionsToView($this->view, $adapterOptions);

        return $this;
    }

    public function addRouterAttributesToListRequest(array $routerAttributesToListRequest): ListViewBuilderInterface
    {
        $this->addRouterAttributesToListRequestToView($this->view, $routerAttributesToListRequest);

        return $this;
    }

    public function addRouterAttributesToListMetadata(array $routerAttributesToListMetadata): ListViewBuilderInterface
    {
        $this->addRouterAttributesToListMetadataToView($this->view, $routerAttributesToListMetadata);

        return $this;
    }

    public function addMetadataRequestParameters(array $metadataRequestParameters): ListViewBuilderInterface
    {
        $this->addMetadataRequestParametersToView($this->view, $metadataRequestParameters);

        return $this;
    }

    public function addResourceStorePropertiesToListRequest(array $resourceStorePropertiesToListRequest): ListViewBuilderInterface
    {
        $this->addResourceStorePropertiesToListRequestToView($this->view, $resourceStorePropertiesToListRequest);

        return $this;
    }

    public function addResourceStorePropertiesToListMetadata(array $resourceStorePropertiesToListMetadata): ListViewBuilderInterface
    {
        $this->addResourceStorePropertiesToListMetadataToView($this->view, $resourceStorePropertiesToListMetadata);

        return $this;
    }

    public function addRequestParameters(array $requestParameters): ListViewBuilderInterface
    {
        $this->addRequestParametersToView($this->view, $requestParameters);

        return $this;
    }

    /**
     * @param Badge[] $badges
     */
    public function addTabBadges(array $badges): ListViewBuilderInterface
    {
        $this->addTabBadgesToView($this->view, $badges);

        return $this;
    }

    public function getView(): View
    {
        if (!$this->view->getOption('resourceKey')) {
            throw new \DomainException(
                'A view for a list view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if (!$this->view->getOption('listKey')) {
            throw new \DomainException(
                'A view for a list view needs a "listKey" option.'
                . ' You have likely forgotten to call the "setListKey" method.'
            );
        }

        if (!$this->view->getOption('adapters')) {
            throw new \DomainException(
                'A view for a list needs a "adapters" option.'
                . ' You have likely forgotten to call the "addListAdapters" method.'
            );
        }

        if ($this->view->getOption('locales') && false === \strpos($this->view->getPath(), ':locale')) {
            throw new \DomainException(
                'A view for a list needs a ":locale" placeholder in its URL if some "locales" have been set.'
            );
        }

        if (!$this->view->getOption('locales') && false !== \strpos($this->view->getPath(), ':locale')) {
            throw new \DomainException(
                'A view for a list cannot have a ":locale" placeholder in its URL if no "locales" have been set.'
            );
        }

        return clone $this->view;
    }
}
