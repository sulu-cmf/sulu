<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\SingleSignOn;

use Sulu\Bundle\SecurityBundle\SingleSignOn\Adapter\OpenId\OpenIdSingleSignOnAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

final class SingleSignOnTokenExtractor implements AccessTokenExtractorInterface
{
    public function __construct(private SingleSignOnAdapterProvider $singleSignOnAdapterProvider)
    {
    }

    public function extractAccessToken(Request $request): ?string
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');
        $routeName = $request->attributes->get('_route');

        if (!$code || !$state || 'sulu_admin' !== $routeName) {
            return null;
        }

        if (!$request->getSession()->has(OpenIdSingleSignOnAdapter::OPEN_ID_ATTRIBUTES)) {
            return null;
        }

        /** @var array{domain: string, state?: string} $expectedAttributes */
        $expectedAttributes = $request->getSession()->get(OpenIdSingleSignOnAdapter::OPEN_ID_ATTRIBUTES);
        // $request->getSession()->remove(OpenIdSingleSignOnAdapter::OPEN_ID_ATTRIBUTES); // TODO fix issue when we are removing the attributes from the session
        $adapter = $this->singleSignOnAdapterProvider->getAdapterByDomain($expectedAttributes['domain']);

        if (!$adapter || !$adapter->isAuthorizationValid($expectedAttributes, $request->query->all())) {
            throw new AccessDeniedHttpException('Invalid authorization via OpenId provider.');
        }

        return $expectedAttributes['domain'] . '::' . $code;
    }
}
