<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use Symfony\Component\HttpFoundation\Request;

/**
 * This is an service helper for ListResources accessed
 * by an REST-API. It contains a few getters, which
 * deliver some values needed by the inheriting controller.
 * These values are calculated from the request paramaters.
 */
class ListRestHelper implements ListRestHelperInterface
{
    /**
     * The current request object.
     *
     * @var Request
     */
    protected $request;

    /**
     * The constructor takes the request as an argument, which
     * is injected by the service container.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Returns the current Request.
     *
     * @return Request
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the desired sort column.
     *
     * @return string
     */
    public function getSortColumn()
    {
        return $this->getRequest()->get('sortBy', 'id');
    }

    /**
     * Returns desired sort order.
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->getRequest()->get('sortOrder', 'asc');
    }

    /**
     * Returns the maximum number of elements in a single response.
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->getRequest()->get('limit', 10);
    }

    /**
     * Returns the calculated value for the starting position based
     * on the page and limit values.
     *
     * @return int|null
     */
    public function getOffset()
    {
        $page = $this->getRequest()->get('page', 1);
        $limit = $this->getLimit();

        return ($limit != null) ? $limit * ($page - 1) : null;
    }

    /**
     * returns the current page.
     *
     * @return mixed
     */
    public function getPage()
    {
        return $this->getRequest()->get('page', 1);
    }

    /**
     * Returns an array with all the fields, which should be contained in the response.
     * If null is returned every field should be contained.
     *
     * @return array|null
     */
    public function getFields()
    {
        $fields = $this->getRequest()->get('fields');

        return ($fields != null) ? explode(',', $fields) : null;
    }

    /**
     * Returns the pattern of the search.
     *
     * @return mixed
     */
    public function getSearchPattern()
    {
        return $this->getRequest()->get('search');
    }

    /**
     * Returns an array with all the fields the search pattern should be executed on.
     *
     * @return array|null
     */
    public function getSearchFields()
    {
        $searchFields = $this->getRequest()->get('searchFields');

        return ($searchFields != null) ? explode(',', $searchFields) : array();
    }
}
