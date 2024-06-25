<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Sulu\Bundle\PageBundle\Content\PageSelectionContainer;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Util\ArrayableInterface;

/**
 * content type for internal links selection.
 */
class PageSelection extends ComplexContentType implements ContentTypeExportInterface, PreResolvableContentTypeInterface
{
    /**
     * @param bool $showDrafts
     * @param mixed[] $permissions
     */
    public function __construct(
        private ContentQueryExecutorInterface $contentQueryExecutor,
        private ContentQueryBuilderInterface $contentQueryBuilder,
        private ReferenceStoreInterface $referenceStore,
        private $showDrafts,
        private $permissions = null,
        private array $enabledTwigAttributes = [
            'path' => true,
        ]
    ) {
        if ($this->enabledTwigAttributes['path'] ?? true) {
            @trigger_deprecation('sulu/sulu', '2.3', 'Enabling the "path" parameter is deprecated.');
        }
    }

    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $data = [];
        if ($node->hasProperty($property->getName())) {
            $data = $node->getProperty($property->getName())->getString();
        }

        $refs = $data ?? [];
        $property->setValue($refs);
    }

    public function getDefaultParams(?PropertyInterface $property = null)
    {
        return ['properties' => new PropertyParameter('properties', [], 'collection')];
    }

    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();
        if ($value instanceof ArrayableInterface) {
            $value = $value->toArray();
        }

        if (isset($value)) {
            // remove not existing ids
            $session = $node->getSession();
            $selectedNodes = $session->getNodesByIdentifier($value);
            $ids = [];
            foreach ($selectedNodes as $selectedNode) {
                if ($selectedNode->getIdentifier() === $node->getIdentifier()) {
                    throw new \InvalidArgumentException('You are not allowed to link a page to itself!');
                }
                $ids[] = $selectedNode->getIdentifier();
            }
            $value = $ids;
        }

        // set value to node
        $node->setProperty($property->getName(), $value, PropertyType::REFERENCE);
    }

    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    public function getContentData(PropertyInterface $property)
    {
        $data = $property->getValue();
        $container = new PageSelectionContainer(
            isset($data) ? $data : [],
            $this->contentQueryExecutor,
            $this->contentQueryBuilder,
            \array_merge($this->getDefaultParams(), $property->getParams()),
            $property->getStructure()->getWebspaceKey(),
            $property->getStructure()->getLanguageCode(),
            $this->showDrafts,
            $this->permissions[PermissionTypes::VIEW],
            $this->enabledTwigAttributes
        );

        return $container->getData();
    }

    public function exportData($propertyValue)
    {
        if (!\is_array($propertyValue) || empty($propertyValue)) {
            return '';
        }

        return \json_encode($propertyValue);
    }

    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $property->setValue(\json_decode($value));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    public function preResolve(PropertyInterface $property)
    {
        $uuids = $property->getValue();
        if (!\is_array($uuids)) {
            return;
        }

        foreach ($uuids as $uuid) {
            $this->referenceStore->add($uuid);
        }
    }
}
