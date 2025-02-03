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

use Jackalope\Query\QueryManager;
use PHPCR\SessionInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sulu:snippet:locale-copy', description: 'Copy snippet nodes from one locale to another')]
class SnippetLocaleCopyCommand extends Command
{
    /**
     * @var QueryManager
     */
    private $queryManager;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(
        private SnippetRepository $snippetRepository,
        private ContentMapperInterface $contentMapper,
        private SessionInterface $session,
        private DocumentManagerInterface $documentManager,
        /**
         * The namespace for languages.
         */
        private string $languageNamespace
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setHelp(
            <<<'EOT'
            The <info>%command.name%</info> command copies the internationalized properties matching <info>srcLocale</info>
to <info>destLocale</info> on all snippet nodes from a specific type.

    %command.full_name% de en --dry-run

You can overwrite existing values using the <info>overwrite</info> option:

    %command.full_name% de en --overwrite --dry-run

Remove the <info>dry-run</info> option to actually persist the changes.
EOT
        );
        $this->addArgument('srcLocale', InputArgument::REQUIRED, 'Locale to copy from (e.g. de)');
        $this->addArgument('destLocale', InputArgument::REQUIRED, 'Locale to copy to (e.g. en)');
        $this->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite existing locales');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not persist changes');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $srcLocale = $input->getArgument('srcLocale');
        $destLocale = $input->getArgument('destLocale');
        $overwrite = $input->getOption('overwrite');
        $dryRun = $input->getOption('dry-run');

        $this->queryManager = $this->session->getWorkspace()->getQueryManager();

        $this->output = $output;

        $this->copyDocuments($srcLocale, $destLocale, $overwrite);
        $this->documentManager->flush();

        if (false === $dryRun) {
            $this->output->writeln('<info>Saving ...</info>');
            $this->session->save();
            $this->output->writeln('<info>Done</info>');
        } else {
            $this->output->writeln('<info>Dry run complete</info>');
        }

        return 0;
    }

    private function copyDocuments($srcLocale, $destLocale, $overwrite)
    {
        foreach ($this->snippetRepository->getSnippets($srcLocale) as $document) {
            $this->copyDocument($srcLocale, $destLocale, $document, $overwrite);
        }
    }

    private function copyDocument($srcLocale, $destLocale, SnippetDocument $document, $overwrite = false)
    {
        if (!$overwrite) {
            $destStructure = $this->contentMapper->load($document->getUuid(), null, $destLocale, true);

            if (!($destStructure->getType() && 'ghost' === $destStructure->getType()->getName())) {
                $this->output->writeln(
                    '<info>Processing aborted: </info>' .
                    $document->getNodeName() . ' <comment>(use overwrite option to force)</comment>'
                );

                return;
            }
        }

        $this->contentMapper->copyLanguage(
            $document->getUuid(),
            $document->getChanger(),
            null,
            $srcLocale,
            $destLocale,
            Structure::TYPE_SNIPPET
        );
        $destDocument = $this->documentManager->find($document->getUuid(), $destLocale);
        $this->documentManager->publish($destDocument, $destLocale);

        $this->output->writeln('<info>Processing: </info>' . $document->getNodeName());
    }
}
