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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\ArticleBundle\Import\ArticleImportInterface;
use Sulu\Bundle\ArticleBundle\Import\ImportResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ArticleImportCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'sulu:article:import';

    /**
     * @var ArticleImportInterface
     */
    private $articleImporter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ArticleImportInterface $articleImporter, ?LoggerInterface $logger = null)
    {
        parent::__construct(static::$defaultName);

        $this->articleImporter = $articleImporter;
        $this->logger = $logger ?: new NullLogger();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'export.xliff')
            ->addArgument('locale', InputArgument::REQUIRED)
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, '', '1.2.xliff')
            ->addOption('uuid', 'u', InputOption::VALUE_REQUIRED)
            ->addOption('overrideSettings', 'o', InputOption::VALUE_NONE, 'Override Settings-Tab')
            ->setDescription('Import article translations from xliff file into a specific language.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getArgument('file');
        if (0 === !\strpos($filePath, '/')) {
            $filePath = \getcwd() . '/' . $filePath;
        }
        $locale = $input->getArgument('locale');
        $format = $input->getOption('format');
        $overrideSettings = $input->getOption('overrideSettings');

        $output->writeln([
            '<info>Language Import</info>',
            '<info>===============</info>',
            '',
            '<info>Options</info>',
            'Locale: ' . $locale,
            'Format: ' . $format,
            'Override Setting: ' . ($overrideSettings ? 'YES' : 'NO'),
            '---------------',
            '',
        ]);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Continue with this options? Be careful! (y/n)</question> ', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<error>Abort!</error>');

            return -1;
        }

        $output->writeln('<info>Continue!</info>');

        $import = $this->articleImporter->import(
            $locale,
            $filePath,
            $output,
            $format,
            $overrideSettings
        );

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(\sprintf('<info>Imported %s/%s</info>', $import->getSuccesses(), $import->getCount()));
        }

        $this->printExceptions($import, $output);

        return $import->getFails();
    }

    protected function printExceptions(ImportResult $import, $output = null)
    {
        if (null === $output) {
            $output = new NullOutput();
        }

        $output->writeln([
            '',
            '',
            '<info>Import Result</info>',
            '<info>===============</info>',
            '<info>' . $import->getSuccesses() . ' Documents imported.</info>',
            '<comment>' . \count($import->getFailed()) . ' Documents ignored.</comment>',
        ]);

        if (!isset($import->getExceptionStore()['ignore'])) {
            return;
        }

        // If more than 20 exceptions write only into log.
        if (\count($import->getExceptionStore()['ignore']) > 20) {
            foreach ($import->getExceptionStore()['ignore'] as $msg) {
                $this->logger->info($msg);
            }

            return;
        }

        foreach ($import->getExceptionStore()['ignore'] as $msg) {
            $output->writeln('<comment>' . $msg . '</comment>');
            $this->logger->info($msg);
        }
    }
}
