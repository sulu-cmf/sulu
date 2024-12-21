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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ArrayMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\EmptyArrayMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NumberMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ObjectMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType\ReferenceContentTypeInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Util\ArrayableInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * content type for image selection.
 */
class MediaSelectionContentType extends ComplexContentType implements ContentTypeExportInterface, PreResolvableContentTypeInterface, PropertyMetadataMapperInterface, ReferenceContentTypeInterface
{
    /**
     * @param array<string,int>|null $permissions;
     */
    public function __construct(
        private MediaManagerInterface $mediaManager,
        private ReferenceStoreInterface $referenceStore,
        private ?RequestAnalyzerInterface $requestAnalyzer = null,
        private $permissions = null,
        private ?PropertyMetadataMinMaxValueResolver $propertyMetadataMinMaxValueResolver = null
    ) {
    }

    public function getDefaultParams(?PropertyInterface $property = null)
    {
        return [
            'types' => new PropertyParameter('types', null),
            'formats' => new PropertyParameter('formats', []),
        ];
    }

    /**
     * @param array $params
     *
     * @return PropertyParameter[]
     */
    public function getParams($params)
    {
        return \array_merge($this->getDefaultParams(), $params);
    }

    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $data = \json_decode($node->getPropertyValueWithDefault($property->getName(), '{"ids": []}'), true);

        $property->setValue(isset($data['ids']) ? $data : null);
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

        // if whole smart-content container is pushed
        if (isset($value['data'])) {
            unset($value['data']);
        }

        // set value to node
        $node->setProperty($property->getName(), \json_encode($value));
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

        $params = $this->getParams($property->getParams());
        $types = $params['types']->getValue();

        $webspace = $this->requestAnalyzer?->getWebspace();

        $container = new MediaSelectionContainer(
            isset($data['config']) ? $data['config'] : [],
            isset($data['displayOption']) ? $data['displayOption'] : '',
            isset($data['ids']) ? $data['ids'] : [],
            $property->getStructure()->getLanguageCode(),
            $types,
            $this->mediaManager,
            $webspace && $webspace->hasWebsiteSecurity() ? $this->permissions[PermissionTypes::VIEW] : null
        );

        return $container->getData();
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
            return \json_encode($propertyValue);
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
        if (!isset($data['ids']) || !\is_array($data['ids'])) {
            return;
        }

        foreach ($data['ids'] as $id) {
            $this->referenceStore->add($id);
        }
    }

    public function mapPropertyMetadata(ContentPropertyMetadata $propertyMetadata): PropertyMetadata
    {
        $mandatory = $propertyMetadata->isRequired();

        $minMaxValue = (object) [
            'min' => null,
            'max' => null,
        ];

        if (null !== $this->propertyMetadataMinMaxValueResolver) {
            $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);
        }

        $idsMetadata = new ArrayMetadata(
            new NumberMetadata(),
            $minMaxValue->min,
            $minMaxValue->max,
            true
        );

        if (!$mandatory) {
            $idsMetadata = new AnyOfsMetadata([
                new EmptyArrayMetadata(),
                $idsMetadata,
            ]);
        }

        $mediaSelectionMetadata = new ObjectMetadata([
            new PropertyMetadata('ids', $mandatory, $idsMetadata),
            new PropertyMetadata('displayOption', false, new StringMetadata()),
        ]);

        if (!$mandatory) {
            $mediaSelectionMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $mediaSelectionMetadata,
            ]);
        }

        return new PropertyMetadata($propertyMetadata->getName(), $mandatory, $mediaSelectionMetadata);
    }

    public function getReferences(PropertyInterface $property, ReferenceCollectorInterface $referenceCollector, string $propertyPrefix = ''): void
    {
        $data = $property->getValue();

        if ($data instanceof MediaSelectionContainer) { // TODO should probably be removed when tests are refactored
            $data = $data->toArray();
        }

        if (!\is_array($data) || !isset($data['ids']) || !\is_array($data['ids'])) {
            return;
        }

        foreach ($data['ids'] as $id) {
            if (!\is_int($id)) {
                continue;
            }

            $referenceCollector->addReference(
                MediaInterface::RESOURCE_KEY,
                (string) $id,
                $propertyPrefix . $property->getName()
            );
        }
    }
}
