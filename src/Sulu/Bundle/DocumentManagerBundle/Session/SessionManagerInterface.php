<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Session;

/**
 * The session manager knows about all sessions available in the document manager.
 *
 * This is especially useful if some component directly writes on nodes without using documents and drafting. It will
 * guarantee that the data will be available on both workspaces.
 */
interface SessionManagerInterface
{
    /**
     * Sets the property of the node at the given path for all available sessions. This means that the values saved in
     * this way will be immediately available in all workspaces.
     *
     * @param string $nodePath The path of the node to manipulate
     * @param string $propertyName The name of the property to set
     * @param mixed $value The value to set
     *
     * @return void
     */
    public function setNodeProperty($nodePath, $propertyName, $value);

    /**
     * Flushes the data for all sessions.
     *
     * @return void
     */
    public function flush();
}
