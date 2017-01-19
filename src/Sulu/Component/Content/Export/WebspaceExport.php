<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Export;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\Export\Export;
use Sulu\Component\Export\Manager\ExportManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Templating\EngineInterface;

/**
 * Export Content by given locale to xliff file.
 */
class WebspaceExport extends Export implements WebspaceExportInterface
{
    /**
     * @var EngineInterface
     */
    protected $documentInspector;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    protected $extensionManager;

    /**
     * @var ExportManagerInterface
     */
    protected $exportManager;

    /**
     * @var string[]
     */
    protected $formatFilePaths;

    /**
     * @var Output
     */
    protected $output;

    /**
     * @param EngineInterface $templating
     * @param DocumentManager $documentManager
     * @param DocumentInspector $documentInspector
     * @param StructureManagerInterface $structureManager
     * @param ExtensionManagerInterface $extensionManager
     * @param ExportManagerInterface $exportManager
     * @param array $formatFilePaths
     */
    public function __construct(
        EngineInterface $templating,
        DocumentManager $documentManager,
        DocumentInspector $documentInspector,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ExportManagerInterface $exportManager,
        array $formatFilePaths
    ) {
        $this->templating = $templating;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->exportManager = $exportManager;
        $this->formatFilePaths = $formatFilePaths;
        $this->output = new NullOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function export(
        $webspaceKey,
        $locale,
        $output,
        $format = '1.2.xliff',
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    ) {
        $this->exportLocale = $locale;
        $this->output = $output;
        $this->format = $format;

        if (null === $this->output) {
            $this->output = new NullOutput();
        }

        if (!$webspaceKey || !$locale) {
            throw new \Exception(sprintf('Invalid parameters for export "%s (%s)"', $webspaceKey, $locale));
        }

        return $this->templating->render(
            $this->getTemplate($this->format),
            $this->getExportData($webspaceKey, $uuid, $nodes, $ignoredNodes)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExportData(
        $webspaceKey,
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    ) {
        /** @var PageDocument[] $documents */
        $documents = $this->getDocuments($webspaceKey, $uuid, $nodes, $ignoredNodes);
        /** @var PageDocument[] $loadedDocuments */
        $documentData = [];

        $this->output->writeln('<info>Loading Data…</info>');

        $progress = new ProgressBar($this->output, count($documents));
        $progress->start();

        foreach ($documents as $key => $document) {
            $contentData = $this->getContentData($document, $this->exportLocale, $this->format);
            $extensionData = $this->getExtensionData($document, $this->format);
            $settingData = $this->getSettingData($document, $this->format);

            $documentData[] = [
                'uuid' => $document->getUuid(),
                'locale' => $document->getLocale(),
                'content' => $contentData,
                'settings' => $settingData,
                'extensions' => $extensionData,
            ];

            $progress->advance();
        }

        $progress->finish();

        $this->output->writeln([
            '',
            '<info>Render Xliff…</info>',
        ]);

        return [
            'webspaceKey' => $webspaceKey,
            'locale' => $this->exportLocale,
            'format' => $this->format,
            'documents' => $documentData,
        ];
    }

    /**
     * Returns a flat array with the extensions of the given document.
     *
     * @param BasePageDocument $document
     * @param string $format
     *
     * @return array
     */
    protected function getExtensionData(BasePageDocument $document)
    {
        $extensionData = [];

        foreach ($document->getExtensionsData()->toArray() as $extensionName => $extensionProperties) {
            /** @var \Sulu\Bundle\ContentBundle\Content\Structure\ExcerptStructureExtension $extension */
            $extension = $this->extensionManager->getExtension($document->getStructureType(), $extensionName);

            if ($extension instanceof ExportExtensionInterface) {
                $extensionData[$extensionName] = $extension->export($extensionProperties, $this->format);
            }
        }

        return $extensionData;
    }

    /**
     * Returns a flat array with the settings of the given document.
     *
     * @param BasePageDocument $document
     * @param string $format
     *
     * @return array
     */
    protected function getSettingData(BasePageDocument $document)
    {
        if ($created = $document->getCreated()) {
            $created = $created->format('c');
        }

        if ($changed = $document->getChanged()) {
            $changed = $changed->format('c');
        }

        if ($published = $document->getPublished()) {
            $published = $published->format('c');
        }

        $settingOptions = [];
        if ($this->format === '1.2.xliff') {
            $settingOptions = ['translate' => false];
        }

        return [
            'structureType' => $this->createProperty('structureType', $document->getStructureType(), $settingOptions),
            'published' => $this->createProperty('published', $published, $settingOptions),
            'created' => $this->createProperty('created', $created, $settingOptions),
            'changed' => $this->createProperty('changed', $changed, $settingOptions),
            'creator' => $this->createProperty('creator', $document->getCreator(), $settingOptions),
            'changer' => $this->createProperty('changer', $document->getChanger(), $settingOptions),
            'locale' => $this->createProperty('locale', $document->getLocale(), $settingOptions),
            'navigationContexts' => $this->createProperty(
                'navigationContexts',
                json_encode($document->getNavigationContexts()),
                $settingOptions
            ),
            'permissions' => $this->createProperty(
                'permissions',
                json_encode($document->getPermissions()),
                $settingOptions
            ),
            'shadowLocale' => $this->createProperty('shadowLocale', $document->getShadowLocale(), $settingOptions),
            'originalLocale' => $this->createProperty(
                'originalLocale',
                $document->getOriginalLocale(),
                $settingOptions
            ),
            'resourceSegment' => $this->createProperty(
                'resourceSegment',
                $document->getResourceSegment(),
                $settingOptions
            ),
            'webspaceName' => $this->createProperty('webspaceName', $document->getWebspaceName(), $settingOptions),
            'redirectExternal' => $this->createProperty(
                'redirectExternal',
                $document->getRedirectExternal(),
                $settingOptions
            ),
            'redirectType' => $this->createProperty('redirectType', $document->getRedirectType(), $settingOptions),
            'redirectTarget' => $this->createProperty(
                'redirectTarget',
                $document->getRedirectTarget(),
                $settingOptions
            ),
            'workflowStage' => $this->createProperty('workflowStage', $document->getWorkflowStage(), $settingOptions),
            'path' => $this->createProperty('path', $document->getPath(), $settingOptions),
        ];
    }

    /**
     * Returns all Documents from given webspace.
     *
     * @param string $webspaceKey
     * @param string $locale
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return array
     */
    protected function getDocuments(
        $webspaceKey,
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    ) {
        $queryString = $this->getDocumentsQueryString($webspaceKey, $uuid, $nodes, $ignoredNodes);

        $query = $this->documentManager->createQuery($queryString, $this->exportLocale);

        return $query->execute();
    }

    /**
     * Create the query to get all documents from given webspace and language.
     *
     * @param $webspaceKey
     * @param $locale
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return string
     */
    protected function getDocumentsQueryString(
        $webspaceKey,
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    ) {
        $where = [];

        // only pages
        $where[] = '([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home")';

        // filter by webspace key
        $where[] = sprintf(
            '(ISDESCENDANTNODE("/cmf/%s/contents") OR ISSAMENODE("/cmf/%s/contents"))',
            $webspaceKey,
            $webspaceKey
        );

        // filter by locale
        $where[] = sprintf(
            '[i18n:%s-template] IS NOT NULL',
            $this->exportLocale
        );

        // filter by uuid
        if ($uuid) {
            $where[] = sprintf('[jcr:uuid] = "%s"', $uuid);
        }

        $nodeWhere = $this->buildNodeUuidToPathWhere($nodes, false);

        if ($nodeWhere) {
            $where[] = $nodeWhere;
        }

        $ignoreWhere = $this->buildNodeUuidToPathWhere($ignoredNodes, true);
        if ($ignoreWhere) {
            $where[] = $ignoreWhere;
        }

        $queryString = 'SELECT * FROM [nt:unstructured] AS a WHERE ' . implode(' AND ', $where);

        return $queryString;
    }

    /**
     * Build query to return only specific nodes.
     *
     * @param $nodes
     * @param bool|false $not
     *
     * @return string
     */
    protected function buildNodeUuidToPathWhere($nodes, $not = false)
    {
        if ($nodes) {
            $paths = $this->getPathsByUuids($nodes);

            $wheres = [];
            foreach ($nodes as $key => $uuid) {
                if (isset($paths[$uuid])) {
                    $wheres[] = sprintf('ISDESCENDANTNODE("%s")', $paths[$uuid]);
                }
            }

            if (!empty($wheres)) {
                return ($not ? 'NOT ' : '') . '(' . implode(' OR ', $wheres) . ')';
            }
        }
    }

    /**
     * Returns node path from given uuid.
     *
     * @param $uuids
     *
     * @return string[]
     */
    protected function getPathsByUuids($uuids)
    {
        $paths = [];

        $where = [];
        foreach ($uuids as $uuid) {
            $where[] = sprintf('[jcr:uuid] = "%s"', $uuid);
        }

        $queryString = 'SELECT * FROM [nt:unstructured] AS a WHERE ' . implode(' OR ', $where);

        $query = $this->documentManager->createQuery($queryString);

        $result = $query->execute();

        /** @var BasePageDocument $page */
        foreach ($result as $page) {
            $paths[$page->getUuid()] = $page->getPath();
        }

        return $paths;
    }
}
