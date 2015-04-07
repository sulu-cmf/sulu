<?php

namespace Sulu\Bundle\CoreBundle\Tests\Cache;

use Sulu\Bundle\CoreBundle\Cache\StructureWarmer;

class StructureWarmerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->structureManager = $this->prophesize('Sulu\Component\Content\StructureManagerInterface');
        $this->warmer = new StructureWarmer($this->structureManager->reveal());
    }

    public function testWarmup()
    {
        $this->structureManager->getStructures('page')->shouldBeCalled();
        $this->structureManager->getStructures('snippet')->shouldBeCalled();
        $this->warmer->warmup('/not/important/argument');
    }
}
