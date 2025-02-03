<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Command;

use Sulu\Component\Content\Export\WebspaceExportInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'sulu:webspaces:export', description: 'Export webspace page translations from given language into xliff file for translating into a new language.')]
class WebspaceExportCommand extends Command
{
    public function __construct(private WebspaceExportInterface $webspaceExporter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('target', InputArgument::REQUIRED, 'export.xliff')
            ->addArgument('webspace', InputArgument::REQUIRED)
            ->addArgument('locale', InputArgument::REQUIRED)
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, '', '1.2.xliff')
            ->addOption('nodes', 'm', InputOption::VALUE_REQUIRED)
            ->addOption('ignored-nodes', 'i', InputOption::VALUE_REQUIRED)
            ->addOption('uuid', 'u', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $webspaceKey = $input->getArgument('webspace');
        $target = $input->getArgument('target');
        if (false === \strpos($target, '/')) {
            $target = \getcwd() . '/' . $target;
        }
        $locale = $input->getArgument('locale');
        $format = $input->getOption('format');
        $uuid = $input->getOption('uuid');
        $nodes = $input->getOption('nodes');

        $output->writeln([
            '<info>Language Export</info>',
            '<info>===============</info>',
            '',
            '<info>Options</info>',
            'Webspace: ' . $webspaceKey,
            'Target: ' . $target,
            'Locale: ' . $locale,
            'Format: ' . $format,
            'UUID: ' . $uuid,
            '---------------',
            '',
        ]);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Continue with this options?(y/n)</question> ', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<error>Abort!</error>');

            return 0;
        }

        $output->writeln('<info>Continue!</info>');

        $ignoredNodes = $input->getOption('ignored-nodes');
        if ($nodes) {
            $nodes = \explode(',', $nodes);
        }
        if ($ignoredNodes) {
            $ignoredNodes = \explode(',', $ignoredNodes);
        }

        $file = $this->webspaceExporter->export(
            $webspaceKey,
            $locale,
            $output,
            $format,
            $uuid,
            $nodes,
            $ignoredNodes
        );

        \file_put_contents($target, $file);

        return 0;
    }
}
