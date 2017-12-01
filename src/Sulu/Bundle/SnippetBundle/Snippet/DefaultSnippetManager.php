<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Settings\SettingsManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

/**
 * Manages default snippets.
 */
class DefaultSnippetManager implements DefaultSnippetManagerInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SettingsManagerInterface
     */
    private $settingsManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentRegistry
     */
    private $registry;

    /**
     * @var FrozenParameterBag
     */
    private $areas;

    public function __construct(
        SettingsManagerInterface $settingsManager,
        DocumentManagerInterface $documentManager,
        WebspaceManagerInterface $webspaceManager,
        DocumentRegistry $registry,
        array $areas
    ) {
        $this->settingsManager = $settingsManager;
        $this->documentManager = $documentManager;
        $this->webspaceManager = $webspaceManager;
        $this->registry = $registry;
        $this->areas = new FrozenParameterBag($areas);
    }

    /**
     * {@inheritdoc}
     */
    public function save($webspaceKey, $type, $uuid, $locale)
    {
        /** @var SnippetDocument $document */
        $document = $this->documentManager->find($uuid, $locale);

        if (!$document) {
            throw new SnippetNotFoundException($uuid);
        }

        if (!$this->checkTemplate($document, $type)) {
            throw new WrongSnippetTypeException($document->getStructureType(), $type, $document);
        }

        $this->settingsManager->save(
            $webspaceKey,
            'snippets-' . $type,
            $this->registry->getNodeForDocument($document)
        );

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($webspaceKey, $type)
    {
        $this->settingsManager->remove($webspaceKey, 'snippets-' . $type);
    }

    /**
     * {@inheritdoc}
     */
    public function load($webspaceKey, $type, $locale)
    {
        $snippetNode = $this->settingsManager->load($webspaceKey, 'snippets-' . $type);

        if (null === $snippetNode) {
            return;
        }

        $uuid = $snippetNode->getIdentifier();
        /** @var SnippetDocument $document */
        $document = $this->documentManager->find($uuid, $locale);

        if (null !== $document && !$this->checkTemplate($document, $type)) {
            throw new WrongSnippetTypeException($document->getStructureType(), $type, $document);
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function isDefault($uuid)
    {
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $settings = $this->settingsManager->loadStringByWildcard($webspace->getKey(), 'snippets-*');

            if (in_array($uuid, $settings)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function loadIdentifier($webspaceKey, $type)
    {
        $area = $this->areas->get($type);

        return $this->settingsManager->loadString($webspaceKey, 'snippets-' . $area['key']);
    }

    /**
     * Check template.
     *
     * @param SnippetDocument $document
     * @param string $type
     *
     * @return bool
     */
    private function checkTemplate($document, $type)
    {
        $area = $this->areas->get($type);

        return $document->getStructureType() === $area['template'];
    }
}
