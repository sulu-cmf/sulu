<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\SessionManager;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;

interface SessionManagerInterface
{
    /**
     * returns a valid session to interact with a phpcr database
     * @return SessionInterface
     */
    public function getSession();

    /**
     * returns the route node for given webspace
     * @param string $webspaceKey
     * @return NodeInterface
     */
    public function getRouteNode($webspaceKey = 'default');

    /**
     * returns the content node for given webspace
     * @param string $webspaceKey
     * @return NodeInterface
     */
    public function getContentNode($webspaceKey = 'default');
}
