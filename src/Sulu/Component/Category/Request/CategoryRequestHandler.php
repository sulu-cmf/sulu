<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Category\Request;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles categories in current request.
 */
class CategoryRequestHandler implements CategoryRequestHandlerInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getCategories($categoriesParameter = 'categories')
    {
        if (null !== $this->requestStack->getCurrentRequest()) {
            $categories = $this->requestStack->getCurrentRequest()->get($categoriesParameter, '');
        } else {
            $categories = '';
        }

        return \array_map(
            function($item) {
                return \trim($item);
            },
            \array_filter(\explode(',', $categories))
        );
    }

    public function appendCategoryToUrl($category, $categoriesParameter = 'categories')
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!(\is_array($category) && \array_key_exists('id', $category))) {
            return;
        }

        $id = $category['id'];

        // extend comma separated list
        $categories = $request->get($categoriesParameter, '');
        $categoriesArray = \array_filter(\array_merge(\explode(',', $categories), [$id]));
        $categories = \implode(',', \array_unique($categoriesArray));

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        $query = \array_merge($query, [$categoriesParameter => $categories]);

        $queryString = \http_build_query($query);

        return $request->getPathInfo() . (\strlen($queryString) > 0 ? '?' . $queryString : '');
    }

    public function removeCategoryFromUrl($category, $categoriesParameter = 'categories')
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!(\is_array($category) && \array_key_exists('id', $category))) {
            return;
        }

        $id = $category['id'];

        // extend comma separated list
        $categories = $request->get($categoriesParameter, '');
        $categoriesArray = \array_filter(\explode(',', $categories), function($categoryId) use ($id) {
            return $categoryId && $categoryId != $id;
        });
        $categories = \implode(',', \array_unique($categoriesArray));

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        $query = \array_merge($query, [$categoriesParameter => $categories]);

        $queryString = \http_build_query($query);

        return $request->getPathInfo() . (\strlen($queryString) > 0 ? '?' . $queryString : '');
    }

    public function toggleCategoryInUrl($category, $categoriesParameter = 'categories')
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!(\is_array($category) && \array_key_exists('id', $category))) {
            return;
        }

        $id = $category['id'];

        // extend comma separated list
        $categories = $request->get($categoriesParameter, '');
        $categoriesArray = \explode(',', $categories);

        if (\in_array($id, $categoriesArray)) {
            $categoriesArray = \array_filter($categoriesArray, function($categoryId) use ($id) {
                return $categoryId != $id;
            });
        } else {
            $categoriesArray = \array_merge($categoriesArray, [$id]);
        }

        $categoriesArray = \array_filter($categoriesArray);
        $categories = \implode(',', \array_unique($categoriesArray));

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        $query = \array_merge($query, [$categoriesParameter => $categories]);

        $queryString = \http_build_query($query);

        return $request->getPathInfo() . (\strlen($queryString) > 0 ? '?' . $queryString : '');
    }

    public function setCategoryToUrl($category, $categoriesParameter = 'categories')
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!(\is_array($category) && \array_key_exists('id', $category))) {
            return;
        }

        $id = $category['id'];

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        $query = \array_merge($query, [$categoriesParameter => $id]);

        $queryString = \http_build_query($query);

        return $request->getPathInfo() . (\strlen($queryString) > 0 ? '?' . $queryString : '');
    }

    public function removeCategoriesFromUrl($categoriesParameter = 'categories')
    {
        $request = $this->requestStack->getCurrentRequest();

        // get all parameter and extend with new tags string
        $query = $request->query->all();
        unset($query[$categoriesParameter]);

        $queryString = \http_build_query($query);

        return $request->getPathInfo() . (\strlen($queryString) > 0 ? '?' . $queryString : '');
    }
}
