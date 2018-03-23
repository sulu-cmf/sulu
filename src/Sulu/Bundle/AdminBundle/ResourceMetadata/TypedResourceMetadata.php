<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata;

use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\Datagrid;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\DatagridInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Type\Type;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Type\TypesInterface;

class TypedResourceMetadata implements DatagridInterface, TypesInterface
{
    /**
     * @var Datagrid
     */
    private $datagrid;

    /**
     * @var Type[]
     */
    private $types = [];

    public function getDatagrid(): Datagrid
    {
        return $this->datagrid;
    }

    public function setDatagrid(Datagrid $datagrid): void
    {
        $this->datagrid = $datagrid;
    }

    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function addType(Type $type): void
    {
        $this->types[$type->getName()] = $type;
    }
}
