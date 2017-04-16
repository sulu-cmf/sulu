<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Permission;

use Symfony\Component\Security\Core\Util\SecureRandom;

/**
 * A simple class for creating random salts for users
 * @package Sulu\Bundle\SecurityBundle\Permission
 */
class SaltGenerator
{
    /**
     * Returns a random salt for password hashing
     * @return string
     */
    public function getRandomSalt()
    {
        $generator = new SecureRandom();
        return base64_encode($generator->nextBytes(32));
    }
}
