<?php

namespace Sulu\Component\Content\Mapper\LocalizationFinder;

use Sulu\Component\Content\Mapper\LocalizationFinder\DelegatingFinder;
use Prophecy\PhpUnit\ProphecyTestCase;

class ParentChildAnyFinderTest extends ProphecyTestCase
{
    private $node;
    private $finder;

    public function setUp()
    {
        parent::setUp();
        $this->node = $this->prophesize('PHPCR\NodeInterface');
        $webspaceManager = $this->prophesize('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');

        $this->finder = new ParentChildAnyFinder($webspaceManager->reveal(), 'prefix', 'internal');
    }

    public function testSupportsNonNullWebspace()
    {
        $res = $this->finder->supports($this->node->reveal(), 'foobar', 'webspace');
        $this->assertTrue($res);
    }

    public function testSupportsNullWebspace()
    {
        $res = $this->finder->supports($this->node->reveal(), 'foobar', null);
        $this->assertFalse($res);
    }
}
