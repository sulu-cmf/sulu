<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Export;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Content\Structure\ExcerptStructureExtension;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\Export\Export;
use Sulu\Component\Export\Manager\ExportManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

/**
 * Export Content by given locale to xliff file.
 */
class WebspaceExport extends Export implements WebspaceExportInterface
{
    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    protected $extensionManager;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(
        Environment $templating,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ExportManagerInterface $exportManager,
        array $formatFilePaths
    ) {
        parent::__construct($templating, $documentManager, $documentInspector, $exportManager, $formatFilePaths);

        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->output = new NullOutput();
    }

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
            throw new \Exception(\sprintf('Invalid parameters for export "%s (%s)"', $webspaceKey, $locale));
        }

        return $this->templating->render(
            $this->getTemplate($this->format),
            $this->getExportData($webspaceKey, $uuid, $nodes, $ignoredNodes)
        );
    }

    /**
     * @return array{
     *  webspaceKey: string,
     *  locale: string,
     *  format: string,
     *  documents: array<mixed>
     * }
     */
    public function getExportData($webspaceKey, $uuid = null, $nodes = null, $ignoredNodes = null)
    {
        /** @var PageDocument[] $documents */
        $documents = $this->getDocuments($webspaceKey, $uuid, $nodes, $ignoredNodes);
        /** @var PageDocument[] $loadedDocuments */
        $documentData = [];

        $this->output->writeln('<info>Loading Data…</info>');

        $progress = new ProgressBar($this->output, \count($documents));
        $progress->start();

        foreach ($documents as $key => $document) {
            $contentData = $this->getContentData($document, $this->exportLocale);
            $extensionData = $this->getExtensionData($document);
            $settingData = $this->getSettingData($document);

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
     * @return array
     */
    protected function getExtensionData(BasePageDocument $document)
    {
        $data = $document->getExtensionsData();
        if ($data instanceof ExtensionContainer) {
            $data = $data->toArray();
        }

        $extensionData = [];
        foreach ($data as $extensionName => $extensionProperties) {
            /** @var ExcerptStructureExtension $extension */
            $extension = $this->extensionManager->getExtension((string) $document->getStructureType(), $extensionName);

            if ($extension instanceof ExportExtensionInterface) {
                $extensionData[$extensionName] = $extension->export($extensionProperties, $this->format);
            }
        }

        return $extensionData;
    }

    /**
     * Returns a flat array with the settings of the given document.
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
        if ('1.2.xliff' === $this->format) {
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
                \json_encode($document->getNavigationContexts()),
                $settingOptions
            ),
            'permissions' => $this->createProperty(
                'permissions',
                \json_encode($document->getPermissions()),
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
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return array
     *
     * @throws DocumentManagerException
     */
    protected function getDocuments($webspaceKey, $uuid = null, $nodes = null, $ignoredNodes = null)
    {
        $queryString = $this->getDocumentsQueryString($webspaceKey, $uuid, $nodes, $ignoredNodes);

        $query = $this->documentManager->createQuery($queryString, $this->exportLocale);

        return $query->execute();
    }

    /**
     * Create the query to get all documents from given webspace and language.
     *
     * @param string $webspaceKey
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return string
     *
     * @throws DocumentManagerException
     */
    protected function getDocumentsQueryString($webspaceKey, $uuid = null, $nodes = null, $ignoredNodes = null)
    {
        $where = [];

        // only pages
        $where[] = '([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home")';

        // filter by webspace key
        $where[] = \sprintf(
            '(ISDESCENDANTNODE("/cmf/%s/contents") OR ISSAMENODE("/cmf/%s/contents"))',
            $webspaceKey,
            $webspaceKey
        );

        // filter by locale
        $where[] = \sprintf(
            '[i18n:%s-template] IS NOT NULL',
            $this->exportLocale
        );

        // filter by uuid
        if ($uuid) {
            $where[] = \sprintf('[jcr:uuid] = "%s"', $uuid);
        }

        $nodeWhere = $this->buildNodeUuidToPathWhere($nodes, false);

        if ($nodeWhere) {
            $where[] = $nodeWhere;
        }

        $ignoreWhere = $this->buildNodeUuidToPathWhere($ignoredNodes, true);
        if ($ignoreWhere) {
            $where[] = $ignoreWhere;
        }

        $queryString = 'SELECT * FROM [nt:unstructured] AS a WHERE ' . \implode(' AND ', $where);

        $queryString .= ' ORDER BY [jcr:path] ASC';

        return $queryString;
    }

    /**
     * Build query to return only specific nodes.
     *
     * @param array $nodes
     * @param bool|false $not
     *
     * @return string
     *
     * @throws DocumentManagerException
     */
    protected function buildNodeUuidToPathWhere($nodes, $not = false)
    {
        if ($nodes) {
            $paths = $this->getPathsByUuids($nodes);

            $wheres = [];
            foreach ($nodes as $key => $uuid) {
                if (isset($paths[$uuid])) {
                    $wheres[] = \sprintf('ISDESCENDANTNODE("%s")', $paths[$uuid]);
                }
            }

            if (!empty($wheres)) {
                return ($not ? 'NOT ' : '') . '(' . \implode(' OR ', $wheres) . ')';
            }
        }
    }

    /**
     * Returns node path from given uuid.
     *
     * @param string[] $uuids
     *
     * @return string[]
     *
     * @throws DocumentManagerException
     */
    protected function getPathsByUuids($uuids)
    {
        $paths = [];

        $where = [];
        foreach ($uuids as $uuid) {
            $where[] = \sprintf('[jcr:uuid] = "%s"', $uuid);
        }

        $queryString = 'SELECT * FROM [nt:unstructured] AS a WHERE ' . \implode(' OR ', $where);

        $query = $this->documentManager->createQuery($queryString);

        $result = $query->execute();

        /** @var BasePageDocument $page */
        foreach ($result as $page) {
            $paths[$page->getUuid()] = $page->getPath();
        }

        return $paths;
    }
}
