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

use Twig\DeprecatedCallableInfo;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension providing generally useful utilities which are available
 * in the Sulu\Component\Util namespace.
 */
class UtilTwigExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('sulu_util_multisort', 'Sulu\Component\Util\SortUtils::multisort'),
            new TwigFilter('sulu_util_filter', 'Sulu\Component\Util\ArrayUtils::filter'),
            new TwigFilter(
                'sulu_util_domain_info',
                [$this, 'extract'],
                ['deprecation_info' => new DeprecatedCallableInfo(package: 'sulu/sulu', version: 2.3)],
            ),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction(
                'sulu_util_domain_info',
                [$this, 'extract'],
                ['deprecation_info' => new DeprecatedCallableInfo(package: 'sulu/sulu', version: 2.3)],
            ),
        ];
    }

    /**
     * @deprecated The "sulu_util_domain_info" is deprecated and will be removed with Sulu 3.0.
     */
    public function extract($url, $mode = null)
    {
        return 'hallo';
        @trigger_deprecation('sulu/sulu', '2.3', 'The "sulu_util_domain_info" is deprecated and will be removed with Sulu 3.0.');

        if (\function_exists('tld_extract')) {
            return tld_extract($url, $mode);
        }

        throw new \LogicException(
            'The "sulu_util_domain_info" requires "layershifter/tld-extract" package to be installed.'
        );
    }
}
