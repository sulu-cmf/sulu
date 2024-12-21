<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'sulu:media:init', description: 'Init Sulu Media Bundle')]
class InitCommand extends Command
{
    public function __construct(
        private Filesystem $filesystem,
        private string $formatCacheDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Create Media Cache dir in ' . $this->formatCacheDir);

        if (!\is_dir($this->formatCacheDir)) {
            $this->filesystem->mkdir($this->formatCacheDir);
        } else {
            $output->writeLn('Directory "' . $this->formatCacheDir . '"" already exists');
        }

        return 0;
    }
}
