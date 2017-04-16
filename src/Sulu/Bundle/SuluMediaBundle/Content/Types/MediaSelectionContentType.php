<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Content\Types;

use JMS\Serializer\Serializer;
use PHPCR\NodeInterface;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\PropertyInterface;

/**
 * content type for image selection
 */
class MediaSelectionContentType extends ComplexContentType
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var string
     */
    private $template;

    /**
     * @var Serializer
     */
    private $serializer;

    function __construct($mediaManager, $serializer, $template)
    {
        $this->mediaManager = $mediaManager;
        $this->serializer = $serializer;
        $this->template = $template;
    }

    /**
     * @return array
     */
    public function getDefaultParams()
    {
        return array(
            'types' => null
        );
    }

    /**
     * @param $params
     * @return array
     */
    public function getParams($params)
    {
        return array_merge($this->getDefaultParams(), $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::PRE_SAVE;
    }

    /**
     * {@inheritdoc}
     */
    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $data = json_decode($node->getPropertyValueWithDefault($property->getName(), '{}'), true);

        $this->setData($data, $property, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview(
        $data,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $this->setData($data, $property, $languageCode);
    }

    /**
     * set data to property
     * @param string[] $data ids of images
     * @param PropertyInterface $property
     * @param $languageCode
     */
    private function setData($data, PropertyInterface $property, $languageCode)
    {
        if ($data instanceof MediaSelectionContainer) {
            $container = $data;
        } else {
            $params = $this->getParams($property->getParams());
            $types = $params['types'];
            $container = new MediaSelectionContainer(
                isset($data['config']) ?  $data['config'] : array(),
                isset($data['displayOption']) ? $data['displayOption'] : '',
                isset($data['ids']) ? $data['ids'] : array(),
                $languageCode,
                $types,
                $this->mediaManager,
                $this->serializer
            );
        }

        $property->setValue($container);
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();

        // if whole smart-content container is pushed
        if (isset($value['data'])) {
            unset($value['data']);
        }

        // set value to node
        $node->setProperty($property->getName(), json_encode($value));
    }

    /**
     * {@inheritdoc}
     */
    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $node->getProperty($property->getName())->remove();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
