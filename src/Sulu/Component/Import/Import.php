<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Import;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Import\Exception\FormatImporterNotFoundException;
use Sulu\Component\Import\Format\FormatImportInterface;
use Sulu\Component\Import\Manager\ImportManagerInterface;

/**
 * Base class for document-language import.
 */
class Import
{
    /**
     * @var array
     */
    protected $exceptionStore = [];

    /**
     * @param FormatImportInterface[] $formatFilePaths
     */
    public function __construct(
        protected ImportManagerInterface $importManager,
        protected LegacyPropertyFactory $legacyPropertyFactory,
        protected array $formatFilePaths
    ) {
        $this->formatFilePaths = $formatFilePaths;
        $this->importManager = $importManager;
        $this->legacyPropertyFactory = $legacyPropertyFactory;
    }

    /**
     * Returns the correct parser like XLIFF1.2.
     *
     * @param string $format
     *
     * @return FormatImportInterface
     *
     * @throws FormatImporterNotFoundException
     */
    protected function getParser($format)
    {
        if (!isset($this->formatFilePaths[$format])) {
            throw new FormatImporterNotFoundException($format);
        }

        return $this->formatFilePaths[$format];
    }

    /**
     * Prepare document-property and import them.
     *
     * @param string $value
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     */
    protected function importProperty(
        PropertyInterface $property,
        NodeInterface $node,
        StructureInterface $structure,
        $value,
        $webspaceKey,
        $locale,
        $format
    ) {
        $contentType = $property->getContentTypeName();

        if (!$this->importManager->hasImport($contentType, $format)) {
            return;
        }

        $translateProperty = $this->legacyPropertyFactory->createTranslatedProperty($property, $locale, $structure);
        $this->importManager->import($contentType, $node, $translateProperty, $value, null, $webspaceKey, $locale);
    }

    /**
     * Add a specific import exception/warning to the exception store.
     * This messages will print after the import is done.
     *
     * @param string $msg
     * @param string $type
     */
    protected function addException($msg = null, $type = 'info')
    {
        if (null === $msg) {
            return;
        }

        if (!isset($this->exceptionStore[$type])) {
            $this->exceptionStore[$type] = [];
        }

        $this->exceptionStore[$type][] = $msg;
    }
}
