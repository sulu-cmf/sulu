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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * @experimental
 */
interface SingleSignOnAdapterInterface
{
    public function generateLoginUrl(Request $request, string $redirectUrl, string $domain): string;

    /**
     * @param array<mixed> $expectedAttributes
     * @param array<mixed> $givenAttributes
     */
    public function isAuthorizationValid(array $expectedAttributes, array $givenAttributes): bool;

    public function createOrUpdateUser(string $token): UserBadge;
}
