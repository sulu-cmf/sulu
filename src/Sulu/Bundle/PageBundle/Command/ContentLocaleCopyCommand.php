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

use Jackalope\Query\QueryManager;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sulu:content:locale-copy', description: 'Copy content nodes from one locale to another')]
class ContentLocaleCopyCommand extends Command
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
        private ContentMapperInterface $contentMapper,
        private SessionInterface $session,
        private string $languageNamespace,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setHelp(
            <<<'EOT'
            The <info>%command.name%</info> command copies the internationalized properties matching <info>srcLocale</info>
to <info>destLocale</info> on all nodes from a specific webspace.

    %command.full_name% sulu_io de en --dry-run

You can overwrite existing values using the <info>overwrite</info> option:

    %command.full_name% sulu_io de en --overwrite --dry-run

Remove the <info>dry-run</info> option to actually persist the changes.
EOT
        );
        $this->addArgument('webspaceKey', InputArgument::REQUIRED, 'Copy locales in nodes belonging to this webspace');
        $this->addArgument('srcLocale', InputArgument::REQUIRED, 'Locale to copy from (e.g. de)');
        $this->addArgument('destLocale', InputArgument::REQUIRED, 'Locale to copy to (e.g. en)');
        $this->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite existing locales');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not persist changes');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $webspaceKey = $input->getArgument('webspaceKey');
        $srcLocale = $input->getArgument('srcLocale');
        $destLocale = $input->getArgument('destLocale');
        $overwrite = $input->getOption('overwrite');
        $dryRun = $input->getOption('dry-run');

        $this->queryManager = $this->session->getWorkspace()->getQueryManager();

        $this->output = $output;

        $this->copyNodes($webspaceKey, $srcLocale, $destLocale, $overwrite);

        if (false === $dryRun) {
            $this->output->writeln('<info>Saving ...</info>');
            $this->session->save();
            $this->output->writeln('<info>Done</info>');
        } else {
            $this->output->writeln('<info>Dry run complete</info>');
        }

        return 0;
    }

    private function copyNodes($webspaceKey, $srcLocale, $destLocale, $overwrite)
    {
        $node = $this->contentMapper->loadStartPage($webspaceKey, $srcLocale);

        // copy start node
        $this->copyNodeRecursive($node, $webspaceKey, $srcLocale, $destLocale, $overwrite);
    }

    private function copyNodeRecursive(StructureInterface $structure, $webspaceKey, $srcLocale, $destLocale, $overwrite)
    {
        $this->copyNode($webspaceKey, $srcLocale, $destLocale, $structure, $overwrite);

        if (!$structure->getHasChildren()) {
            return;
        }

        foreach ($this->contentMapper->loadByParent($structure->getUuid(), $webspaceKey, $srcLocale) as $child) {
            $this->copyNodeRecursive($child, $webspaceKey, $srcLocale, $destLocale, $overwrite);
        }
    }

    private function copyNode($webspaceKey, $srcLocale, $destLocale, StructureInterface $structure, $overwrite = false)
    {
        if (!$overwrite) {
            $destStructure = $this->contentMapper->load($structure->getUuid(), $webspaceKey, $destLocale, true);

            if (!($destStructure->getType() && 'ghost' === $destStructure->getType()->getName())) {
                $this->output->writeln(
                    '<info>Processing aborted: </info>' .
                    $structure->getPath() . ' <comment>(use overwrite option to force)</comment>'
                );

                return;
            }
        }

        if ($structure->getType() && 'ghost' === $structure->getType()->getName()) {
            $this->output->writeln(
                '<info>Processing aborted: </info>' .
                $structure->getPath() . ' <comment>(source language does not exist)</comment>'
            );

            return;
        }

        try {
            $this->contentMapper->copyLanguage(
                $structure->getUuid(),
                $structure->getChanger(),
                $webspaceKey,
                $srcLocale,
                $destLocale
            );

            $this->output->writeln('<info>Processing: </info>' . $structure->getPath());
        } catch (ResourceLocatorAlreadyExistsException $e) {
            $this->output->writeln(
                \sprintf(
                    '<info>Processing aborted: </info> %s <comment>Resource Locator "%s" already exists',
                    $structure->getPath(),
                    $structure->getResourceLocator()
                )
            );
        }
    }
}
