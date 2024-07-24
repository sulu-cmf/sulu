<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Command;

use Sulu\Bundle\ArticleBundle\Export\ArticleExportInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ArticleExportCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'sulu:article:export';

    /**
     * @var ArticleExportInterface
     */
    private $articleExporter;

    public function __construct(ArticleExportInterface $articleExporter)
    {
        parent::__construct(static::$defaultName);

        $this->articleExporter = $articleExporter;
    }

    protected function configure(): void
    {
        $this->addArgument('target', InputArgument::REQUIRED, 'export.xliff')
            ->addArgument('locale', InputArgument::REQUIRED)
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, '', '1.2.xliff')
            ->setDescription('Export article translations from given language into xliff file for translating into a new language.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getArgument('target');
        if (0 === !\strpos($target, '/')) {
            $target = \getcwd() . '/' . $target;
        }
        $locale = $input->getArgument('locale');
        $format = $input->getOption('format');

        $output->writeln([
            '<info>Article Language Export</info>',
            '<info>=======================</info>',
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

        $file = $this->articleExporter->export($locale, $format, $output);

        \file_put_contents($target, $file);

        return 0;
    }
}
