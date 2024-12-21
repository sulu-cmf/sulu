<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit;

use Jackalope\Workspace;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\UUIDHelper;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\NodeHelper;
use Sulu\Component\DocumentManager\NodeHelperInterface;

class NodeHelperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var NodeHelperInterface
     */
    private $nodeHelper;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $session;

    public function setUp(): void
    {
        $this->nodeHelper = new NodeHelper();

        $this->node = $this->prophesize(NodeInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);

        $this->node->getSession()->willReturn($this->session->reveal());
    }

    public function testMove(): void
    {
        $destinationNode = $this->prophesize(NodeInterface::class);
        $destinationNode->getPath()->willReturn('/path/to/some/other/node');
        $this->session->getNodeByIdentifier('5f2a72d1-e384-4571-9663-51902d62ac86')
            ->willReturn($destinationNode->reveal());

        $this->node->getName()->willReturn('node');
        $this->node->getPath()->willReturn('/path/to/node');

        $this->session->move('/path/to/node', '/path/to/some/other/node/node')->shouldBeCalled();
        $this->nodeHelper->move($this->node->reveal(), '5f2a72d1-e384-4571-9663-51902d62ac86');
    }

    public function testMoveWithDestinationName(): void
    {
        $destinationNode = $this->prophesize(NodeInterface::class);
        $destinationNode->getPath()->willReturn('/path/to/some/other/node');
        $this->session->getNodeByIdentifier('5f2a72d1-e384-4571-9663-51902d62ac86')
            ->willReturn($destinationNode->reveal());

        $this->node->getPath()->willReturn('/path/to/node');

        $this->session->move('/path/to/node', '/path/to/some/other/node/new-node')->shouldBeCalled();
        $this->nodeHelper->move($this->node->reveal(), '5f2a72d1-e384-4571-9663-51902d62ac86', 'new-node');
    }

    public function testCopy(): void
    {
        /** @var ObjectProphecy<Workspace> $workspace */
        $workspace = $this->prophesize(Workspace::class);
        $this->session->getWorkspace()->willReturn($workspace->reveal());
        $identifier = UUIDHelper::generateUUID();

        $destinationNode = $this->prophesize(NodeInterface::class);
        $destinationNode->getPath()->willReturn('/path/to/some/other/node');
        $this->session->getNodeByIdentifier($identifier)->willReturn($destinationNode->reveal());

        $this->node->getName()->willReturn('node');
        $this->node->getPath()->willReturn('/path/to/node');

        $workspace->copy('/path/to/node', '/path/to/some/other/node/node');
        $this->assertEquals('/path/to/some/other/node/node', $this->nodeHelper->copy($this->node->reveal(), $identifier));
    }

    public function testCopyWithDestinationName(): void
    {
        $workspace = $this->prophesize(Workspace::class);
        $this->session->getWorkspace()->willReturn($workspace->reveal());
        $identifier = UUIDHelper::generateUUID();

        $destinationNode = $this->prophesize(NodeInterface::class);
        $destinationNode->getPath()->willReturn('/path/to/some/other/node');
        $this->session->getNodeByIdentifier($identifier)->willReturn($destinationNode->reveal());

        $this->node->getPath()->willReturn('/path/to/node');

        $workspace->copy('/path/to/node', '/path/to/some/other/node/new-node');
        $this->assertEquals('/path/to/some/other/node/new-node', $this->nodeHelper->copy($this->node->reveal(), $identifier, 'new-node'));
    }

    public function testReorderUuidTarget(): void
    {
        $parentNode = $this->prophesize(NodeInterface::class);
        $parentNode->getPath()->willReturn('/path/to');
        $this->node->getParent()->willReturn($parentNode->reveal());
        $this->node->getName()->willReturn('node');

        $siblingNode = $this->prophesize(NodeInterface::class);
        $siblingNode->getPath()->willReturn('/path/to/sibling');
        $this->session->getNodeByIdentifier('uuid')->willReturn($siblingNode->reveal());

        $parentNode->orderBefore('node', 'sibling')->shouldBeCalled();

        $this->nodeHelper->reorder($this->node->reveal(), 'uuid');
    }

    public function testExceptionTargetNotSibling(): void
    {
        $this->expectException(
            DocumentManagerException::class,
            'Cannot reorder documents which are not sibilings. Trying to reorder "/path/to/node" to "/path/to/deep/node".'
        );
        $parentNode = $this->prophesize(NodeInterface::class);
        $parentNode->getPath()->willReturn('/path/to');
        $this->node->getParent()->willReturn($parentNode->reveal());
        $this->node->getPath()->willReturn('/path/to/node');

        $nonSiblingNode = $this->prophesize(NodeInterface::class);
        $nonSiblingNode->getPath()->willReturn('/path/to/deep/node');
        $this->session->getNodeByIdentifier('uuid')->willReturn($nonSiblingNode->reveal());

        $this->nodeHelper->reorder($this->node->reveal(), 'uuid');
    }

    public function testOrderAfterLast(): void
    {
        $parentNode = $this->prophesize(NodeInterface::class);
        $parentNode->getPath()->willReturn('/path/to');
        $this->node->getParent()->willReturn($parentNode->reveal());
        $this->node->getName()->willReturn('node');

        $parentNode->orderBefore('node', null)->shouldBeCalled();

        $this->nodeHelper->reorder($this->node->reveal(), null);
    }
}
