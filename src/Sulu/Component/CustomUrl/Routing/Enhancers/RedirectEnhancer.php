<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Routing\Enhancers;

use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

/**
 * If custom-url is a redirect it appends url to defaults.
 */
class RedirectEnhancer extends AbstractEnhancer
{
    public function __construct(private WebspaceManagerInterface $webspaceManager)
    {
    }

    protected function doEnhance(
        CustomUrlBehavior $customUrl,
        Webspace $webspace,
        array $defaults,
        Request $request
    ) {
        $resourceSegment = '/';
        if (null !== $customUrl->getTargetDocument()) {
            $resourceSegment = $customUrl->getTargetDocument()->getResourceSegment();
        }

        $url = $this->webspaceManager->findUrlByResourceLocator(
            $resourceSegment,
            $defaults['_environment'],
            $customUrl->getTargetLocale(),
            $defaults['_webspace']->getKey(),
            $request->getHost(),
            $request->getScheme()
        );

        $requestFormat = $request->getRequestFormat(null);
        $requestFormatSuffix = $requestFormat ? '.' . $requestFormat : '';

        $queryString = $request->getQueryString();
        $queryStringSuffix = $queryString ? '?' . $queryString : '';

        return [
            '_controller' => 'sulu_website.redirect_controller::redirectAction',
            'url' => $url . $requestFormatSuffix . $queryStringSuffix,
        ];
    }

    protected function supports(CustomUrlBehavior $customUrl)
    {
        return $customUrl->isRedirect() || null === $customUrl->getTargetDocument();
    }
}
