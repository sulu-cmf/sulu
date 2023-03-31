<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Domain\Exception;

class StoreTrashItemHandlerNotFoundException extends \Exception
{
    private string $resourceKey;

    public function __construct(string $resourceKey)
    {
        $this->resourceKey = $resourceKey;

        parent::__construct(
            \sprintf('StoreTrashItemHandler for "%s" not found.', $this->resourceKey)
        );
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }
}
