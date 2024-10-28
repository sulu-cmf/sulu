<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

/**
 * The NodeHelperInterface describes action being executable on a node.
 */
interface NodeHelperInterface
{
    /**
     * Move the given node to the given parent node. Additionally a new name can also be passed.
     *
     * @param string $parentUuid
     * @param string|null $destinationName
     */
    public function move(NodeInterface $node, $parentUuid, $destinationName = null);

    /**
     * Copies the given node to the given parent node. Additionally a new name can also be passed.
     *
     * @param string $parentUuid
     * @param string|null $destinationName
     *
     * @return string The path of the new node
     */
    public function copy(NodeInterface $node, $parentUuid, $destinationName = null);

    /**
     * Reorders the given node before the given UUID. Throws an exception if the given node and the node identified by
     * the passed UUID are not siblings, since the operation would not be a simple reordering anymore.
     *
     * If the node should be passed to the last position null should be passed as destinationUuid.
     *
     * @param string|null $destinationUuid
     *
     * @throws DocumentManagerException
     */
    public function reorder(NodeInterface $node, $destinationUuid);

    /**
     * Sorting the siblings of a given node alphabetic order.
     *
     * @throws DocumentManagerException
     */
    public function sort(NodeInterface $node, string $locale): void;
}
