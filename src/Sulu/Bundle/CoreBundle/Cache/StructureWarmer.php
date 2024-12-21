<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Cache;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Generates the Structure cache files.
 */
class StructureWarmer implements CacheWarmerInterface
{
    public function __construct(private StructureManagerInterface $structureManager)
    {
    }

    /**
     * Warm up the cache for page and snippets.
     *
     * {@inheritdoc}
     */
    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        // warmup the pages
        $this->structureManager->getStructures(Structure::TYPE_PAGE);

        // warm up the snippets
        $this->structureManager->getStructures(Structure::TYPE_SNIPPET);

        return [];
    }

    /**
     * Return true for isOptonal method.
     *
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }
}
