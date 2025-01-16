<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UpdateBuildCommand extends Command
{
    public const EXIT_CODE_ABORTED_MANUAL_BUILD = 1;
    public const EXIT_CODE_COULD_NOT_INSTALL_NPM_PACKAGES = 2;
    public const EXIT_CODE_COULD_NOT_BUILD_ADMIN_ASSETS = 3;

    protected static $defaultName = 'sulu:admin:update-build';

    public const ASSETS_DIR = \DIRECTORY_SEPARATOR . 'assets' . \DIRECTORY_SEPARATOR . 'admin' . \DIRECTORY_SEPARATOR;

    public const BUILD_DIR = \DIRECTORY_SEPARATOR . 'public' . \DIRECTORY_SEPARATOR . 'build' . \DIRECTORY_SEPARATOR . 'admin';

    public const REPOSITORY_NAME = 'skeleton';

    public const VERSION_REGEX = '/^\d+\.\d+\.\d+(-(alpha|beta|RC)\d+)?$/';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $projectDir,
        private string $suluVersion,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription(
            'Updates the administration application JavaScript build by downloading the official build '
            . 'from the sulu/skeleton repository or building the assets via npm.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $needManualBuild = false;

        $suluVersion = $this->suluVersion;
        $isTaggedVersion = \preg_match(static::VERSION_REGEX, $this->suluVersion);

        if (!$isTaggedVersion) {
            $ui->warning(
                'This command can only download the official build for tagged versions of the "sulu/sulu" '
                . 'package, not for branches etc. Your version is "' . $this->suluVersion . '".' . \PHP_EOL
                . 'When not using a tagged version, you need to create the JavaScript build by yourself.' . \PHP_EOL
                . 'Please make sure that the content of your "assets/admin" folder is compatible with '
                . 'your "sulu/sulu" package.'
            );

            if ('y' !== \strtolower(
                $ui->ask('Do you want to update your "assets/admin" folder to match the "sulu/skeleton"?', 'y')
            )) {
                return 0;
            }

            // if `2.3@dev` is set it will need to convert `2.x-dev` into `2.x`
            // if `2.2.*@dev` is set it will need to convert `2.2.x-dev` into `2.2`
            $suluVersion = \str_replace('-dev', '', $suluVersion);
            if (3 !== \strlen($suluVersion)) {
                $suluVersion = \str_replace('.x', '', $suluVersion);
            }

            if (0 === \strpos($suluVersion, 'dev-')) {
                $suluVersion = $ui->ask(
                    \sprintf('Cannot detect "sulu/skeleton" branch for version "%s". Which "sulu/skeleton" branch do you want to use to update your "assets/admin" folder?', $suluVersion),
                    '2.x'
                );
            }

            $needManualBuild = true;
        }

        $remoteRepository = 'https://raw.githubusercontent.com/sulu/skeleton/' . $suluVersion;
        $remoteArchive = 'https://codeload.github.com/sulu/skeleton/zip/' . $suluVersion;

        $output->writeln('<info>Checking for changed files...</info>');

        $assetFiles = [
            'app.js',
            'index.js',
            'package.json',
            'webpack.config.js',
            'babel.config.json',
            '.browserslistrc',
            '.npmrc',
            'postcss.config.js',
        ];

        $renamedFiles = [
            '.babelrc' => 'babel.config.json',
        ];

        $deletedFiles = [
            '.babelrc',
        ];

        $appFiles = ['app.js']; // files which are expected to be changed but requires then a manual build

        foreach ($renamedFiles as $oldFile => $newFile) {
            if (
                $filesystem->exists($this->projectDir . static::ASSETS_DIR . $oldFile)
                && !$filesystem->exists($this->projectDir . static::ASSETS_DIR . $newFile)
            ) {
                if ('y' === \strtolower(
                    $ui->ask(\sprintf('The "%s" should be renamed to "%s" should wo do this now?', $oldFile, $newFile), 'y')
                )) {
                    $filesystem->rename($this->projectDir . static::ASSETS_DIR . $oldFile, $this->projectDir . static::ASSETS_DIR . $newFile);
                }
            }
        }

        foreach ($deletedFiles as $deletedFile) {
            if ($filesystem->exists($this->projectDir . static::ASSETS_DIR . $deletedFile)) {
                if ('y' === \strtolower(
                    $ui->ask(\sprintf('The "%s" should be deleted should wo do this now?', $deletedFile), 'y')
                )) {
                    $filesystem->remove($this->projectDir . static::ASSETS_DIR . $deletedFile);
                }
            }
        }

        foreach ($assetFiles as $file) {
            $filePath = static::ASSETS_DIR . $file;
            $ui->section('Checking: ' . $filePath);
            $localContent = $this->getLocalFile($filePath);
            $remoteContent = $this->getRemoteFile($remoteRepository, $filePath);

            if ($this->hash($localContent) !== $this->hash($remoteContent)) {
                $ui->writeln('Differences between local and remote version of the file found:');
                $ui->writeln('');

                $ui->table(['Old Version', 'New Version'], [
                    [$localContent, $remoteContent],
                ]);

                $defaultValueUseLocaleFile = 'y';
                if ($localContent && \in_array($file, $appFiles)) {
                    $defaultValueUseLocaleFile = 'n';
                }

                if ('y' !== \strtolower(
                    $ui->ask(\sprintf('Do you want to overwrite your local version of "%s"?', $file), $defaultValueUseLocaleFile)
                )) {
                    $needManualBuild = true;

                    if ($localContent && \in_array($file, ['package.json'])) {
                        $mergedJson = $this->mergeJsonStrings($localContent, $remoteContent);

                        $ui->writeln(\sprintf('Merged Version of "%s":', $file));
                        $ui->writeln($mergedJson);

                        if ('y' === \strtolower(
                            $ui->ask(
                                \sprintf('Merge "%s" together like above?', $file),
                                'y'
                            )
                        )
                        ) {
                            $ui->writeln(\sprintf('Write new "%s" version.', $file));
                            $this->writeFile($filePath, $mergedJson . "\n");
                        }
                    }

                    continue;
                }

                $ui->writeln(\sprintf('Overwriting "%s" with remote version.', $file));
                $this->writeFile($filePath, $remoteContent);
            }
        }

        $ui->section('Cleanup previously installed "node_modules" folders');
        $this->cleanupPreviouslyInstalledDependencies();

        if ($needManualBuild) {
            if ($isTaggedVersion) {
                $ui->warning(\sprintf(
                    'The files in the local "%s" folder do not match the ones in the remote repository "%s".' . \PHP_EOL
                    . 'If you have added custom JavaScript to the administration interface, you need to create '
                    . 'the JavaScript build by yourself.',
                    static::ASSETS_DIR,
                    $remoteRepository
                ));
            } else {
                $ui->warning('You are not using a tagged version of the "sulu/sulu" package and therefore need to create the JavaScript build by yourself.');
            }

            return $this->doManualBuild($ui);
        }

        $tempDirectory = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . static::REPOSITORY_NAME . \uniqid((string) \rand(), true);
        $tempFileZip = $tempDirectory . '.zip';

        $output->writeln('<info>Download remote repository...</info>');
        $response = $this->httpClient->request('GET', $remoteArchive);
        \file_put_contents($tempFileZip, $response->getContent());

        if (!\class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('The "ext-zip" extension is required to download the admin build.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($tempFileZip)) {
            $output->writeln('<info>Extract ZIP archive...</info>');
            $zip->extractTo($tempDirectory);
            $zip->close();

            $buildDir = $this->projectDir . static::BUILD_DIR;
            $extractedFolderName = static::REPOSITORY_NAME . '-' . $suluVersion;
            $tempProjectDir = $tempDirectory . \DIRECTORY_SEPARATOR . $extractedFolderName;

            $output->writeln('<info>Delete old build folder...</info>');
            $filesystem->remove(\glob($buildDir . \DIRECTORY_SEPARATOR . '*'));

            $output->writeln('<info>Copy build folder from remote repository...</info>');
            $filesystem->mirror(
                $tempProjectDir . static::BUILD_DIR,
                $buildDir
            );

            $filesystem->remove($tempDirectory);
        } else {
            $output->writeln('<error>Error when unpacking the ZIP archive</error>');
        }

        \unlink($tempFileZip);

        return 0;
    }

    private function getLocalFile(string $path): string
    {
        if (!\file_exists($this->projectDir . $path)) {
            return '';
        }

        return \file_get_contents($this->projectDir . $path);
    }

    private function getRemoteFile(string $remoteRepository, string $path): string
    {
        $path = \str_replace(\DIRECTORY_SEPARATOR, '/', $path);

        try {
            $response = $this->httpClient->request('GET', $remoteRepository . $path);

            return $response->getContent();
        } catch (ClientException $e) {
            return '';
        }
    }

    private function hash(string $content): string
    {
        /** @var string $replacedContent */
        $replacedContent = \preg_replace('/\s+/', '', $content);

        // we remove all whitespaces as the developer could change the indention or/and the line breaks of this files
        return \hash('sha256', $replacedContent);
    }

    private function writeFile(string $path, string $content): void
    {
        \file_put_contents($this->projectDir . $path, $content);
    }

    private function mergeJsonStrings(string $mainJson, string $additionalJson): string
    {
        $mainJsonArray = \json_decode($mainJson, true);
        $additionalJsonArray = \json_decode($additionalJson, true);

        if (!$mainJsonArray) {
            throw new \RuntimeException(\sprintf('The following is not a valid json: ' . \PHP_EOL . '%s', $mainJson));
        }

        if (!$additionalJsonArray) {
            throw new \RuntimeException(\sprintf('The following is not a valid json: ' . \PHP_EOL . '%s', $additionalJson));
        }

        $jsonArray = \array_replace_recursive($mainJsonArray, $additionalJsonArray);

        return \json_encode($jsonArray, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);
    }

    private function doManualBuild(SymfonyStyle $ui): int
    {
        if ('y' !== \strtolower($ui->ask('Do you want to create a build now?', 'y'))) {
            return static::EXIT_CODE_ABORTED_MANUAL_BUILD;
        }

        $ui->title('Start manual build ...');

        $ui->section('Cleanup previously installed "node_modules" folders');
        $this->cleanupPreviouslyInstalledDependencies();

        $errorHelpMessage =
            'Visit https://docs.sulu.io/en/latest/cookbook/build-admin-frontend.html#common-errors' . \PHP_EOL
            . 'to get some tips on how to fix this error.';

        $ui->section('Install npm dependencies');
        if ($this->runProcess($ui, 'npm install')) {
            $ui->error('Unexpected error while installing npm dependencies.');
            $ui->info($errorHelpMessage);

            return static::EXIT_CODE_COULD_NOT_INSTALL_NPM_PACKAGES;
        }

        $ui->section('Build administration interface assets');
        if ($this->runProcess($ui, 'npm run build')) {
            $ui->error('Unexpected error while building administration interface assets.');
            $ui->info($errorHelpMessage);

            return static::EXIT_CODE_COULD_NOT_BUILD_ADMIN_ASSETS;
        }

        return 0;
    }

    private function cleanupPreviouslyInstalledDependencies(): void
    {
        $filesToCleanup = [
            'package-lock.json',
            'yarn.lock',
            'node_modules',
        ];

        /** @var array{dependencies: array<string, string>}|null $packageJson */
        $packageJson = \json_decode($this->getLocalFile(static::ASSETS_DIR . 'package.json'), true);
        if (!$packageJson) {
            throw new \Exception(\sprintf('Could not parse "%s" file', static::ASSETS_DIR . 'package.json'));
        }

        $suluVendorAssetFolder = \dirname(\dirname(\dirname(\dirname(\dirname(__DIR__)))));
        $npmPackageFolders = [
            $this->projectDir . static::ASSETS_DIR,
            $suluVendorAssetFolder,
        ];

        foreach ($packageJson['dependencies'] as $dependency => $path) {
            if (0 !== \strpos($path, 'file:')) {
                continue;
            }

            $npmPackageFolders[] = $this->projectDir . static::ASSETS_DIR . \substr($path, \strlen('file:'));
        }

        $filesystem = new Filesystem();
        foreach ($npmPackageFolders as $folder) {
            foreach ($filesToCleanup as $blockingFile) {
                $path = $folder . $blockingFile;

                if (!$filesystem->exists($path)) {
                    continue;
                }

                $filesystem->remove($path);
            }
        }
    }

    private function runProcess(SymfonyStyle $ui, string $command): ?int
    {
        $process = Process::fromShellCommandline($command, $this->projectDir . static::ASSETS_DIR);
        $process->setTimeout(3600);
        $process->run(function($type, $buffer) use ($ui) {
            $ui->write($buffer, false, OutputInterface::OUTPUT_RAW);
        });

        return $process->getExitCode();
    }
}
