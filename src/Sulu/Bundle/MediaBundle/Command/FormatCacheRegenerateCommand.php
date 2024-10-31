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

use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FormatCacheRegenerateCommand extends Command
{
    protected static $defaultName = 'sulu:media:regenerate-formats';

    public function __construct(
        private Filesystem $filesystem,
        private FormatManagerInterface $formatManager,
        private string $localFormatCachePath
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Loops over sulu image cache, and regenerates the existing images');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);

        $imageCachePath = \realpath($this->localFormatCachePath);
        if (false === $imageCachePath) {
            throw new FileNotFoundException(\sprintf('Unable to resolve path "%s".', $this->localFormatCachePath));
        }

        $finder = new Finder();
        $finder->in($imageCachePath);
        $files = $finder->files();

        if (!\count($files)) {
            $ui->writeln(\sprintf('No images to regenerate found in "%s".', $this->localFormatCachePath));

            return 0;
        }

        $progressBar = $ui->createProgressBar(\count($files));

        $ui->writeln('Starting to regenerate: ' . \count($files) . ' images');
        $ui->writeln('');

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $fileInformation = $this->getFileInformationArrayFromPath($file->getRelativePathname());

            $this->formatManager->returnImage(
                $fileInformation['id'],
                $fileInformation['formatKey'],
                $fileInformation['fileName']
            );

            $progressBar->advance();
        }

        $progressBar->finish();

        $ui->writeln('');
        $ui->writeln('');

        $ui->success(\sprintf('Finished regenerating of "%s" images.', \count($files)));

        return 0;
    }

    private function getFileInformationArrayFromPath($path): array
    {
        $pathParts = \explode(\DIRECTORY_SEPARATOR, $path);
        $formatKey = \reset($pathParts);
        $filenameParts = \explode('-', \end($pathParts), 2);
        $id = (int) $filenameParts[0];
        $fileName = $filenameParts[1];

        return [
            'id' => $id,
            'formatKey' => $formatKey,
            'fileName' => $fileName,
        ];
    }
}
