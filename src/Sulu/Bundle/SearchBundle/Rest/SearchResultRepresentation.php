<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Rest;

use Hateoas\Representation\PaginatedRepresentation;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\XmlAttribute;

class SearchResultRepresentation extends PaginatedRepresentation
{
    /**
     * @Expose
     * @XmlAttribute
     *
     * @var array
     */
    protected $totals;

    /**
     * @Expose
     * @XmlAttribute
     *
     * @var float
     */
    protected $time;

    /**
     * {@inheritDoc}
     *
     * @param array $totals
     */
    public function __construct(
        $inline,
        $route,
        array $parameters = array(),
        $page,
        $limit,
        $pages,
        $pageParameterName = null,
        $limitParameterName = null,
        $absolute = false,
        $total = null,
        $totals = array(),
        $time
    ) {
        parent::__construct(
            $inline,
            $route,
            $parameters,
            $page,
            $limit,
            $pages,
            $pageParameterName,
            $limitParameterName,
            $absolute,
            $total,
            $time
        );

        $this->totals = $totals;
        $this->time = $time;
    }
}
