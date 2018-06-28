<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for Checkbox.
 */
class Checkbox extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('Checkbox', false);
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

        if (null !== $value && false !== $value && 'false' !== $value && '' !== $value) {
            $node->setProperty($property->getName(), true);
        } else {
            $node->setProperty($property->getName(), false);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [
            'type' => 'checkbox',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $preparedValue = true;

        if ('0' === $value) {
            $preparedValue = false;
        }

        parent::importData($node, $property, $preparedValue, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}
