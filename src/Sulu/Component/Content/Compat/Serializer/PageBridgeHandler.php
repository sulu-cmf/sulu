<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;

/**
 * Handle serialization and deserialization of the PageBridge.
 */
class PageBridgeHandler implements SubscribingHandlerInterface
{
    public function __construct(
        private DocumentInspector $inspector,
        private LegacyPropertyFactory $propertyFactory,
        private StructureMetadataFactoryInterface $structureFactory,
    ) {
    }

    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => PageBridge::class,
                'method' => 'doDeserialize',
            ],
        ];
    }

    /**
     * @return PageBridge
     */
    public function doDeserialize(
        DeserializationVisitorInterface $visitor,
        array $data,
        array $type,
        Context $context
    ) {
        $document = $context->getNavigator()->accept($data['document'], ['name' => $data['documentClass']]);
        $structure = $this->structureFactory->getStructureMetadata('page', $data['structure']);
        $bridge = new PageBridge($structure, $this->inspector, $this->propertyFactory, $document);

        // filthy hack to set the Visitor::$result to null and force the
        // serializer to return the Bridge and not the Document
        $visitor->setNavigator($context->getNavigator());

        return $bridge;
    }
}
