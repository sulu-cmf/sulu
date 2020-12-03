<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

class ObjectMetadata extends SchemaMetadata
{
    /**
     * @param PropertyMetadata[] $properties
     */
    public function __construct(array $properties = [], ?int $minProperties = null, ?int $maxProperties = null)
    {
        parent::__construct($properties, [], [], 'object', $minProperties, $maxProperties);
    }
}
