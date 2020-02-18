<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for Date.
 */
class Date extends SimpleContentType
{
    private $template;

    public function __construct($template)
    {
        parent::__construct('Date');

        $this->template = $template;
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
        if (null != $value) {
            $value = \DateTime::createFromFormat('Y-m-d', $value);

            $node->setProperty($property->getName(), $value);
        } else {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = '';
        if ($node->hasProperty($property->getName())) {
            /** @var \DateTime $propertyValue */
            $propertyValue = $node->getPropertyValue($property->getName());

            if ($propertyValue instanceof \DateTime) {
                $value = $propertyValue->format('Y-m-d');
            }
        }

        $property->setValue($value);

        return $value;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [
            'display_options' => new PropertyParameter('display_options', [], 'collection'),
            'placeholder' => new PropertyParameter('placeholder', null),
        ];
    }
}
