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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Component\Snippet\Import\SnippetImportInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'sulu:snippet:import', description: 'Import snippet translations from xliff file into a specific language.')]
class SnippetImportCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(private SnippetImportInterface $snippetImporter, ?LoggerInterface $logger = null)
    {
        parent::__construct();
        $this->logger = $logger ?: new NullLogger();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'test.xliff')
            ->addArgument('locale', InputArgument::REQUIRED)
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, '', '1.2.xliff');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getArgument('file');

        if (0 === !\strpos($filePath, '/')) {
            $filePath = \getcwd() . '/' . $filePath;
        }

        $locale = $input->getArgument('locale');
        $format = $input->getOption('format');

        $output->writeln([
            '<info>Language Snippet Import</info>',
            '<info>===============</info>',
            '',
            '<info>Options</info>',
            'Locale: ' . $locale,
            'Format: ' . $format,
            '---------------',
            '',
        ]);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Continue with this options? Be careful! (y/n)</question> ', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<error>Abort!</error>');

            return 0;
        }

        $output->writeln('<info>Continue!</info>');

        $import = $this->snippetImporter->import(
            $locale,
            $filePath,
            $output,
            $format
        );

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(\sprintf('<info>Imported %s/%s</info>', $import->successes, $import->count));
        }

        $this->printExceptions($import, $output);

        return (int) $import->fails;
    }

    /**
     * Print the completion message after import is done.
     *
     * @param \stdClass $import
     * @param OutputInterface $output
     */
    protected function printExceptions($import, $output = null)
    {
        if (null === $output) {
            $output = new NullOutput();
        }

        $output->writeln([
            '',
            '',
            '<info>Import Result</info>',
            '<info>===============</info>',
            '<info>' . $import->successes . ' Documents imported.</info>',
            '<comment>' . \count($import->failed) . ' Documents ignored.</comment>',
        ]);

        if (!isset($import->exceptionStore['ignore'])) {
            return;
        }

        // If more than 20 exceptions write only into log.
        if (\count($import->exceptionStore['ignore']) > 20) {
            foreach ($import->exceptionStore['ignore'] as $msg) {
                $this->logger->info($msg);
            }

            return;
        }

        foreach ($import->exceptionStore['ignore'] as $msg) {
            $output->writeln('<comment>' . $msg . '</comment>');
            $this->logger->info($msg);
        }
    }
}
