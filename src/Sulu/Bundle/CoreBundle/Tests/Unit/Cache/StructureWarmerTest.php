<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CoreBundle\Cache\StructureWarmer;
use Sulu\Component\Content\Compat\StructureManagerInterface;

class StructureWarmerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<StructureManagerInterface>
     */
    private $structureManager;

    /**
     * @var StructureWarmer
     */
    private $warmer;

    public function setUp(): void
    {
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->warmer = new StructureWarmer($this->structureManager->reveal());
    }

    public function testWarmup(): void
    {
        $this->structureManager->getStructures('page')->shouldBeCalled();
//        $this->structureManager->getStructures('snippet')->shouldBeCalled();
        $this->warmer->warmup('/not/important/argument');
    }
}
