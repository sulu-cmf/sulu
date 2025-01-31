<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Core;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension providing generally useful utilities which are available
 * in the Sulu\Component\Util namespace.
 */
class UtilTwigExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('sulu_util_multisort', [\Sulu\Component\Util\SortUtils::class, 'multisort']),
            new TwigFilter('sulu_util_filter', [\Sulu\Component\Util\ArrayUtils::class, 'filter']),
        ];
    }
}
