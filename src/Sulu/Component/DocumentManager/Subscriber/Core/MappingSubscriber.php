<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Core;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\InvalidLocaleException;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\ProxyFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber uses the field map in the metadata to map fields from
 * the PHPCR nodes to the document and vice-versa.
 */
class MappingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MetadataFactoryInterface $factory,
        private PropertyEncoder $encoder,
        private ProxyFactory $proxyFactory,
        private DocumentRegistry $documentRegistry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::HYDRATE => ['handleHydrate', -100],
            Events::PERSIST => ['handleMapping', -100],
            Events::PUBLISH => ['handleMapping', -128],
        ];
    }

    public function handleMapping(AbstractMappingEvent $event)
    {
        $metadata = $this->factory->getMetadataForClass(\get_class($event->getDocument()));
        $locale = $event->getLocale();
        $node = $event->getNode();
        $accessor = $event->getAccessor();

        foreach ($metadata->getFieldMappings() as $fieldName => $fieldMapping) {
            if (false === $fieldMapping['mapped']) {
                continue;
            }

            match ($fieldMapping['type']) {
                'reference' => $this->persistReference($node, $accessor, $fieldName, $locale, $fieldMapping),
                'json_array' => $this->persistJsonArray($node, $accessor, $fieldName, $locale, $fieldMapping),
                default => $this->persistGeneric($node, $accessor, $fieldName, $locale, $fieldMapping),
            };
        }
    }

    /**
     * Persist a reference field type.
     */
    private function persistReference(
        NodeInterface $node,
        DocumentAccessor $accessor,
        $fieldName,
        $locale,
        $fieldMapping
    ) {
        $referenceDocument = $accessor->get($fieldName);

        if (!$referenceDocument) {
            return;
        }

        if ($fieldMapping['multiple']) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Mapping references as multiple not currently supported (when mapping "%s")',
                    $fieldName
                )
            );
        }

        try {
            $referenceNode = $this->documentRegistry->getNodeForDocument($referenceDocument);
            $phpcrName = $this->encoder->encode($fieldMapping['encoding'], $fieldMapping['property'], $locale);
            $node->setProperty($phpcrName, $referenceNode);
        } catch (InvalidLocaleException $ex) {
            // arguments invalid, no valid propertyname could be generated (e.g. no locale given for localized encoding)
            return;
        }
    }

    /**
     * Persist "scalar" field types.
     */
    private function persistGeneric(
        NodeInterface $node,
        DocumentAccessor $accessor,
        $fieldName,
        $locale,
        array $fieldMapping
    ) {
        try {
            $phpcrName = $this->encoder->encode($fieldMapping['encoding'], $fieldMapping['property'], $locale);
            $value = $accessor->get($fieldName);
            $this->validateFieldValue($value, $fieldName, $fieldMapping);
            $node->setProperty($phpcrName, $value);
        } catch (InvalidLocaleException $ex) {
            // arguments invalid, no valid propertyname could be generated (e.g. no locale given for localized encoding)
            return;
        }
    }

    /**
     * Persist "json_array" field types.
     */
    private function persistJsonArray(
        NodeInterface $node,
        DocumentAccessor $accessor,
        $fieldName,
        $locale,
        array $fieldMapping
    ) {
        try {
            $phpcrName = $this->encoder->encode($fieldMapping['encoding'], $fieldMapping['property'], $locale);
            $value = $accessor->get($fieldName);
            $this->validateFieldValue($value, $fieldName, $fieldMapping);
            $node->setProperty($phpcrName, \json_encode($value));
        } catch (InvalidLocaleException $ex) {
            // arguments invalid, no valid propertyname could be generated (e.g. no locale given for localized encoding)
            return;
        }
    }

    public function handleHydrate(AbstractMappingEvent $event)
    {
        $class = \get_class($event->getDocument());

        // TODO: Return false here in case this is for instance an UnknownDocument.
        //       But we should probably map the UnknownDocument and let an Exception be
        //       thrown in other cases.
        if (false === $this->factory->hasMetadataForClass($class)) {
            return;
        }

        $metadata = $this->factory->getMetadataForClass($class);
        $locale = $event->getLocale();
        $node = $event->getNode();
        $accessor = $event->getAccessor();
        $document = $event->getDocument();

        foreach ($metadata->getFieldMappings() as $fieldName => $fieldMapping) {
            if (false === $fieldMapping['mapped']) {
                continue;
            }

            match ($fieldMapping['type']) {
                'reference' => $this->hydrateReferenceField(
                    $node,
                    $document,
                    $accessor,
                    $fieldName,
                    $locale,
                    $fieldMapping,
                    $event->getOptions()
                ),
                'json_array' => $this->hydrateJsonArrayField($node, $accessor, $fieldName, $locale, $fieldMapping),
                default => $this->hydrateGenericField($node, $accessor, $fieldName, $locale, $fieldMapping),
            };
        }
    }

    /**
     * Hydrate reference field types.
     */
    private function hydrateReferenceField(
        NodeInterface $node,
        $document,
        DocumentAccessor $accessor,
        $fieldName,
        $locale,
        array $fieldMapping,
        array $options
    ) {
        try {
            $phpcrName = $this->encoder->encode($fieldMapping['encoding'], $fieldMapping['property'], $locale);
            $referencedNode = $node->getPropertyValueWithDefault(
                $phpcrName,
                $this->getDefaultValue($fieldMapping)
            );

            if ($referencedNode) {
                $accessor->set(
                    $fieldName,
                    $this->proxyFactory->createProxyForNode($document, $referencedNode, $options)
                );
            }
        } catch (InvalidLocaleException $ex) {
            // arguments invalid, no valid propertyname could be generated (e.g. no locale given for localized encoding)
            return;
        }
    }

    /**
     * Hydrate "scalar" field types.
     */
    private function hydrateGenericField(
        NodeInterface $node,
        DocumentAccessor $accessor,
        $fieldName,
        $locale,
        array $fieldMapping
    ) {
        try {
            $phpcrName = $this->encoder->encode($fieldMapping['encoding'], $fieldMapping['property'], $locale);
            $value = $node->getPropertyValueWithDefault(
                $phpcrName,
                $this->getDefaultValue($fieldMapping)
            );
            $accessor->set($fieldName, $value);
        } catch (InvalidLocaleException $ex) {
            // arguments invalid, no valid propertyname could be generated (e.g. no locale given for localized encoding)
            return;
        }
    }

    /**
     * Hydrate "json_array" field types.
     */
    private function hydrateJsonArrayField(
        NodeInterface $node,
        DocumentAccessor $accessor,
        $fieldName,
        $locale,
        array $fieldMapping
    ) {
        try {
            $phpcrName = $this->encoder->encode($fieldMapping['encoding'], $fieldMapping['property'], $locale);
            $value = $node->getPropertyValueWithDefault(
                $phpcrName,
                $this->getDefaultValue($fieldMapping)
            );
            $accessor->set($fieldName, \json_decode($value, true));
        } catch (InvalidLocaleException $ex) {
            // arguments invalid, no valid propertyname could be generated (e.g. no locale given for localized encoding)
            return;
        }
    }

    private function getDefaultValue(array $fieldMapping)
    {
        if ($fieldMapping['default']) {
            return $fieldMapping['default'];
        }

        return $fieldMapping['multiple'] ? [] : null;
    }

    private function validateFieldValue($value, $fieldName, $fieldMapping)
    {
        if ($fieldMapping['multiple'] && !\is_array($value)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Field "%s" is mapped as multiple, and therefore must be an array, got "%s"',
                    $fieldName,
                    \is_object($value) ? \get_class($value) : \gettype($value)
                )
            );
        }
    }
}
