<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Command;

use Jackalope\Workspace;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Command\CleanupHistoryCommand;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupHistoryCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $defaultSession;

    /**
     * @var Workspace
     */
    private $defaultWorkspace;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var Workspace
     */
    private $liveWorkspace;

    /**
     * @var CleanupHistoryCommand
     */
    private $cleanupHistoryCommand;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    public function setUp()
    {
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);

        $this->defaultSession = $this->prophesize(SessionInterface::class);
        $this->defaultWorkspace = $this->prophesize(Workspace::class);
        $this->defaultSession->getWorkspace()->willReturn($this->defaultWorkspace->reveal());

        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->liveWorkspace = $this->prophesize(Workspace::class);
        $this->liveSession->getWorkspace()->willReturn($this->liveWorkspace->reveal());

        $this->cleanupHistoryCommand = new CleanupHistoryCommand(
            $this->sessionManager->reveal(),
            $this->defaultSession->reveal(),
            $this->liveSession->reveal()
        );

        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
    }

    public function testExecute()
    {
        $this->input->getArgument('webspaceKey')->willReturn('sulu_io');
        $this->input->getArgument('locale')->willReturn('de');
        $this->input->getOption('base-path')->willReturn(null);
        $this->input->getOption('dry-run')->willReturn(false);
        $this->sessionManager->getRoutePath('sulu_io', 'de')->willReturn('/cmf/sulu_io/routes/de');
        $this->defaultSession->nodeExists('/cmf/sulu_io/routes/de')->willReturn(true);
        $this->liveSession->nodeExists('/cmf/sulu_io/routes/de')->willReturn(true);

        $defaultRouteNode = $this->prophesize(NodeInterface::class);
        $defaultRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de');
        $defaultRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(false);
        $defaultChildRouteNode = $this->prophesize(NodeInterface::class);
        $defaultChildRouteNode->getNodes()->willReturn([]);
        $defaultChildRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de/child');
        $defaultChildRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(false);
        $defaultChildHistoryRouteNode = $this->prophesize(NodeInterface::class);
        $defaultChildHistoryRouteNode->getNodes()->willReturn([]);
        $defaultChildHistoryRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de/child');
        $defaultChildHistoryRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(true);
        $defaultRouteNode->getNodes()->willReturn([$defaultChildRouteNode->reveal(), $defaultChildHistoryRouteNode->reveal()]);
        $this->defaultSession->getNode('/cmf/sulu_io/routes/de')->willReturn($defaultRouteNode->reveal());

        $liveRouteNode = $this->prophesize(NodeInterface::class);
        $liveRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de');
        $liveRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(false);
        $liveChildRouteNode = $this->prophesize(NodeInterface::class);
        $liveChildRouteNode->getNodes()->willReturn([]);
        $liveChildRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de/child');
        $liveChildRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(false);
        $liveChildHistoryRouteNode = $this->prophesize(NodeInterface::class);
        $liveChildHistoryRouteNode->getNodes()->willReturn([]);
        $liveChildHistoryRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de/child');
        $liveChildHistoryRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(true);
        $liveRouteNode->getNodes()->willReturn([$liveChildRouteNode->reveal(), $liveChildHistoryRouteNode->reveal()]);
        $this->liveSession->getNode('/cmf/sulu_io/routes/de')->willReturn($liveRouteNode->reveal());

        $defaultChildRouteNode->remove()->shouldNotBeCalled();
        $defaultChildHistoryRouteNode->remove()->shouldBeCalled();
        $liveChildRouteNode->remove()->shouldNotBeCalled();
        $liveChildHistoryRouteNode->remove()->shouldBeCalled();

        $this->defaultSession->save()->shouldBeCalled();
        $this->liveSession->save()->shouldBeCalled();

        $executeMethod = new \ReflectionMethod(CleanupHistoryCommand::class, 'execute');
        $executeMethod->setAccessible(true);

        $executeMethod->invoke($this->cleanupHistoryCommand, $this->input->reveal(), $this->output->reveal());
    }

    public function testExecuteDryRun()
    {
        $this->input->getArgument('webspaceKey')->willReturn('sulu_io');
        $this->input->getArgument('locale')->willReturn('de');
        $this->input->getOption('base-path')->willReturn(null);
        $this->input->getOption('dry-run')->willReturn(true);
        $this->sessionManager->getRoutePath('sulu_io', 'de')->willReturn('/cmf/sulu_io/routes/de');
        $this->defaultSession->nodeExists('/cmf/sulu_io/routes/de')->willReturn(true);
        $this->liveSession->nodeExists('/cmf/sulu_io/routes/de')->willReturn(true);

        $defaultRouteNode = $this->prophesize(NodeInterface::class);
        $defaultRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de');
        $defaultRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(false);
        $defaultChildRouteNode = $this->prophesize(NodeInterface::class);
        $defaultChildRouteNode->getNodes()->willReturn([]);
        $defaultChildRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de/child');
        $defaultChildRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(false);
        $defaultChildHistoryRouteNode = $this->prophesize(NodeInterface::class);
        $defaultChildHistoryRouteNode->getNodes()->willReturn([]);
        $defaultChildHistoryRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de/child');
        $defaultChildHistoryRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(true);
        $defaultRouteNode->getNodes()->willReturn([$defaultChildRouteNode->reveal(), $defaultChildHistoryRouteNode->reveal()]);
        $this->defaultSession->getNode('/cmf/sulu_io/routes/de')->willReturn($defaultRouteNode->reveal());

        $liveRouteNode = $this->prophesize(NodeInterface::class);
        $liveRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de');
        $liveRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(false);
        $liveChildRouteNode = $this->prophesize(NodeInterface::class);
        $liveChildRouteNode->getNodes()->willReturn([]);
        $liveChildRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de/child');
        $liveChildRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(false);
        $liveChildHistoryRouteNode = $this->prophesize(NodeInterface::class);
        $liveChildHistoryRouteNode->getNodes()->willReturn([]);
        $liveChildHistoryRouteNode->getPath()->willReturn('/cmf/sulu_io/routes/de/child');
        $liveChildHistoryRouteNode->getPropertyValueWithDefault('sulu:history', false)->willReturn(true);
        $liveRouteNode->getNodes()->willReturn([$liveChildRouteNode->reveal(), $liveChildHistoryRouteNode->reveal()]);
        $this->liveSession->getNode('/cmf/sulu_io/routes/de')->willReturn($liveRouteNode->reveal());

        $defaultChildRouteNode->remove()->shouldNotBeCalled();
        $defaultChildHistoryRouteNode->remove()->shouldNotBeCalled();
        $liveChildRouteNode->remove()->shouldNotBeCalled();
        $liveChildHistoryRouteNode->remove()->shouldNotBeCalled();

        $this->defaultSession->save()->shouldNotBeCalled();
        $this->liveSession->save()->shouldNotBeCalled();

        $executeMethod = new \ReflectionMethod(CleanupHistoryCommand::class, 'execute');
        $executeMethod->setAccessible(true);

        $executeMethod->invoke($this->cleanupHistoryCommand, $this->input->reveal(), $this->output->reveal());
    }
}
