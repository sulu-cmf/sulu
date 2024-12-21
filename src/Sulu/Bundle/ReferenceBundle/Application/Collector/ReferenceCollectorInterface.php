<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Application\Collector;

use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;

interface ReferenceCollectorInterface
{
    public function addReference(
        string $resourceKey,
        string $resourceId,
        string $referenceProperty,
    ): ReferenceInterface;
}
