<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use JMS\Serializer\Annotation as Serializer;

/**
 * @deprecated use PaginatedRepresentation instead
 */
#[Serializer\ExclusionPolicy('all')]
class ListRepresentation extends PaginatedRepresentation implements RepresentationInterface
{
    /**
     * @param mixed $data The data which will be presented
     * @param string $rel The name of the relation inside of the _embedded field
     * @param string $route The name of the route, for generating the links
     * @param array $parameters The parameters to append to the route
     * @param int $page The number of the current page
     * @param int|null $limit The size of one page
     * @param int $total The total number of elements
     */
    public function __construct($data, $rel, private $route, private $parameters, $page, $limit, $total)
    {
        parent::__construct($data, $rel, (int) $page, (int) $limit, (int) $total);
    }
}
