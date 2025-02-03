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
use Sulu\Component\Content\Document\Extension\ExtensionContainer;

/**
 * Handle serializeation and deserialization of document content.
 */
class ExtensionContainerHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => ExtensionContainer::class,
                'method' => 'doSerialize',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => ExtensionContainer::class,
                'method' => 'doDeserialize',
            ],
        ];
    }

    public function doSerialize(
        SerializationVisitorInterface $visitor,
        ExtensionContainer $container,
        array $type,
        Context $context
    ) {
        return $context->getNavigator()->accept($container->toArray());
    }

    public function doDeserialize(
        DeserializationVisitorInterface $visitor,
        array $data,
        array $type,
        Context $context
    ) {
        return new ExtensionContainer($data);
    }
}
