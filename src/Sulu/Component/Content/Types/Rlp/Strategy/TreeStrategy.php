<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Strategy;

use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;

/**
 * implements RLP Strategy "whole tree"
 */
class TreeStrategy extends RlpStrategy
{
    public function __construct(RlpMapperInterface $mapper, PathCleanupInterface $cleaner)
    {
        parent::__construct('whole-tree', $mapper, $cleaner);
    }

    /**
     * internal generator
     * @param string $title
     * @param string $parentPath
     * @return string
     */
    protected function generatePath($title, $parentPath = null)
    {
        // if parent has no resource create a new tree
        if ($parentPath == null) {
            return '/' . $title;
        }
        // concat parentPath and title to whole tree path
        return $parentPath . '/' . $title;
    }
}
