<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use Sulu\Component\Content\Export\WebspaceInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export a webspace in a specific format.
 */
class WebspaceExportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:webspaces:export')
            ->addArgument('target', InputArgument::REQUIRED, 'test.xliff')
            ->addOption('webspace', 'w', InputOption::VALUE_REQUIRED)
            ->addOption('locale', 'l', InputOption::VALUE_REQUIRED)
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, '', '1.2.xliff')
            ->addOption('nodes', 'm', InputOption::VALUE_REQUIRED)
            ->addOption('ignored-nodes', 'i', InputOption::VALUE_REQUIRED)
            ->addOption('uuid', 'u', InputOption::VALUE_REQUIRED)
            ->setDescription('Export webspace');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $webspaceKey = $input->getOption('webspace');
        $target = $input->getArgument('target');
        if (!strpos($target, '/') === 0) {
            $target = getcwd() . '/' . $target;
        }
        $locale = $input->getOption('locale');
        $format = $input->getOption('format');
        $uuid = $input->getOption('uuid');
        $nodes = $input->getOption('nodes');
        $ignoredNodes = $input->getOption('ignored-nodes');
        if ($nodes) {
            $nodes = explode(',', $nodes);
        }
        if ($ignoredNodes) {
            $ignoredNodes = explode(',', $ignoredNodes);
        }

        /** @var WebspaceInterface $webspaceExporter */
        $webspaceExporter = $this->getContainer()->get('sulu_content.export.webspace');

        $file = $webspaceExporter->export(
            $webspaceKey,
            $locale,
            $format,
            $uuid,
            $nodes,
            $ignoredNodes
        );

        file_put_contents($target, $file);

        return 0;
    }
}
