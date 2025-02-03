<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Command;

use Sulu\Component\Snippet\Export\SnippetExportInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'sulu:snippet:export', description: 'Export snippet translations from given language into xliff file for translating into a new language.')]
class SnippetExportCommand extends Command
{
    public function __construct(private SnippetExportInterface $snippetExporter)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument('target', InputArgument::REQUIRED, 'Target for export (e.g. export_de.xliff)');
        $this->addArgument('locale', InputArgument::REQUIRED, 'Locale to export (e.g. de, en)');
        $this->addOption('format', 'f', InputOption::VALUE_REQUIRED, '', '1.2.xliff');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getArgument('target');
        if (false === \strpos($target, '/')) {
            $target = \getcwd() . '/' . $target;
        }
        $locale = $input->getArgument('locale');
        $format = $input->getOption('format');

        $output->writeln([
            '<info>Language Export</info>',
            '<info>===============</info>',
            '',
            '<info>Options</info>',
            'Target: ' . $target,
            'Locale: ' . $locale,
            'Format: ' . $format,
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

        $file = $this->snippetExporter->export(
            $locale,
            $output,
            $format
        );

        \file_put_contents($target, $file);

        return 0;
    }
}
