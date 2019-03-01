<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\NodeTypes\Content;

/**
 * Node type for representing snippets in the PHPCR.
 */
class SnippetNodeType extends ContentNodeType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu:snippet';
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaredSupertypeNames()
    {
        return [
            'sulu:content',
        ];
    }
}
