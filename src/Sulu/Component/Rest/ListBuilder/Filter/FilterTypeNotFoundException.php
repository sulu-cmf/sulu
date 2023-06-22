<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Filter;

class FilterTypeNotFoundException extends \Exception
{
    public function __construct(private string $type)
    {
        parent::__construct(\sprintf('There is no FilterType registered for the type "%s".', $this->type));
    }

    public function getType(): string
    {
        return $this->type;
    }
}
