<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Snippet\Import;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\Import\Exception\FormatImporterNotFoundException;
use Sulu\Component\Import\Format\FormatImportInterface;
use Sulu\Component\Import\Import;
use Sulu\Component\Import\Manager\ImportManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import Snippets by given xliff-file.
 */
class SnippetImport extends Import implements SnippetImportInterface
{
    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var DocumentRegistry
     */
    protected $documentRegistry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        private DocumentManagerInterface $documentManager,
        StructureManagerInterface $structureManager,
        DocumentRegistry $documentRegistry,
        ImportManagerInterface $importManager,
        LegacyPropertyFactory $legacyPropertyFactory,
        FormatImportInterface $xliff12,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($importManager, $legacyPropertyFactory, ['1.2.xliff' => $xliff12]);
        $this->documentRegistry = $documentRegistry;
        $this->structureManager = $structureManager;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Import Snippet by given XLIFF-File.
     *
     * @param string $locale
     * @param string $filePath
     * @param string $format
     *
     * @return \stdClass
     *
     * @throws FormatImporterNotFoundException
     */
    public function import($locale, $filePath, ?OutputInterface $output = null, $format = '1.2.xliff')
    {
        $parsedDataList = $this->getParser($format)->parse($filePath, $locale);
        $failedImports = [];
        $importedCounter = 0;
        $successCounter = 0;

        if (null === $output) {
            $output = new NullOutput();
        }

        $progress = new ProgressBar($output, \count($parsedDataList));
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progress->start();

        foreach ($parsedDataList as $parsedData) {
            ++$importedCounter;

            if (!$this->importDocument($parsedData, $locale, $format)) {
                $failedImports[] = $parsedData;
            } else {
                ++$successCounter;
            }

            $this->logger->info(\sprintf('Document %s/%s', $importedCounter, \count($parsedDataList)));

            $progress->advance();
        }

        $progress->finish();

        $return = new \stdClass();
        $return->count = $importedCounter;
        $return->fails = \count($failedImports);
        $return->successes = $successCounter;
        $return->failed = $failedImports;
        $return->exceptionStore = $this->exceptionStore;

        return $return;
    }

    /**
     * Import document by locale into given webspace.
     *
     * @param string $locale
     * @param string $format
     *
     * @return bool
     */
    protected function importDocument(array $parsedData, $locale, $format)
    {
        $uuid = $parsedData['uuid'];
        $data = $parsedData['data'];
        $documentType = Structure::TYPE_SNIPPET;

        try {
            /** @var SnippetDocument $document */
            $document = $this->documentManager->find(
                $uuid,
                $locale,
                [
                    'type' => $documentType,
                    'load_ghost_content' => false,
                ]
            );

            if (!$document instanceof SnippetDocument) {
                throw new \Exception(
                    \sprintf('Document(%s) is not an instanecof SnippetDocument', $uuid)
                );
            }

            if (!$this->setDocumentData($document, $locale, $format, $data)) {
                return false;
            }

            // set required data
            $document->setTitle($this->getParser($format)->getPropertyData('title', $data));

            // save document
            $this->documentManager->persist($document, $locale);
            $this->documentManager->publish($document, $locale);
            $this->documentManager->flush();
            $this->documentRegistry->clear(); // FIXME else it failed on multiple page import

            return true;
        } catch (\Exception $e) {
            if ($e instanceof DocumentManagerException) {
                return false;
            }

            $this->logger->error(
                \sprintf(
                    '<info>%s</info>%s: <error>%s</error>%s',
                    $uuid,
                    \PHP_EOL . \get_class($e),
                    $e->getMessage(),
                    \PHP_EOL . $e->getTraceAsString()
                )
            );

            $this->documentManager->flush();
            $this->documentManager->clear();
        }

        return false;
    }

    /**
     * Loops all documents and imports all properties of the documents.
     *
     * @param SnippetDocument $document
     * @param string $locale
     * @param string $format
     * @param array $data
     *
     * @return bool
     *
     * @throws FormatImporterNotFoundException
     */
    protected function setDocumentData($document, $locale, $format, $data)
    {
        $structure = $this->structureManager->getStructure((string) $document->getStructureType(), Structure::TYPE_SNIPPET);
        $properties = $structure->getProperties(true);
        $node = $this->documentRegistry->getNodeForDocument($document);
        $node->setProperty(\sprintf('i18n:%s-template', $locale), $document->getStructureType());
        $state = $this->getParser($format)->getPropertyData('state', $data, null, null, 2);
        $node->setProperty(\sprintf('i18n:%s-state', $locale), $state);

        // Check title is set in xliff-file.
        if ('' === $this->getParser($format)->getPropertyData('title', $data)) {
            $this->addException(\sprintf('Snippet(%s) has not set any title', $document->getUuid()), 'ignore');

            return false;
        }

        // Import document-property.
        foreach ($properties as $property) {
            $value = $this->getParser($format)->getPropertyData(
                $property->getName(),
                $data,
                $property->getContentTypeName()
            );

            $this->importProperty($property, $node, $structure, $value, null, $locale, $format);
        }

        return true;
    }
}
