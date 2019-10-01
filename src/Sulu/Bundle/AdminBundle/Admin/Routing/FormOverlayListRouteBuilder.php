<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

class FormOverlayListRouteBuilder implements FormOverlayListRouteBuilderInterface
{
    use RouteBuilderTrait;
    use ListRouteBuilderTrait, FormRouteBuilderTrait {
        ListRouteBuilderTrait::setResourceKeyToRoute insteadof FormRouteBuilderTrait;
        ListRouteBuilderTrait::setBackRouteToRoute insteadof FormRouteBuilderTrait;
        ListRouteBuilderTrait::setEditRouteToRoute insteadof FormRouteBuilderTrait;
        ListRouteBuilderTrait::addLocalesToRoute insteadof FormRouteBuilderTrait;
        ListRouteBuilderTrait::addToolbarActionsToRoute insteadof FormRouteBuilderTrait;
    }
    use TabRouteBuilderTrait;

    const VIEW = 'sulu_admin.form_overlay_list';

    public function __construct(string $name, string $path)
    {
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): FormOverlayListRouteBuilderInterface
    {
        $this->setResourceKeyToRoute($this->route, $resourceKey);

        return $this;
    }

    public function setListKey(string $listKey): FormOverlayListRouteBuilderInterface
    {
        $this->setListKeyToRoute($this->route, $listKey);

        return $this;
    }

    public function setFormKey(string $formKey): FormOverlayListRouteBuilderInterface
    {
        $this->setFormKeyToRoute($this->route, $formKey);

        return $this;
    }

    public function setApiOptions(array $apiOptions): FormOverlayListRouteBuilderInterface
    {
        $this->setApiOptionsToRoute($this->route, $apiOptions);

        return $this;
    }

    public function setTitle(string $title): FormOverlayListRouteBuilderInterface
    {
        $this->setTitleToRoute($this->route, $title);

        return $this;
    }

    public function setAddOverlayTitle(string $addOverlayTitle): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('addOverlayTitle', $addOverlayTitle);

        return $this;
    }

    public function setEditOverlayTitle(string $editOverlayTitle): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('editOverlayTitle', $editOverlayTitle);

        return $this;
    }

    public function setTabTitle(string $tabTitle): FormOverlayListRouteBuilderInterface
    {
        $this->setTabTitleToRoute($this->route, $tabTitle);

        return $this;
    }

    public function setTabOrder(int $tabOrder): FormOverlayListRouteBuilderInterface
    {
        $this->setTabOrderToRoute($this->route, $tabOrder);

        return $this;
    }

    public function setTabCondition(string $tabCondition): FormOverlayListRouteBuilderInterface
    {
        $this->setTabConditionToRoute($this->route, $tabCondition);

        return $this;
    }

    public function addListAdapters(array $listAdapters): FormOverlayListRouteBuilderInterface
    {
        $this->addListAdaptersToRoute($this->route, $listAdapters);

        return $this;
    }

    public function addLocales(array $locales): FormOverlayListRouteBuilderInterface
    {
        $this->addLocalesToRoute($this->route, $locales);

        return $this;
    }

    public function setDefaultLocale(string $locale): FormOverlayListRouteBuilderInterface
    {
        $this->setDefaultLocaleToRoute($this->route, $locale);

        return $this;
    }

    public function addToolbarActions(array $toolbarActions): FormOverlayListRouteBuilderInterface
    {
        $this->addToolbarActionsToRoute($this->route, $toolbarActions);

        return $this;
    }

    public function setBackRoute(string $backRoute): FormOverlayListRouteBuilderInterface
    {
        $this->setBackRouteToRoute($this->route, $backRoute);

        return $this;
    }

    public function enableSearching(): FormOverlayListRouteBuilderInterface
    {
        $this->setSearchableToRoute($this->route, true);

        return $this;
    }

    public function disableSearching(): FormOverlayListRouteBuilderInterface
    {
        $this->setSearchableToRoute($this->route, false);

        return $this;
    }

    public function addRouterAttributesToListRequest(array $routerAttributesToListRequest): FormOverlayListRouteBuilderInterface
    {
        $this->addRouterAttributesToListRequestToRoute($this->route, $routerAttributesToListRequest);

        return $this;
    }

    public function addRouterAttributesToFormRequest(array $routerAttributesToFormRequest): FormOverlayListRouteBuilderInterface
    {
        $this->addRouterAttributesToFormRequestToRoute($this->route, $routerAttributesToFormRequest);

        return $this;
    }

    public function addResourceStorePropertiesToListRequest(array $resourceStorePropertiesToListRequest): FormOverlayListRouteBuilderInterface
    {
        $this->addResourceStorePropertiesToListRequestToRoute($this->route, $resourceStorePropertiesToListRequest);

        return $this;
    }

    public function addResourceStorePropertiesToFormRequest(array $resourceStorePropertiesToFormRequest): FormOverlayListRouteBuilderInterface
    {
        $oldResourceStorePropertiesToFormRequest = $this->route->getOption('resourceStorePropertiesToFormRequest');
        $newResourceStorePropertiesToFormRequest = $oldResourceStorePropertiesToFormRequest ? array_merge($oldResourceStorePropertiesToFormRequest, $resourceStorePropertiesToFormRequest) : $resourceStorePropertiesToFormRequest;
        $this->route->setOption('resourceStorePropertiesToFormRequest', $newResourceStorePropertiesToFormRequest);

        return $this;
    }

    public function setOverlaySize(string $overlaySize): FormOverlayListRouteBuilderInterface
    {
        $this->route->setOption('overlaySize', $overlaySize);

        return $this;
    }

    public function getRoute(): Route
    {
        if (!$this->route->getOption('resourceKey')) {
            throw new \DomainException(
                'A route for a form-overlay-list view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if (!$this->route->getOption('listKey')) {
            throw new \DomainException(
                'A route for a form-overlay-list view needs a "listKey" option.'
                . ' You have likely forgotten to call the "setListKey" method.'
            );
        }

        if (!$this->route->getOption('formKey')) {
            throw new \DomainException(
                'A route for a form-overlay-list view needs a "formKey" option.'
                . ' You have likely forgotten to call the "setFormKey" method.'
            );
        }

        if (!$this->route->getOption('adapters')) {
            throw new \DomainException(
                'A route for a form-overlay-list needs a "adapters" option.'
                . ' You have likely forgotten to call the "addListAdapters" method.'
            );
        }

        if ($this->route->getOption('locales') && false === strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a form-overlay-list needs a ":locale" placeholder in its URL if some "locales" have been set.'
            );
        }

        if (!$this->route->getOption('locales') && false !== strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a form-overlay-list cannot have a ":locale" placeholder in its URL if no "locales" have been set.'
            );
        }

        return clone $this->route;
    }
}
