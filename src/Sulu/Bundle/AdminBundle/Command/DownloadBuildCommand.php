<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

@trigger_deprecation(
    'sulu/sulu',
    '2.0',
    'The "%s" class is deprecated, use "%s" instead.',
    DownloadBuildCommand::class,
    UpdateBuildCommand::class
);

/**
 * @deprecated use the "UpdateBuildCommand" class instead
 */
#[AsCommand(name: 'sulu:admin:download-build', description: 'Downloads the current admin application build from the sulu/skeleton repository.')]
class DownloadBuildCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        if (null === $application) {
            $ui = new SymfonyStyle($input, $output);
            $ui->error('Could not find application');

            return 1;
        }
        $command = $application->find('sulu:admin:update-build');

        return $command->run($input, $output);
    }
}
