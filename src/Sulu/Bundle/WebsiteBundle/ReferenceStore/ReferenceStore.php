<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\ReferenceStore;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Represents implementation for reference-store.
 */
class ReferenceStore implements ReferenceStoreInterface, ResetInterface
{
    private array $ids = [];

    public function add($id)
    {
        if (\in_array($id, $this->ids)) {
            return;
        }

        $this->ids[] = $id;
    }

    public function getAll()
    {
        return $this->ids;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->ids = [];
    }
}
