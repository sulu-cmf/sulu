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

use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class SingleSignOnTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private SingleSignOnAdapterProvider $singleSignOnAdapterProvider,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        [$domain, $token] = \explode('::', $accessToken, 2);
        $adapter = $this->singleSignOnAdapterProvider->getAdapterByDomain($domain);

        if (!$adapter) {
            throw new \RuntimeException(\sprintf('No adapter found for domain "%s".', $domain));
        }

        return $adapter->createOrUpdateUser($token);
    }
}
