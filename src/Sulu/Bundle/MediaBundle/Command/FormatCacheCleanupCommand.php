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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(name: 'sulu:media:format:cache:cleanup', description: 'Remove media formats which medias not longer exist in the database')]
class FormatCacheCleanupCommand extends Command
{
    public function __construct(
        private EntityRepository $mediaRepository,
        private Filesystem $filesystem,
        private string $localFormatCachePath
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do nothing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);

        $finder = new Finder();
        $finder->in(\realpath($this->localFormatCachePath));
        $files = $finder->files();

        $progressBar = $ui->createProgressBar(\count($files));
        $removedIds = [];
        $removedCount = 0;
        $existsIds = [];

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $progressBar->setMessage($file->getPathname());
            $mediaId = \explode('-', $file->getBasename())[0];

            if (!\is_numeric($mediaId)) {
                $progressBar->advance();
                continue;
            }

            if (isset($removedIds[$mediaId]) || (!isset($existsIds[$mediaId]) && !$this->mediaExists($mediaId))) {
                $removedIds[$mediaId] = $mediaId;
                ++$removedCount;

                if ($ui->isVerbose()) {
                    $output->writeln('');
                    $output->writeln($file->getPathname());
                }

                if (!$input->getOption('dry-run')) {
                    $this->filesystem->remove($file->getPathname());
                }
            } else {
                $existsIds[$mediaId] = $mediaId;
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $ui->writeln('');
        $ui->writeln('');

        $message = 'Removed Media: ' . \count($removedIds) . ' Removed Files: ' . $removedCount;

        if ($input->getOption('dry-run')) {
            $ui->note('Dry Run: ' . $message);
        } else {
            $ui->success($message);
        }

        return 0;
    }

    private function mediaExists($mediaId)
    {
        try {
            $mediaId = $this->mediaRepository->createQueryBuilder('media')
                ->select('media.id')
                ->where('media.id = :id')
                ->setParameter('id', $mediaId)
                ->getQuery()->getSingleScalarResult();

            return (bool) $mediaId;
        } catch (NoResultException $e) {
            return false;
        }
    }
}
