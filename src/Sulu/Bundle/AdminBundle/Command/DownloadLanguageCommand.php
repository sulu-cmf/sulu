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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webmozart\Assert\Assert;

class DownloadLanguageCommand extends Command
{
    protected static $defaultName = 'sulu:admin:download-language';

    /**
     * @param array<string> $defaultLanguages
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private Filesystem $filesystem,
        private string $projectDir,
        private array $defaultLanguages,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Downloads the currently approved translations for the given language.')
            ->addArgument('languages', InputArgument::IS_ARRAY, 'The languages to download', $this->defaultLanguages)
            ->addOption(
                'translation-endpoint',
                null,
                InputOption::VALUE_REQUIRED,
                'The endpoint where the translation should be downloaded.',
                'https://translations.sulu.io/'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!\class_exists(\ZipArchive::class)) {
            $ui = new SymfonyStyle($input, $output);
            $ui->error('The "sulu:admin:download-language" command requires the "zip" php extension.');

            return 1;
        }

        $languages = $input->getArgument('languages');

        foreach ($languages as $language) {
            $this->executeLanguage($input, $output, $language);
        }

        return 0;
    }

    protected function executeLanguage(InputInterface $input, OutputInterface $output, string $language): void
    {
        $translationBaseUrl = $input->getOption('translation-endpoint');

        $ui = new SymfonyStyle($input, $output);
        $ui->section('Language: ' . $language);

        if (\in_array($language, ['en', 'de'])) {
            $ui->note(\sprintf(
                'Download skipped because Sulu includes a translation for the language "%s" per default.',
                $language
            ));

            return;
        }

        /** @var array{require: array<string, string>} $composerJson */
        $composerJson = \json_decode(
            \file_get_contents($this->projectDir . \DIRECTORY_SEPARATOR . 'composer.json'),
            true,
            flags: \JSON_THROW_ON_ERROR
        );
        $packages = \array_keys($composerJson['require']);
        $suluPackages = \array_filter($packages, function($package) {
            return 0 === \strpos($package, 'sulu/');
        });

        $translationsFile = $this->projectDir
            . \DIRECTORY_SEPARATOR
            . 'translations/sulu'
            . \DIRECTORY_SEPARATOR
            . 'admin.' . $language . '.json';

        $translations = [];
        if (\file_exists($translationsFile)) {
            $translations = \json_decode(\file_get_contents($translationsFile), true);
            Assert::isArray($translations, 'Unable to read translations file into array: ' . $translationsFile);
        }

        $packages = [];
        foreach ($suluPackages as $suluPackage) {
            $packageTranslations = $this->downloadLanguage($output, $language, $suluPackage, $translationBaseUrl);
            $packages[$suluPackage] = [
                'Package' => $suluPackage,
                'Translations' => \count($packageTranslations),
            ];
            $translations = \array_merge($translations, $packageTranslations);
        }

        $ui->newLine();

        if (empty($translations)) {
            $ui->warning(\sprintf('Translation for language "%s" does not exist yet. Reach out to the Sulu team via https://sulu.io/contact-us if you are interested in creating it. Your contribution is highly appreciated!', $language));

            return;
        }

        $ui->table(['Package', 'Translations'], $packages);

        $output->writeln(\sprintf('<info>Writing language %s into translations folder</info>', $language));

        $this->filesystem->mkdir(\dirname($translationsFile));

        $this->filesystem->dumpFile($translationsFile, \json_encode($translations, flags: \JSON_THROW_ON_ERROR));
    }

    private function downloadLanguage(
        OutputInterface $output,
        string $language,
        string $project,
        string $translationBaseUrl,
    ): array {
        $output->writeln('<info>Starting download for the "' . $project . '" project in "' . $language . '"</info>');
        $response = $this->httpClient->request(
            'GET',
            $translationBaseUrl . $project . '/' . $language
        );

        $tempDirectory = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'sulu-language-' . $language . \DIRECTORY_SEPARATOR;
        $tempFile = $tempDirectory . 'download.zip';

        $this->filesystem->mkdir($tempDirectory);

        if (404 === $response->getStatusCode()) {
            return [];
        }

        $this->filesystem->dumpFile($tempFile, $response->getContent());

        $translations = [];
        $zip = new \ZipArchive();
        if ($zip->open($tempFile)) {
            $output->writeln('<info>Extract ZIP archive...</info>');
            $zip->extractTo($tempDirectory);
            $zip->close();

            $languageFile = 'admin.' . $language . '.json';
            $translations = \json_decode(\file_get_contents($tempDirectory . $languageFile), true);
        } else {
            $output->writeln('<error>Error when unpacking the ZIP archive</error>');
        }

        $this->filesystem->remove($tempDirectory);

        return $translations;
    }
}
