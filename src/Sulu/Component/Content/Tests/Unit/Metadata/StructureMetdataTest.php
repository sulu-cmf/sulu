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

namespace Sulu\Component\Content\Tests\Unit\Metadata;

use Sulu\Component\Content\Metadata\StructureMetadata;

class StructureMetdataTest extends ItemMetadataCase
{
    public function getMetadata()
    {
        return new StructureMetadata();
    }
}
