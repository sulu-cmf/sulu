<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;

/**
 * Content Type for target groups from the audience targeting.
 */
class TargetGroupSelection extends ComplexContentType implements ContentTypeExportInterface
{
    /**
     * Responsible for persisting the categories in the database.
     */
    private \Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface $targetGroupRepository;

    public function __construct(TargetGroupRepositoryInterface $targetGroupRepository)
    {
        $this->targetGroupRepository = $targetGroupRepository;
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $ids = $node->getPropertyValueWithDefault($property->getName(), []);
        $property->setValue($ids);
    }

    public function getContentData(PropertyInterface $property)
    {
        $audienceTargetGroupIds = $property->getValue();
        if (!\is_array($audienceTargetGroupIds) || empty($audienceTargetGroupIds)) {
            return [];
        }

        return $this->targetGroupRepository->findByIds($audienceTargetGroupIds);
    }

    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $node->setProperty($property->getName(), $property->getValue());
    }

    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    public function exportData($propertyValue)
    {
        if (\is_array($propertyValue) && \count($propertyValue) > 0) {
            return \json_encode($propertyValue);
        }

        return \json_encode([]);
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
}
