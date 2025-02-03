<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Structure\ManagedStructure;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * Normalize ManagedStructure instances to the Structure type.
 */
class StructureSubscriber implements EventSubscriberInterface
{
    public function __construct(private DocumentInspector $inspector)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    public function onPreSerialize(PreSerializeEvent $event)
    {
        if ($event->getObject() instanceof Structure) {
            $event->setType(Structure::class);
        }
    }

    /**
     * Adds all the structure specific data (template, structure properties and breadcrumb) to the serialization.
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $document = $event->getObject();
        $context = $event->getContext();

        if (!$document instanceof StructureBehavior) {
            return;
        }

        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();
        $structureMetadata = $this->inspector->getStructureMetadata($document);

        if ($structureMetadata) {
            $template = $document->getStructureType();
            $visitor->visitProperty(
                new StaticPropertyMetadata('', 'template', $template),
                $template
            );

            $visitor->visitProperty(
                new StaticPropertyMetadata('', 'originTemplate', $template),
                $template
            );

            $internal = false;
            $visitor->visitProperty(
                new StaticPropertyMetadata('', 'internal', $internal),
                $internal
            );

            $localizedTemplate = $structureMetadata->getTitle(
                $this->inspector->getLocale($document)
            );

            $visitor->visitProperty(
                new StaticPropertyMetadata('', 'localizedTemplate', $localizedTemplate),
                $localizedTemplate
            );

            if ($context->hasAttribute('groups')
                && \in_array('defaultPage', $context->getAttribute('groups'))
            ) {
                $this->addStructureProperties($structureMetadata, $document, $visitor);
            }
        }
    }

    /**
     * Adds the properties of the structure to the serialization.
     */
    private function addStructureProperties(
        StructureMetadata $structureMetadata,
        StructureBehavior $document,
        SerializationVisitorInterface $visitor
    ) {
        /** @var ManagedStructure $structure */
        $structure = $document->getStructure();
        $data = $structure->toArray();
        foreach ($structureMetadata->getProperties() as $name => $property) {
            if ('title' === $name || !\array_key_exists($name, $data) || $property->hasTag('sulu.rlp')) {
                continue;
            }

            $visitor->visitProperty(
                new StaticPropertyMetadata('', $name, $data[$name]),
                $data[$name]
            );
        }
    }
}
