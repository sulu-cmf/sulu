<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Hateoas\Configuration\Annotation\Relation;
use Hateoas\Configuration\Annotation\Route;
use JMS\Serializer\Annotation\ExclusionPolicy;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;

/**
 * This class represents a list for the categories.
 *
 * @ExclusionPolicy("all")
 * @Relation(
 *     "children",
 *     href = @Route(
 *         "expr(object.getRoute())",
 *         parameters = "expr({ parent: '{parentId}' } + object.getParameters())",
 *         absolute = "expr(object.isAbsolute())",
 *     )
 * )
 */
class CategoryListRepresentation extends ListRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function __construct($data, $rel, $route, $parameters, $page, $limit, $total)
    {
        parent::__construct($data, $rel, $route, $parameters, $page, $limit, $total);
    }
}
