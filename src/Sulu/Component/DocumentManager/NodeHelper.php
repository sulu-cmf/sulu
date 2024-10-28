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
use PHPCR\SessionInterface;
use PHPCR\Util\PathHelper;
use PHPCR\Util\UUIDHelper;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

/**
 * The NodeHelper takes a node and some additional arguments to execute certain actions based on the passed node,
 * especially on the session of the passed Node.
 */
class NodeHelper implements NodeHelperInterface
{
    public function __construct(private PropertyEncoder $encoder)
    {
    }

    public function move(NodeInterface $node, $parentUuid, $destinationName = null)
    {
        if (!$destinationName) {
            $destinationName = $node->getName();
        }

        $session = $node->getSession();
        $parentPath = $this->normalizePath($session, $parentUuid);
        $session->move($node->getPath(), $parentPath . '/' . $destinationName);
    }

    public function copy(NodeInterface $node, $parentUuid, $destinationName = null)
    {
        if (!$destinationName) {
            $destinationName = $node->getName();
        }

        $session = $node->getSession();
        $parentPath = $this->normalizePath($session, $parentUuid);
        $destinationPath = $parentPath . '/' . $destinationName;
        $session->getWorkspace()->copy($node->getPath(), $destinationPath);

        return $destinationPath;
    }

    public function reorder(NodeInterface $node, $destinationUuid)
    {
        $session = $node->getSession();
        $parentNode = $node->getParent();

        if (!$destinationUuid) {
            $parentNode->orderBefore($node->getName(), null);

            return;
        }

        $siblingPath = $session->getNodeByIdentifier($destinationUuid)->getPath();

        if (PathHelper::getParentPath($siblingPath) !== $parentNode->getPath()) {
            throw new DocumentManagerException(
                \sprintf(
                    'Cannot reorder documents which are not sibilings. Trying to reorder "%s" to "%s".',
                    $node->getPath(),
                    $siblingPath
                )
            );
        }

        $parentNode->orderBefore($node->getName(), PathHelper::getNodeName($siblingPath));
    }

    public function sort(NodeInterface $node, string $locale): void
    {
        $parentNode = $node->getParent();

        $propertyName = $this->encoder->localizedContentName('title', $locale);

        $nodes = \iterator_to_array($parentNode->getNodes());
        \usort($nodes, function(NodeInterface $a, NodeInterface $b) use ($propertyName): int {
            return $a->getPropertyValueWithDefault($propertyName, '') <=> $b->getPropertyValueWithDefault($propertyName, '');
        });

        // Putting the last node in place (ordering at the end)
        $parentNode->orderBefore($nodes[\count($nodes) - 1]->getName(), null);

        // Ordering the nodes from the end backwards
        for ($i = \count($nodes) - 2; $i >= 0; --$i) {
            $parentNode->orderBefore($nodes[$i]->getName(), $nodes[$i + 1]->getName());
        }
    }

    /**
     * Returns the path based on the given UUID.
     *
     * @param string $identifier
     *
     * @return string
     */
    private function normalizePath(SessionInterface $session, $identifier)
    {
        if (!UUIDHelper::isUUID($identifier)) {
            return $identifier;
        }

        return $session->getNodeByIdentifier($identifier)->getPath();
    }
}
