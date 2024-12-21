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

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sulu:content:cleanup-history', description: 'Cleanup resource-locator history')]
class CleanupHistoryCommand extends Command
{
    public function __construct(
        private SessionManagerInterface $sessionManager,
        private SessionInterface $defaultSession,
        private SessionInterface $liveSession
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setHelp(
            <<<'EOT'
The <info>%command.name%</info> command cleanup the history of the resource-locator of a <info>locale</info>.

    %command.full_name% sulu_io de --dry-run

If you want to cleanup a special directory you can past the base-path:

    %command.full_name% sulu_io de -p /team --dry-run
EOT
        );
        $this->addArgument('webspaceKey', InputArgument::REQUIRED, 'Resource-locators belonging to this webspace');
        $this->addArgument('locale', InputArgument::REQUIRED, 'Locale to search (e.g. de)');
        $this->addOption('base-path', 'p', InputOption::VALUE_OPTIONAL, 'base path to search for history urls');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not persist changes');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $webspaceKey = $input->getArgument('webspaceKey');
        $locale = $input->getArgument('locale');
        $basePath = $input->getOption('base-path');
        $dryRun = $input->getOption('dry-run');

        $path = $this->sessionManager->getRoutePath($webspaceKey, $locale);
        $relativePath = (null !== $basePath ? '/' . \ltrim($basePath, '/') : '/');
        $fullPath = \rtrim($path . $relativePath, '/');

        $this->cleanSession($output, $this->defaultSession, $fullPath, $dryRun);
        $this->cleanSession($output, $this->liveSession, $fullPath, $dryRun);

        if (false === $dryRun) {
            $this->defaultSession->save();
            $this->liveSession->save();
            $output->writeln('<info>Save complete</info>');
        } else {
            $output->writeln('<info>Dry run complete</info>');
        }

        return 0;
    }

    private function cleanSession(OutputInterface $output, SessionInterface $session, $path, $dryRun)
    {
        $sessionName = $session->getWorkspace()->getName();
        $output->writeln(\sprintf('<info>Session</info> %s', $sessionName));

        if (!$session->nodeExists($path)) {
            $output->write(
                \sprintf(
                    '<error>Resource-Locator "%s" not found in session "%s"</error>',
                    $path,
                    $sessionName
                )
            );

            return;
        }

        $node = $session->getNode($path);

        $this->cleanup($output, $node, $path, $dryRun);

        $output->writeln('');
    }

    /**
     * Cleanup specific node and his children.
     *
     * @param string $rootPath
     * @param bool $dryRun
     */
    private function cleanup(OutputInterface $output, NodeInterface $node, $rootPath, $dryRun)
    {
        foreach ($node->getNodes() as $childNode) {
            $this->cleanup($output, $childNode, $rootPath, $dryRun);
        }

        $path = \ltrim(\str_replace($rootPath, '', $node->getPath()), '/');

        if (!$node->getPropertyValueWithDefault('sulu:history', false)) {
            $output->writeln(
                '<info>Processing aborted: </info>/' .
                $path . ' <comment>(no history url)</comment>'
            );

            return;
        }

        if (false === $dryRun) {
            $node->remove();
        }
        $output->writeln('<info>Processing: </info>/' . $path);
    }
}
