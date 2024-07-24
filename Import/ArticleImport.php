<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Import;

use PHPCR\NodeInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Structure\ArticleBridge;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\Import\Format\FormatImportInterface;
use Sulu\Component\Import\Import;
use Sulu\Component\Import\Manager\ImportManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ArticleImport extends Import implements ArticleImportInterface
{
    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var DocumentInspector
     */
    protected $documentInspector;

    /**
     * @var DocumentRegistry
     */
    protected $documentRegistry;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    protected $extensionManager;

    /**
     * @var ResourceLocatorStrategyInterface
     */
    protected $rlpStrategy;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected static $excludedSettings = [
        'title',
        'locale',
        'webspaceName',
        'structureType',
        'originalLocale',
        'url',
        'resourceSegment',
    ];

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        DocumentRegistry $documentRegistry,
        LegacyPropertyFactory $legacyPropertyFactory,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ImportManagerInterface $importManager,
        FormatImportInterface $xliff12,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($importManager, $legacyPropertyFactory, ['1.2.xliff' => $xliff12]);

        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->documentRegistry = $documentRegistry;
        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->logger = $logger ?: new NullLogger();
    }

    public function import(
        string $locale,
        string $filePath,
        ?OutputInterface $output = null,
        string $format = '1.2.xliff',
        bool $overrideSettings = false
    ): ImportResult {
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

            try {
                $this->importDocument($parsedData, $format, $locale, $overrideSettings);
                ++$successCounter;
            } catch (ImportException $exception) {
                $failedImports[] = $parsedData;
            }

            $this->logger->info(\sprintf('Document %s/%s', $importedCounter, \count($parsedDataList)));

            $progress->advance();
        }

        $progress->finish();

        return new ImportResult($importedCounter, \count($failedImports), $successCounter, $failedImports, $this->exceptionStore);
    }

    protected function importDocument(array $parsedData, string $format, string $locale, bool $overrideSettings): void
    {
        $uuid = null;

        try {
            if (!isset($parsedData['uuid']) || !isset($parsedData['structureType']) || !isset($parsedData['data'])) {
                $this->addException('uuid, structureType or data for import not found.', 'ignore');

                throw new ImportException('uuid, structureType or data for import not found.');
            }

            $uuid = $parsedData['uuid'];
            $structureType = $parsedData['structureType'];
            $data = $parsedData['data'];

            /** @var ArticleDocument $document */
            $document = $this->documentManager->find(
                $uuid,
                $locale,
                [
                    'load_ghost_content' => false,
                ]
            );

            if (!$document instanceof ArticleDocument) {
                throw new ImportException(\sprintf('Document(%s) is not an instanecof BasePageDocument', $uuid));
            }

            $document->setStructureType($structureType);

            if (!$this->setDocumentData($document, $locale, $format, $data)) {
                throw new ImportException();
            }

            $this->setDocumentSettings($document, $format, $data, $overrideSettings);

            // save document
            $this->documentManager->persist($document, $locale);

            if (WorkflowStage::PUBLISHED === ((int) $this->getParser($format)->getPropertyData('workflowStage', $data))) {
                $this->documentManager->publish($document, $locale);
            }

            $this->documentManager->flush();
            $this->documentRegistry->clear();
        } catch (\Exception $e) {
            if ($e instanceof DocumentManagerException) {
                throw new ImportException('', 0, $e);
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

            throw new ImportException('', 0, $e);
        }
    }

    protected function setDocumentData(
        ArticleDocument $document,
        string $locale,
        string $format,
        array $data
    ): bool {
        /** @var ArticleBridge $structure */
        $structure = $this->structureManager->getStructure($document->getStructureType(), 'article');
        $node = $this->documentRegistry->getNodeForDocument($document);
        $node->setProperty(\sprintf('i18n:%s-template', $locale), $document->getStructureType());
        $state = $this->getParser($format)->getPropertyData('state', $data, null, null, 2);
        $node->setProperty(\sprintf('i18n:%s-state', $locale), $state);

        if ('' === $this->getParser($format)->getPropertyData('title', $data)) {
            $this->addException(\sprintf('Document(%s) has not set any title', $document->getUuid()), 'ignore');

            return false;
        }

        $structure->setDocument($document);

        $properties = $structure->getProperties(true);
        foreach ($properties as $property) {
            $value = $this->getParser($format)->getPropertyData(
                $property->getName(),
                $data,
                $property->getContentTypeName()
            );

            $this->importProperty($property, $node, $structure, $value, null, $locale, $format);
            $document->getStructure()->getProperty($property->getName())->setValue($property->getValue());
        }

        // import extensions
        $extensions = $this->extensionManager->getExtensions($document->getStructureType());

        foreach ($extensions as $key => $extension) {
            $extensionData = $this->importExtension($extension, $key, $node, $data, $locale, $format);
            $document->setExtension($key, $extensionData);
        }

        // set required data
        $document->setTitle($this->getParser($format)->getPropertyData('title', $data));

        return true;
    }

    protected function setDocumentSettings(
        ArticleDocument $document,
        string $format,
        array $data,
        bool $overrideSettings
    ): void {
        if (!$overrideSettings) {
            return;
        }

        foreach ($data as $key => $property) {
            $setter = 'set' . \ucfirst($key);

            if (\in_array($key, self::$excludedSettings) || !\method_exists($document, $setter)) {
                continue;
            }

            $value = $this->getParser($format)->getPropertyData(
                $key,
                $data
            );

            $document->$setter($this->getSetterValue($key, $value));
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function getSetterValue(string $key, $value)
    {
        if (empty($value)) {
            return null;
        }

        switch ($key) {
            case 'additionalWebspaces':
                $value = \json_decode($value, true);
                break;
            case 'authored':
                $value = new \DateTime($value);
                break;
        }

        return $value;
    }

    protected function importExtension(
        ExtensionInterface $extension,
        string $extensionKey,
        NodeInterface $node,
        array $data,
        string $locale,
        string $format
    ): array {
        $extensionData = [];

        if ($extension instanceof ExportExtensionInterface) {
            foreach ($extension->getImportPropertyNames() as $propertyName) {
                $value = $this->getParser($format)->getPropertyData(
                    $propertyName,
                    $data,
                    null,
                    $extensionKey
                );

                $extensionData[$propertyName] = $value;
            }

            $extension->import($node, $extensionData, null, $locale, $format);
        }

        return $extension->load($node, null, $locale);
    }
}
