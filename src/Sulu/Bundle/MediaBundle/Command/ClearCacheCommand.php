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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCacheCommand extends Command
{
    protected static $defaultName = 'sulu:media:format:cache:clear';

    /**
     * @var FormatCacheClearerInterface
     */
    private $cacheClearer;

    public function __construct(FormatCacheClearerInterface $cacheClearer)
    {
        parent::__construct();

        $this->cacheClearer = $cacheClearer;
    }

    protected function configure()
    {
        $this->setDescription('Clear all or the given Sulu media format cache')
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
