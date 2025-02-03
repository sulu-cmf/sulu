<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Command;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\MarkupBundle\Markup\HtmlTagExtractor;
use Sulu\Bundle\MarkupBundle\Markup\TagMatchGroup;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\EventListener\PageRemoveSubscriber;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ItemMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sulu:webspaces:copy', description: 'Copies a given webspace with given locale to a destination webspace with a destination locale.')]
class WebspaceCopyCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    protected $webspaceKeySource;

    /**
     * @var string
     */
    protected $webspaceKeyDestination;

    public function __construct(
        private DocumentManagerInterface $documentManager,
        private SessionManagerInterface $sessionManager,
        private DocumentInspector $documentInspector,
        private HtmlTagExtractor $htmlTagExtractor
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('source-webspace', InputArgument::REQUIRED)
            ->addArgument('source-locale', InputArgument::REQUIRED)
            ->addArgument('destination-webspace', InputArgument::REQUIRED)
            ->addArgument('destination-locale', InputArgument::REQUIRED)
            ->addOption('clear-destination-webspace');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $webspaceKeySource = $input->getArgument('source-webspace');
        $localesSource = \explode(',', $input->getArgument('source-locale'));
        $webspaceKeyDestination = $input->getArgument('destination-webspace');
        $localesDestination = \explode(',', $input->getArgument('destination-locale'));

        if (\count($localesSource) !== \count($localesDestination)) {
            $output->writeln([
                '<error>Aborted!</error>',
                '<comment>Provide correct source and destination locales</comment>',
            ]);

            return -1;
        }

        $localesPairs = [];
        for ($i = 0; $i < \count($localesSource); ++$i) {
            $localesPairs[] = $localesSource[$i] . ' => ' . $localesDestination[$i];
        }

        $output->writeln([
            '<info>Webspace Copy</info>',
            '<info>==============================</info>',
            '',
            '<info>Options</info>',
            '------------------------------',
            'Webspace: ' . $webspaceKeySource . ' => ' . $webspaceKeyDestination,
            'Locales: ' . \implode(', ', $localesPairs),
            '------------------------------',
            '',
        ]);

        $output->writeln([
            '<info>==============================</info>',
            '<info>ATTENTION</info>',
            '<info>The whole destination webspace (' . $webspaceKeyDestination . ') will be deleted!</info>',
            '<info>==============================</info>',
            '',
        ]);

        if (true !== $input->getOption('clear-destination-webspace')) {
            $output->writeln([
                '<error>==============================',
                '<error>Aborted!</error>',
                '<error>This command currently does not work if there is already data in the webspace. You can run the command with --clear-destination-webspace to remove all the content from the destination webspace.</error>',
                '<error>==============================</error>',
            ]);

            return -1;
        }

        $this->webspaceKeySource = $webspaceKeySource;
        $this->webspaceKeyDestination = $webspaceKeyDestination;

        $this->output = $output;

        $this->output->writeln([
            '==============================',
            '1. Clear destination webspace',
            '------------------------------',
        ]);
        $this->clearDestinationWebspace();
        $this->output->writeln([
            '------------------------------',
            '',
        ]);

        $this->output->writeln([
            '==============================',
            '2. Copy pages to destination webspace',
        ]);
        for ($i = 0; $i < \count($localesSource); ++$i) {
            $this->copyWebspace(
                $localesSource[$i],
                $localesDestination[$i]
            );
        }

        $this->output->writeln([
            '==============================',
            '3. Copy redirects and structure',
        ]);
        for ($i = 0; $i < \count($localesSource); ++$i) {
            $this->copyRedirectsAndStructure(
                $localesSource[$i],
                $localesDestination[$i]
            );
        }

        $this->output->writeln('<info>Done</info>');

        return 0;
    }

    /**
     * Removes all pages from given webspace.
     */
    protected function clearDestinationWebspace()
    {
        $homeDocument = $this->documentManager->find(
            $this->sessionManager->getContentPath($this->webspaceKeyDestination)
        );
        foreach ($homeDocument->getChildren() as $child) {
            $this->output->writeln('<info>Processing: </info>' . $child->getPath());
            $this->documentManager->remove($child, [
                PageRemoveSubscriber::FORCE_REMOVE_CHILDREN_OPTION => true,
            ]);
            $this->documentManager->flush();
        }
    }

    /**
     * Copies a given webspace with given locale to a destination webspace with a destination locale.
     *
     * @param string $localeSource
     * @param string $localeDestination
     */
    protected function copyWebspace($localeSource, $localeDestination)
    {
        $this->output->writeln([
            '------------------------------',
            '<info>Webspace: </info>' . $this->webspaceKeySource . ' => ' . $this->webspaceKeyDestination,
            '<info>Locale: </info>' . $localeSource . ' => ' . $localeDestination,
            '------------------------------',
        ]);

        /** @var HomeDocument $homeDocumentSource */
        $homeDocumentSource = $this->documentManager->find(
            $this->sessionManager->getContentPath($this->webspaceKeySource),
            $localeSource
        );

        // Generate all needed page documents.
        $this->recursiveCopy(
            $homeDocumentSource,
            null,
            $localeDestination
        );

        $this->output->writeln([
            '------------------------------',
            '',
        ]);
    }

    /**
     * Copies a given webspace with given locale to a destination webspace with a destination locale.
     *
     * @param string $localeSource
     * @param string $localeDestination
     */
    protected function copyRedirectsAndStructure(
        $localeSource,
        $localeDestination
    ) {
        $this->output->writeln([
            '------------------------------',
            '<info>Webspace: </info>' . $this->webspaceKeySource . ' => ' . $this->webspaceKeyDestination,
            '<info>Locale: </info>' . $localeSource . ' => ' . $localeDestination,
            '------------------------------',
        ]);

        /** @var HomeDocument $homeDocumentSource */
        $homeDocumentSource = $this->documentManager->find(
            $this->sessionManager->getContentPath($this->webspaceKeySource),
            $localeSource
        );

        // Generate redirect and structure.
        $this->recursiveCopyRedirectsAndStructure(
            $homeDocumentSource,
            $localeDestination
        );

        $this->output->writeln([
            '------------------------------',
            '',
        ]);
    }

    /**
     * @param string $localeDestination
     */
    protected function recursiveCopy(
        BasePageDocument $documentSource,
        ?BasePageDocument $parentDocumentDestination,
        $localeDestination
    ) {
        if (LocalizationState::GHOST === $this->documentInspector->getLocalizationState($documentSource)) {
            $this->io->warning('Can not copy ghost page and its possible children: ' . $documentSource->getPath());

            return;
        }

        $newPath = \str_replace(
            $this->sessionManager->getContentPath($this->webspaceKeySource),
            $this->sessionManager->getContentPath($this->webspaceKeyDestination),
            $documentSource->getPath()
        );

        $this->output->writeln('<info>Processing: </info>' . $documentSource->getPath() . ' => ' . $newPath);

        /* @var BasePageDocument $documentDestination */
        try {
            $documentDestination = $this->documentManager->find($newPath, $localeDestination);
        } catch (DocumentNotFoundException $exception) {
            $documentDestination = $this->documentManager->create('page');
        }

        // Set data.
        $documentDestination->setTitle($documentSource->getTitle());
        $documentDestination->setStructureType($documentSource->getStructureType());
        $documentDestination->setWorkflowStage(WorkflowStage::TEST);
        $documentDestination->setExtensionsData($documentSource->getExtensionsData());
        $documentDestination->setResourceSegment($documentSource->getResourceSegment());
        $documentDestination->setPermissions($documentSource->getPermissions());
        $documentDestination->setSuluOrder($documentSource->getSuluOrder());
        $documentDestination->setNavigationContexts($documentSource->getNavigationContexts());
        $documentDestination->setShadowLocaleEnabled($documentSource->isShadowLocaleEnabled());
        $documentDestination->setShadowLocale($documentSource->getShadowLocale());

        // Set parent.
        if ($documentDestination instanceof PageDocument) {
            $documentDestination->setParent($parentDocumentDestination);
        }

        $this->saveDocument($documentDestination, $localeDestination, $newPath);

        foreach ($documentSource->getChildren() as $child) {
            $this->recursiveCopy(
                $child,
                $documentDestination,
                $localeDestination
            );
        }
    }

    /**
     * @param string $localeDestination
     */
    protected function recursiveCopyRedirectsAndStructure(
        BasePageDocument $documentSource,
        $localeDestination
    ) {
        if (LocalizationState::LOCALIZED === $this->documentInspector->getLocalizationState($documentSource)) {
            $newPath = \str_replace(
                $this->sessionManager->getContentPath($this->webspaceKeySource),
                $this->sessionManager->getContentPath($this->webspaceKeyDestination),
                $documentSource->getPath()
            );

            $this->output->writeln('<info>Processing: </info>' . $documentSource->getPath() . ' => ' . $newPath);

            try {
                /** @var PageDocument $documentDestination */
                $documentDestination = $this->documentManager->find($newPath, $localeDestination);

                // Copy the redirects and correct the target.
                switch ($documentSource->getRedirectType()) {
                    case RedirectType::INTERNAL:
                        $newPathTarget = \str_replace(
                            $this->sessionManager->getContentPath($this->webspaceKeySource),
                            $this->sessionManager->getContentPath($this->webspaceKeyDestination),
                            $documentSource->getRedirectTarget()->getPath()
                        );

                        $documentDestination->setRedirectType(RedirectType::INTERNAL);
                        $documentDestination->setRedirectTarget(
                            $this->documentManager->find($newPathTarget, $localeDestination)
                        );
                        break;
                    case RedirectType::EXTERNAL:
                        $documentDestination->setRedirectType(RedirectType::EXTERNAL);
                        $documentDestination->setRedirectExternal($documentDestination->getRedirectExternal());
                        break;
                }

                // Copy the structure and correct the target of references.
                $newStructure = $documentSource->getStructure()->toArray();
                $metadata = $this->documentInspector->getStructureMetadata($documentSource);
                foreach ($metadata->getProperties() as $property) {
                    $this->processContentType(
                        $property,
                        $newStructure,
                        $documentSource->getLocale(),
                        $localeDestination
                    );
                }
                $documentDestination->getStructure()->bind($newStructure);

                // Save new document.
                $this->saveDocument($documentDestination, $localeDestination);
            } catch (DocumentNotFoundException $e) {
                // Do nothing.
            }
        }

        foreach ($documentSource->getChildren() as $child) {
            $this->recursiveCopyRedirectsAndStructure(
                $child,
                $localeDestination
            );
        }
    }

    /**
     * @param string $localeSource
     * @param string $localeDestination
     */
    protected function processContentType(
        ItemMetadata $property,
        array &$structureArray,
        $localeSource,
        $localeDestination
    ) {
        switch ($property->getType()) {
            case 'smart_content':
                $this->updateSmartContentStructure(
                    $structureArray,
                    $property,
                    $localeSource,
                    $localeDestination
                );
                break;
            case 'text_editor':
                $this->updateHtmlSuluLinks(
                    $structureArray,
                    $property,
                    $localeSource,
                    $localeDestination
                );
                break;
            case 'page_selection':
                $this->updatePageSelection(
                    $structureArray,
                    $property,
                    $localeSource,
                    $localeDestination
                );
                break;
            case 'single_page_selection':
                $this->updateSinglePageSelection(
                    $structureArray,
                    $property,
                    $localeSource,
                    $localeDestination
                );
                break;
            case 'block':
                $this->updateBlocksStructure(
                    $structureArray,
                    $property,
                    $localeSource,
                    $localeDestination
                );
                break;
            case 'teaser_selection':
                $this->updateTeaserSelection(
                    $structureArray,
                    $property,
                    $localeSource,
                    $localeDestination
                );
                break;
        }
    }

    /**
     * Process content type block.
     *
     * @param string $localeSource
     * @param string $localeDestination
     */
    protected function updateBlocksStructure(
        array &$structureArray,
        BlockMetadata $property,
        $localeSource,
        $localeDestination
    ) {
        if (!\array_key_exists($property->getName(), $structureArray) || !$structureArray[$property->getName()]) {
            return;
        }

        foreach ($structureArray[$property->getName()] as &$structure) {
            /** @var ItemMetadata $component */
            $component = $property->getComponentByName($structure['type']);
            /** @var PropertyMetadata $child */
            foreach ($component->getChildren() as $child) {
                if ($structure[$child->getName()]) {
                    $this->processContentType(
                        $child,
                        $structure,
                        $localeSource,
                        $localeDestination
                    );
                }
            }
        }
    }

    /**
     * Updates the smart content structure when the property `dataSource` is set and the target is in the same webspace.
     *
     * @param string $localeSource
     * @param string $localeDestination
     */
    protected function updateSmartContentStructure(
        array &$structureArray,
        PropertyMetadata $property,
        $localeSource,
        $localeDestination
    ) {
        /** @var PropertyParameter $parameter */
        foreach ($property->getParameters() as $parameter) {
            if (!\array_key_exists($property->getName(), $structureArray)) {
                continue;
            }

            if ('provider' !== $parameter['name'] || 'content' !== $parameter['value']) {
                continue;
            }

            if (!\array_key_exists('dataSource', $structureArray[$property->getName()])) {
                continue;
            }

            $targetDocumentDestination = $this->getTargetDocumentDestination(
                $structureArray[$property->getName()]['dataSource'],
                $localeSource,
                $localeDestination
            );

            if (!$targetDocumentDestination) {
                continue;
            }

            $structureArray[$property->getName()]['dataSource'] = $targetDocumentDestination->getUuid();
        }
    }

    /**
     * Updates references in structure for content type `teaser_selection`.
     *
     * @param string $localeSource
     * @param string $localeDestination
     */
    protected function updateTeaserSelection(
        array &$structureArray,
        PropertyMetadata $property,
        $localeSource,
        $localeDestination
    ) {
        if (!isset($structureArray[$property->getName()]['items'])) {
            return;
        }

        foreach ($structureArray[$property->getName()]['items'] as $key => $teaserItem) {
            if ('content' !== $teaserItem['type']) {
                continue;
            }

            $targetDocumentDestination = $this->getTargetDocumentDestination(
                $teaserItem['id'],
                $localeSource,
                $localeDestination
            );

            if (!$targetDocumentDestination) {
                continue;
            }

            $structureArray[$property->getName()]['items'][$key]['id'] = $targetDocumentDestination->getUuid();
        }
    }

    /**
     * @param string $localeSource
     * @param string $localeDestination
     */
    protected function updateHtmlSuluLinks(
        array &$structureArray,
        PropertyMetadata $property,
        $localeSource,
        $localeDestination
    ) {
        if (!\array_key_exists($property->getName(), $structureArray) || !$structureArray[$property->getName()]) {
            return;
        }

        if (!\strpos($structureArray[$property->getName()], 'sulu-link')) {
            return;
        }

        /** @var TagMatchGroup[] $tagMatchGroups */
        $tagMatchGroups = $this->htmlTagExtractor->extract($structureArray[$property->getName()]);

        foreach ($tagMatchGroups as $tagMatchGroup) {
            if ('sulu' === $tagMatchGroup->getNamespace() && 'link' === $tagMatchGroup->getTagName()) {
                foreach ($tagMatchGroup->getTags() as $tag) {
                    if ('page' !== $tag['provider']) {
                        continue;
                    }

                    $targetUuid = $tag['href'];

                    $targetDocumentDestination = $this->getTargetDocumentDestination(
                        $targetUuid,
                        $localeSource,
                        $localeDestination
                    );

                    if (!$targetDocumentDestination) {
                        continue;
                    }

                    $structureArray[$property->getName()] = \str_replace(
                        $targetUuid,
                        $targetDocumentDestination->getUuid(),
                        $structureArray[$property->getName()]
                    );
                }
            }
        }
    }

    /**
     * Updates references in structure for content type `page_selection`.
     *
     * @param string $localeSource
     * @param string $localeDestination
     */
    protected function updatePageSelection(
        array &$structureArray,
        PropertyMetadata $property,
        $localeSource,
        $localeDestination
    ) {
        if (!\array_key_exists($property->getName(), $structureArray) || !$structureArray[$property->getName()]) {
            return;
        }

        foreach ($structureArray[$property->getName()] as $key => $value) {
            $targetDocumentDestination = $this->getTargetDocumentDestination(
                $value,
                $localeSource,
                $localeDestination
            );

            if (!$targetDocumentDestination) {
                continue;
            }

            $structureArray[$property->getName()][$key] = $targetDocumentDestination->getUuid();
        }
    }

    /**
     * Updates references in structure for content type `single_page_selection`.
     *
     * @param string $localeSource
     * @param string $localeDestination
     */
    protected function updateSinglePageSelection(
        array &$structureArray,
        PropertyMetadata $property,
        $localeSource,
        $localeDestination
    ) {
        if (!\array_key_exists($property->getName(), $structureArray) || !$structureArray[$property->getName()]) {
            return;
        }

        $targetDocumentDestination = $this->getTargetDocumentDestination(
            $structureArray[$property->getName()],
            $localeSource,
            $localeDestination
        );

        if (!$targetDocumentDestination) {
            return;
        }

        $structureArray[$property->getName()] = $targetDocumentDestination->getUuid();
    }

    /**
     * @param string $uuid
     * @param string $localeSource
     * @param string $localeDestination
     *
     * @return null|BasePageDocument
     */
    protected function getTargetDocumentDestination(
        $uuid,
        $localeSource,
        $localeDestination
    ) {
        /** @var BasePageDocument $targetDocumentSource */
        $targetDocumentSource = $this->documentManager->find($uuid, $localeSource);

        if ($this->webspaceKeySource !== $targetDocumentSource->getWebspaceName()) {
            return null;
        }

        $newPathTarget = \str_replace(
            $this->sessionManager->getContentPath($this->webspaceKeySource),
            $this->sessionManager->getContentPath($this->webspaceKeyDestination),
            $targetDocumentSource->getPath()
        );

        $targetDocument = null;

        try {
            $targetDocument = $this->documentManager->find($newPathTarget, $localeDestination);
        } catch (DocumentNotFoundException $e) {
            return null;
        }

        return $targetDocument;
    }

    /**
     * @param string $locale
     * @param string|null $path
     */
    protected function saveDocument(BasePageDocument $document, $locale, $path = null)
    {
        $persistOptions = [];

        if ($path) {
            $persistOptions['path'] = $path;
        }

        $this->documentManager->persist($document, $locale, $persistOptions);
        $this->documentManager->flush();
    }
}
