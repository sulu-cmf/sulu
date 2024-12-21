<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AnyOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NumberMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ObjectMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType\ReferenceContentTypeInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class SingleMediaSelection extends SimpleContentType implements PreResolvableContentTypeInterface, PropertyMetadataMapperInterface, ReferenceContentTypeInterface
{
    public function __construct(
        private MediaManagerInterface $mediaManager,
        private ReferenceStoreInterface $mediaReferenceStore,
        private RequestAnalyzerInterface $requestAnalyzer,
        private ?SecurityCheckerInterface $securityChecker = null
    ) {
        parent::__construct('SingleMediaSelection', '{"id": null}');
    }

    public function getContentData(PropertyInterface $property): ?Media
    {
        $data = $property->getValue();
        if (!isset($data['id'])) {
            return null;
        }

        try {
            $entity = $this->mediaManager->getById($data['id'], $property->getStructure()->getLanguageCode());
        } catch (MediaNotFoundException $e) {
            return null;
        }

        $webspace = $this->requestAnalyzer->getWebspace();

        if ($webspace
            && $webspace->hasWebsiteSecurity()
            && $this->securityChecker
            && !$this->securityChecker->hasPermission(
                new SecurityCondition(
                    MediaAdmin::SECURITY_CONTEXT,
                    $property->getStructure()->getLanguageCode(),
                    Collection::class,
                    $entity->getCollection()
                ),
                PermissionTypes::VIEW
            )
        ) {
            return null;
        }

        return $entity;
    }

    public function getViewData(PropertyInterface $property)
    {
        return $property->getValue();
    }

    public function exportData($propertyValue)
    {
        if (!\is_array($propertyValue)) {
            return '';
        }

        if (!empty($propertyValue)) {
            return \json_encode($propertyValue) ?: '';
        }

        return '';
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
        $property->setValue(\json_decode($value, true));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    public function preResolve(PropertyInterface $property)
    {
        $data = $property->getValue();
        if (!isset($data['id'])) {
            return;
        }

        $this->mediaReferenceStore->add($data['id']);
    }

    protected function encodeValue($value)
    {
        return \json_encode($value);
    }

    protected function decodeValue($value)
    {
        if (!\is_string($value)) {
            return null;
        }

        return \json_decode($value, true);
    }

    public function mapPropertyMetadata(ContentPropertyMetadata $propertyMetadata): PropertyMetadata
    {
        $mandatory = $propertyMetadata->isRequired();

        $idMetadata = new NumberMetadata();

        if (!$mandatory) {
            $idMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $idMetadata,
            ]);
        }

        $singleMediaSelectionMetadata = new ObjectMetadata([
            new PropertyMetadata('id', $mandatory, $idMetadata),
            new PropertyMetadata('displayOption', false, new StringMetadata()),
        ]);

        if (!$mandatory) {
            $singleMediaSelectionMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $singleMediaSelectionMetadata,
            ]);
        }

        return new PropertyMetadata($propertyMetadata->getName(), $mandatory, $singleMediaSelectionMetadata);
    }

    public function getReferences(PropertyInterface $property, ReferenceCollectorInterface $referenceCollector, string $propertyPrefix = ''): void
    {
        $data = $property->getValue();
        if (!\is_array($data) || !isset($data['id']) || !\is_int($data['id'])) {
            return;
        }

        $referenceCollector->addReference(
            MediaInterface::RESOURCE_KEY,
            (string) $data['id'],
            $propertyPrefix . $property->getName()
        );
    }
}
