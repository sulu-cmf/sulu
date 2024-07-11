<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Twig;

use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Cache\MemoizeTwigExtensionTrait;
use Twig\Extension\AbstractExtension;

/**
 * Provides memoized Twig functions to handle snippets.
 */
class MemoizedSnippetTwigExtension extends AbstractExtension
{
    use MemoizeTwigExtensionTrait;

    /**
     * @param int $lifeTime
     */
    public function __construct(
        SnippetTwigExtensionInterface $extension,
        MemoizeInterface $memoizeCache,
        $lifeTime,
    ) {
        $this->extension = $extension;
        $this->memoizeCache = $memoizeCache;
        $this->lifeTime = $lifeTime;
    }
}
