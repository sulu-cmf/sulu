<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Block;

use Sulu\Component\Content\Compat\Block\BlockPropertyType;

class HiddenBlockVisitor implements BlockVisitorInterface
{
    public function visit(BlockPropertyType $block): ?BlockPropertyType
    {
        $blockPropertyTypeSettings = $block->getSettings();

        return \is_array($blockPropertyTypeSettings) && !empty($blockPropertyTypeSettings['hidden'])
            ? null
            : $block;
    }
}
