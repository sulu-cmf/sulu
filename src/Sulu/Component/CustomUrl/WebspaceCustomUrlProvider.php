<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl;

use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Url\WebspaceUrlProviderInterface;
use Sulu\Component\Webspace\Webspace;

/**
 * Returns custom-urls for given webspace.
 */
class WebspaceCustomUrlProvider implements WebspaceUrlProviderInterface
{
    public function __construct(private CustomUrlManagerInterface $customUrlManager)
    {
    }

    public function getUrls(Webspace $webspace, $environment)
    {
        $urls = [];
        foreach ($this->customUrlManager->findUrls($webspace->getKey()) as $customUrl) {
            $urls[] = new Url($customUrl);
        }

        return $urls;
    }
}
