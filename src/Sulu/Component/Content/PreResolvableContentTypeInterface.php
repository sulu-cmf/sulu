<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * This interface indicates that the content-type uses the pre-resolve hock of the structure-resolver.
 */
interface PreResolvableContentTypeInterface
{
    /**
     * Will be called before the property will get resolved.
     *
     * @return void
     */
    public function preResolve(PropertyInterface $property);
}
