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

trait ListRouteBuilderTrait
{
    use ToolbarActionsRouteBuilderTrait;

    private function setResourceKeyToRoute(Route $route, string $resourceKey): void
    {
        $route->setOption('resourceKey', $resourceKey);
    }

    private function setListKeyToRoute(Route $route, string $listKey): void
    {
        $route->setOption('listKey', $listKey);
    }

    private function setUserSettingsKeyToRoute(Route $route, string $userSettingsKey): void
    {
        $route->setOption('userSettingsKey', $userSettingsKey);
    }

    private function setTitleToRoute(Route $route, string $title): void
    {
        $route->setOption('title', $title);
    }

    private function addListAdaptersToRoute(Route $route, array $listAdapters): void
    {
        $oldListAdapters = $route->getOption('adapters');
        $newListAdapters = $oldListAdapters ? array_merge($oldListAdapters, $listAdapters) : $listAdapters;
        $route->setOption('adapters', $newListAdapters);
    }

    private function setBackRouteToRoute(Route $route, string $backRoute): void
    {
        $route->setOption('backRoute', $backRoute);
    }

    private function setAddRouteToRoute(Route $route, string $addRoute): void
    {
        $route->setOption('addRoute', $addRoute);
    }

    private function setEditRouteToRoute(Route $route, string $editRoute): void
    {
        $route->setOption('editRoute', $editRoute);
    }

    private function setSearchableToRoute(Route $route, bool $searchable): void
    {
        $route->setOption('searchable', $searchable);
    }

    private function addRouterAttributesToListRequestToRoute(Route $route, array $routerAttributesToListRequest): void
    {
        $oldRouterAttributesToListRequest = $route->getOption('routerAttributesToListRequest');
        $newRouterAttributesToListRequest = $oldRouterAttributesToListRequest
            ? array_merge($oldRouterAttributesToListRequest, $routerAttributesToListRequest)
            : $routerAttributesToListRequest;

        $route->setOption('routerAttributesToListRequest', $newRouterAttributesToListRequest);
    }

    private function addRouterAttributesToListMetadataToRoute(Route $route, array $routerAttributesToListMetadata): void
    {
        $oldRouterAttributesToListMetadata = $route->getOption('routerAttributesToListMetadata');
        $newRouterAttributesToListMetadata = $oldRouterAttributesToListMetadata
            ? array_merge($oldRouterAttributesToListMetadata, $routerAttributesToListMetadata)
            : $routerAttributesToListMetadata;

        $route->setOption('routerAttributesToListMetadata', $newRouterAttributesToListMetadata);
    }

    private function addLocalesToRoute(Route $route, array $locales): void
    {
        $oldLocales = $route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $route->setOption('locales', $newLocales);
    }

    private function setDefaultLocaleToRoute(Route $route, string $locale): void
    {
        $route->setAttributeDefault('locale', $locale);
    }

    private function addResourceStorePropertiesToListRequestToRoute(Route $route, array $resourceStorePropertiesToListRequest): void
    {
        $oldResourceStorePropertiesToListRequest = $route->getOption('resourceStorePropertiesToListRequest');
        $newResourceStorePropertiesToListRequest = $oldResourceStorePropertiesToListRequest
            ? array_merge($oldResourceStorePropertiesToListRequest, $resourceStorePropertiesToListRequest)
            : $resourceStorePropertiesToListRequest;

        $route->setOption('resourceStorePropertiesToListRequest', $newResourceStorePropertiesToListRequest);
    }
}
