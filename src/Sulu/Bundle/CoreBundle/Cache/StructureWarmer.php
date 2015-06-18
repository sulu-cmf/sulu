<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Compat\Structure;

/**
 * Generates the Structure cache files.
 */
class StructureWarmer implements CacheWarmerInterface
{
    /**
     * @var StructureManager
     */
    private $structureManager;

    public function __construct(StructureManagerInterface $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    public function warmUp($cacheDir)
    {
        // warmup the pages
        $this->structureManager->getStructures(Structure::TYPE_PAGE);

        // warm up the snippets
        $this->structureManager->getStructures(Structure::TYPE_SNIPPET);
    }

    public function isOptional()
    {
        return true;
    }
}
