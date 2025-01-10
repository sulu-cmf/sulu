<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleIconSelect extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('single_icon_select');
    }

    public function getContentData(PropertyInterface $property)
    {
        $params = $property->getParams();

        $content = [
            'icon' => $property->getValue(),
            'iconSet' => $params['icon_set']->getValue(),
        ];

        return $content;
    }
}
