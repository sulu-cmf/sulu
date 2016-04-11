<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Extension\ManagedExtensionContainer;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExtensionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var NamespaceRegistry
     */
    private $namespaceRegistry;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * TODO: Remove this: Use a dedicated namespace instead.
     *
     * @var string
     */
    private $internalPrefix = '';

    public function __construct(
        PropertyEncoder $encoder,
        ExtensionManagerInterface $extensionManager,
        // these two dependencies should absolutely not be necessary
        NamespaceRegistry $namespaceRegistry
    ) {
        $this->encoder = $encoder;
        $this->extensionManager = $extensionManager;
        $this->namespaceRegistry = $namespaceRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // persist should happen before content is mapped
            Events::PERSIST => ['handlePersist', 10],
            // hydrate should happen afterwards
            Events::HYDRATE => ['handleHydrate', -10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        if (!$event->getDocument() instanceof ExtensionBehavior) {
            return;
        }

        $this->hydrate($event);
    }

    /**
     * {@inheritdoc}
     */
    public function handlePersist(PersistEvent $event)
    {
        $locale = $event->getLocale();

        if (!$locale) {
            return;
        }

        $document = $event->getDocument();

        if (!$document instanceof ExtensionBehavior) {
            return;
        }

        $structureType = $document->getStructureType();
        $node = $event->getNode();
        $extensionsData = $document->getExtensionsData();

        $webspaceName = $event->getManager()->getInspector()->getWebspace($document);
        $prefix = $this->namespaceRegistry->getPrefix('extension_localized');

        $extensions = $this->extensionManager->getExtensions($structureType);

        foreach ($extensions as $extension) {
            $extensionData = null;

            if (!isset($extensionsData[$extension->getName()])) {
                continue;
            }

            $extensionData = $extensionsData[$extension->getName()];

            $extension->setLanguageCode($locale, $prefix, $this->internalPrefix);
            $extension->save(
                $node,
                $extensionData,
                $webspaceName,
                $locale
            );
        }

        $this->hydrate($event);
    }

    private function hydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        $node = $event->getNode();
        $locale = $event->getManager()->getInspector()->getLocale($document);
        $webspaceName = $event->getManager()->getInspector()->getWebspace($document);
        $structureType = $document->getStructureType();

        if (null === $structureType) {
            return;
        }

        $prefix = $this->namespaceRegistry->getPrefix('extension_localized');
        $extensionContainer = new ManagedExtensionContainer(
            $structureType,
            $this->extensionManager,
            $node,
            $locale,
            $prefix,
            $this->internalPrefix,
            $webspaceName
        );

        $document->setExtensionsData($extensionContainer);
    }
}
