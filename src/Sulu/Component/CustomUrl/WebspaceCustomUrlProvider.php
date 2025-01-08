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

use Sulu\Component\CustomUrl\Repository\CustomUrlRepositoryInterface;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Url\WebspaceUrlProviderInterface;
use Sulu\Component\Webspace\Webspace;

class WebspaceCustomUrlProvider implements WebspaceUrlProviderInterface
{
    public function __construct(
        private CustomUrlRepositoryInterface $customUrlRepository
    ) {
    }

    public function getUrls(Webspace $webspace, $environment): array
    {
        $urls = [];
        foreach ($this->customUrlRepository->findPathsByWebspace($webspace->getKey()) as $customUrl) {
            $urls[] = new Url($customUrl, $environment);
        }

        return $urls;
    }
}
