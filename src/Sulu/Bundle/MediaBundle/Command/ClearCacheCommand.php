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

use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheClearerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sulu:media:format:cache:clear', description: 'Clear all or the given Sulu media format cache')]
class ClearCacheCommand extends Command
{
    public function __construct(private FormatCacheClearerInterface $cacheClearer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('cache', InputArgument::OPTIONAL, 'Optional alias to clear the specific cache')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cache = $input->getArgument('cache');

        $output->writeln('Clearing the Sulu media format cache.');
        $this->cacheClearer->clear($cache);

        return 0;
    }
}
