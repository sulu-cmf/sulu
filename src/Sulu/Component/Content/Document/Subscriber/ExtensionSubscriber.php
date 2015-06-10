<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Extension\ExtensionManager;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\Content\Document\Extension\ManagedExtensionContainer;

class ExtensionSubscriber extends AbstractMappingSubscriber
{
    private $extensionManager;
    private $inspector;
    private $namespaceRegistry;

    // TODO: Remove this: Use a dedicated namespace instead
    private $internalPrefix = '';

    public function __construct(
        PropertyEncoder $encoder,
        ExtensionManagerInterface $extensionManager,
        DocumentInspector $inspector,

        // these two dependencies should absolutely not be necessary
        NamespaceRegistry $namespaceRegistry
    )
    {
        parent::__construct($encoder);
        $this->extensionManager = $extensionManager;
        $this->inspector = $inspector;
        $this->namespaceRegistry = $namespaceRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            // persist should happen before content is mapped
            Events::PERSIST => array('handlePersist', 10),

            // hydrate should happen afterwards
            Events::HYDRATE => array('handleHydrate', -10),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($document)
    {
        return $document instanceof ExtensionBehavior;
    }

    /**
     * {@inheritDoc}
     */
    public function doHydrate(AbstractMappingEvent $event)
    {
        $this->hydrate($event);
    }

    /**
     * {@inheritDoc}
     */
    public function doPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        $structureType = $document->getStructureType();
        $node = $event->getNode();
        $extensionsData = $document->getExtensionsData();

        $locale = $event->getLocale();
        $webspaceName = $this->inspector->getWebspace($document);
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
        $locale = $event->getLocale();
        $webspaceName = $this->inspector->getWebspace($document);
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
