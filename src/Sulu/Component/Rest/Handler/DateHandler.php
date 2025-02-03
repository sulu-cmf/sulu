<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;

/**
 * Serializes Date for array serializer.
 */
class DateHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): array
    {
        return [
            [
                'type' => 'DateTime',
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'array',
                'method' => 'deserialize',
            ],
            [
                'type' => 'DateTimeImmutable',
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'array',
                'method' => 'deserialize',
            ],
            [
                'type' => 'DateTime',
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'array',
                'method' => 'serialize',
            ],
            [
                'type' => 'DateTimeImmutable',
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'array',
                'method' => 'serialize',
            ],
        ];
    }

    public function serialize(SerializationVisitorInterface $visitor, $date, array $type, Context $context)
    {
        return $date;
    }

    public function deserialize(SerializationVisitorInterface $visitor, $date, array $type, Context $context)
    {
        return $date;
    }
}
