<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Repository;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Types\Rlp\ResourceLocatorInformation;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * resource locator repository.
 */
class ResourceLocatorRepository implements ResourceLocatorRepositoryInterface
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var RlpStrategyInterface
     */
    private $rlpStrategy;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var string[]
     */
    private $apiBasePath = [
        '/admin/api/node/resourcelocator',
        '/admin/api/nodes/resourcelocators',
        '/admin/api/nodes/{uuid}/resourcelocators',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        RlpStrategyInterface $rlpStrategy,
        StructureManagerInterface $structureManager,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector
    ) {
        $this->rlpStrategy = $rlpStrategy;
        $this->structureManager = $structureManager;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($parts, $parentUuid, $uuid, $webspaceKey, $languageCode, $templateKey, $segmentKey = null)
    {
        /** @var StructureInterface $structure */
        $structure = $this->structureManager->getStructure($templateKey);
        $title = $this->implodeRlpParts($structure, $parts);

        if ($parentUuid !== null) {
            $parentDocument = $this->documentManager->find($parentUuid, $languageCode);
            $parentPath = $this->rlpStrategy->loadByContent($this->getNonGhostAncestor($parentDocument));
            $result = $this->rlpStrategy->generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey);
        } elseif ($uuid !== null) {
            $result = $this->rlpStrategy->generateForUuid($title, $uuid, $webspaceKey, $languageCode, $segmentKey);
        } else {
            $parentPath = '/';
            $result = $this->rlpStrategy->generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey);
        }

        return [
            'resourceLocator' => $result,
            '_links' => [
                'self' => $this->getBasePath() . '/generates',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getHistory($uuid, $webspaceKey, $languageCode)
    {
        $urls = $this->rlpStrategy->loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode);

        $result = [];
        /** @var ResourceLocatorInformation $url */
        foreach ($urls as $url) {
            $defaultParameter = '&language=' . $languageCode . '&webspace=' . $webspaceKey;
            $deleteParameter = '?path=' . $url->getResourceLocator() . $defaultParameter;

            $result[] = [
                'id' => $url->getId(),
                'resourceLocator' => $url->getResourceLocator(),
                'created' => $url->getCreated(),
                '_links' => [
                    'delete' => $this->getBasePath(null, 0) . $deleteParameter,
                ],
            ];
        }

        return [
            '_embedded' => [
                'resourcelocators' => $result,
            ],
            '_links' => [
                'self' => $this->getBasePath($uuid) . '/history?language=' . $languageCode . '&webspace=' . $webspaceKey,
            ],
            'total' => count($result),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $this->rlpStrategy->deleteByPath($path, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * returns base path fo given uuid.
     *
     * @param null|string $uuid
     * @param int         $default
     *
     * @return string
     */
    private function getBasePath($uuid = null, $default = 1)
    {
        if ($uuid !== null) {
            return str_replace('{uuid}', $uuid, $this->apiBasePath[2]);
        } else {
            return $this->apiBasePath[$default];
        }
    }

    /**
     * @param StructureInterface $structure
     * @param array              $parts
     * @param string             $separator default '-'
     *
     * @return string
     */
    private function implodeRlpParts(StructureInterface $structure, array $parts, $separator = '-')
    {
        $title = '';
        // concat rlp parts in sort of priority
        foreach ($structure->getPropertiesByTagName('sulu.rlp.part') as $property) {
            $title = $parts[$property->getName()] . $separator . $title;
        }
        $title = substr($title, 0, -1);

        return $title;
    }

    /**
     * Returns the first ancestor-or-self of the given document which is not a ghost-document.
     * If all ancestor documents are ghost-documents, the root document is returned.
     *
     * @param object    $document
     *
     * @return object
     */
    private function getNonGhostAncestor($document)
    {
        while ($this->documentInspector->getLocalizationState($document) === LocalizationState::GHOST
            && $document instanceof ParentBehavior
        ) {
            $document = $document->getParent();
        }

        return $document;
    }
}
