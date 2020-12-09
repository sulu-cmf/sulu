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

class EmptyStringMetadata extends StringMetadata
{
    public function __construct()
    {
        parent::__construct(null, 0);
    }
}
