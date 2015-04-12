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
use Sulu\Component\Content\Structure\Factory\StructureFactoryInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\Compat\Stucture\LegacyStructureConstants;

/**
 * Generates the Structure cache files
 */
class StructureWarmer implements CacheWarmerInterface
{
    /**
     * @var StructureManager
     */
    private $structureFactory;

    public function __construct(StructureFactoryInterface $structureFactory)
    {
        $this->structureFactory = $structureFactory;
    }

    public function warmUp($cacheDir)
    {
        // warmup the pages
        $this->structureFactory->getStructures(LegacyStructureConstants::TYPE_PAGE);

        // warm up the snippets
        $this->structureFactory->getStructures(LegacyStructureConstants::TYPE_SNIPPET);
    }

    public function isOptional()
    {
        return true;
    }
}
