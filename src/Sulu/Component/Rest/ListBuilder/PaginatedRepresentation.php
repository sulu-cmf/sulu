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
 * This class represents a list for our common rest services.
 */
#[Serializer\ExclusionPolicy('all')]
class PaginatedRepresentation extends CollectionRepresentation implements RepresentationInterface
{
    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $total;

    /**
     * @var int
     */
    protected $pages;

    public function __construct($data, string $rel, ?int $page, int $limit, int $total)
    {
        parent::__construct($data, $rel);
        $this->page = $page;
        $this->limit = $limit;
        $this->total = $total;
        $this->pages = ($limit ? \ceil($total / $limit) : 1) ?: 1;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['limit'] = $this->getLimit();
        $data['total'] = $this->getTotal();
        $data['page'] = $this->getPage();
        $data['pages'] = $this->getPages();

        return $data;
    }
}
