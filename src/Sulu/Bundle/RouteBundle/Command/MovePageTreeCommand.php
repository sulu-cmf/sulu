<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Command;

use Sulu\Bundle\RouteBundle\PageTree\PageTreeMoverInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Move documents from given parent-page to another.
 */
#[AsCommand(name: 'sulu:route:page-tree:move')]
class MovePageTreeCommand extends Command
{
    public function __construct(
        private PageTreeMoverInterface $pageTreeMover,
        private ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool,
        private DocumentManagerInterface $documentManager
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->addArgument('source-segment', InputArgument::REQUIRED)
            ->addArgument('destination-segment', InputArgument::REQUIRED)
            ->addArgument('webspace-key', InputArgument::REQUIRED)
            ->addArgument('locale', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $input->getArgument('source-segment');
        $destination = $input->getArgument('destination-segment');
        $webspaceKey = $input->getArgument('webspace-key');
        $locale = $input->getArgument('locale');

        $strategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);

        $destinationUuid = $strategy->loadByResourceLocator($destination, $webspaceKey, $locale);
        $document = $this->documentManager->find($destinationUuid, $locale);

        $this->pageTreeMover->move($source, $document);

        $this->documentManager->flush();

        return 0;
    }
}
