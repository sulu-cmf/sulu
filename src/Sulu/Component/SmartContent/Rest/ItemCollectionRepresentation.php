<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Rest;

use JMS\Serializer\Annotation as Serializer;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\SmartContent\DatasourceItemInterface;

/**
 * Provides a collection of items and the datasource.
 */
#[Serializer\ExclusionPolicy('all')]
class ItemCollectionRepresentation extends CollectionRepresentation
{
    /**
     * @var int
     */
    private $total;

    public function __construct(array $items, private ?DatasourceItemInterface $datasource)
    {
        parent::__construct($items, 'items');
        $this->total = \count($items);
    }

    /**
     * Returns datasource of smart content item collection.
     *
     * @return DatasourceItemInterface|null
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * Returns amount of items.
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['total'] = $this->getTotal();
        $data['datasource'] = $this->getDatasource();

        return $data;
    }
}
