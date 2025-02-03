<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Bridge\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;

/**
 * Handle serializeation and deserialization of children collections.
 */
class ChildrenCollectionHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => ChildrenCollection::class,
                'method' => 'doSerialize',
            ],
        ];
    }

    public function doSerialize(
        SerializationVisitorInterface $visitor,
        ChildrenCollection $childrenCollection,
        array $type,
        Context $context
    ) {
        $array = $childrenCollection->toArray();

        return $context->getNavigator()->accept($array);
    }
}
