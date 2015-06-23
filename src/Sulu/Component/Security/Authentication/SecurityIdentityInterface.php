<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

/**
 * Interface for SecurityIdentities, which will return a name. Required for making decoupled references.
 */
interface SecurityIdentityInterface
{
    /**
     * Returns the identifier for a SecurityIdentity.
     *
     * @return string
     */
    public function getIdentifier();
}
