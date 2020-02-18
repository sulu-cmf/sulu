<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Teaser;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreNotExistsException;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * Provides content-type for selecting teasers.
 */
class TeaserContentType extends SimpleContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    /**
     * @var TeaserManagerInterface
     */
    private $teaserManager;

    /**
     * @var ReferenceStorePoolInterface
     */
    private $referenceStorePool;

    /**
     * @param string $template
     */
    public function __construct(
        $template,
        TeaserProviderPoolInterface $providerPool,
        TeaserManagerInterface $teaserManager,
        ReferenceStorePoolInterface $referenceStorePool
    ) {
        parent::__construct('teaser_selection');

        $this->template = $template;
        $this->teaserProviderPool = $providerPool;
        $this->teaserManager = $teaserManager;
        $this->referenceStorePool = $referenceStorePool;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [
            'providerConfiguration' => $this->teaserProviderPool->getConfiguration(),
            'present_as' => new PropertyParameter('present_as', [], 'collection'),
        ];
    }

    protected function decodeValue($value)
    {
        return json_decode($value, true);
    }

    protected function encodeValue($value)
    {
        return json_encode($value);
    }

    public function getContentData(PropertyInterface $property)
    {
        $items = $this->getItems($property);
        if (0 === count($items)) {
            return [];
        }

        $result = $this->teaserManager->find($items, $property->getStructure()->getLanguageCode());

        $mediaReferenceStore = $this->getMediaReferenceStore();
        if (!$mediaReferenceStore) {
            return $result;
        }

        foreach ($result as $item) {
            if ($item->getMediaId()) {
                $mediaReferenceStore->add($item->getMediaId());
            }
        }

        return $result;
    }

    /**
     * @return ReferenceStoreInterface|null
     */
    private function getMediaReferenceStore()
    {
        try {
            return $this->referenceStorePool->getStore('media');
        } catch (ReferenceStoreNotExistsException $exception) {
            return null;
        }
    }

    public function getViewData(PropertyInterface $property)
    {
        return $this->getValue($property);
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
        if (!empty($value)) {
            $value = json_decode($value);
        }

        parent::importData($node, $property, $value, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    public function preResolve(PropertyInterface $property)
    {
        foreach ($this->getItems($property) as $item) {
            try {
                $this->referenceStorePool->getStore($item['type'])->add($item['id']);
            } catch (ReferenceStoreNotExistsException $exception) {
                // ignore not existing stores
            }
        }
    }

    /**
     * Returns items.
     *
     * @return array
     */
    private function getItems(PropertyInterface $property)
    {
        $value = $this->getValue($property);
        if (!is_array($value['items']) || 0 === count($value['items'])) {
            return [];
        }

        return $value['items'];
    }

    /**
     * Returns property-value merged with defaults.
     *
     * @return array
     */
    private function getValue(PropertyInterface $property)
    {
        $default = ['presentAs' => null, 'items' => []];
        if (!is_array($property->getValue())) {
            return $default;
        }

        return array_merge($default, $property->getValue());
    }
}
