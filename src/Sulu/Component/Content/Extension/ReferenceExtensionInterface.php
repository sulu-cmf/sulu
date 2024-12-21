<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Extension;

use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;

interface ReferenceExtensionInterface
{
    /**
     * @param mixed[] $data
     */
    public function getReferences(array $data, ReferenceCollectorInterface $referenceCollector, string $propertyPrefix = ''): void;
}
