<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Component\Content\Document\Structure\Structure;

/**
 * Handle serialization and deserialization of document content.
 */
class StructureHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => Structure::class,
                'method' => 'doSerialize',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => Structure::class,
                'method' => 'doDeserialize',
            ],
        ];
    }

    public function doSerialize(
        SerializationVisitorInterface $visitor,
        Structure $structure,
        array $type,
        Context $context
    ) {
        $array = $structure->toArray();

        return $context->getNavigator()->accept($array);
    }

    public function doDeserialize(
        DeserializationVisitorInterface $visitor,
        array $data,
        array $type,
        Context $context
    ) {
        $structure = new Structure();

        foreach ($data as $key => $value) {
            $structure->getProperty($key)->setValue($value);
        }

        return $structure;
    }
}
