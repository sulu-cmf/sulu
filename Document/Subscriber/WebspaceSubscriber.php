<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Subscriber;

use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\Behavior\WebspaceBehavior;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\RouteBundle\PageTree\PageTreeTrait;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events to set webspace settings.
 */
class WebspaceSubscriber implements EventSubscriberInterface
{
    use PageTreeTrait;

    public const MAIN_WEBSPACE_PROPERTY = 'mainWebspace';

    public const ADDITIONAL_WEBSPACES_PROPERTY = 'additionalWebspaces';

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var DocumentInspector
     */
    protected $documentInspector;

    /**
     * @var PropertyEncoder
     */
    protected $propertyEncoder;

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        PropertyEncoder $propertyEncoder
    ) {
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->propertyEncoder = $propertyEncoder;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => ['loadProperties'],
            Events::PERSIST => ['saveProperties'],
            Events::PUBLISH => ['saveProperties'],
        ];
    }

    public function loadProperties(AbstractMappingEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof WebspaceBehavior) {
            return;
        }

        $locale = $event->getLocale();
        if (LocalizationState::GHOST === $this->documentInspector->getLocalizationState($document)) {
            $locale = $document->getOriginalLocale();
        }

        $mainWebspace = $event->getNode()->getPropertyValueWithDefault(
            $this->getMainWebspacePropertyName($locale),
            null
        );
        $additionalWebspaces = $event->getNode()->getPropertyValueWithDefault(
            $this->getAdditionalWebspacesPropertyName($locale),
            null
        );

        $document->setMainWebspace($mainWebspace);
        $document->setAdditionalWebspaces($additionalWebspaces);
    }

    public function saveProperties(AbstractMappingEvent $event): void
    {
        $document = $event->getDocument();
        if (!$document instanceof ArticleInterface || !$document instanceof WebspaceBehavior) {
            return;
        }

        $parentPageUuid = $this->getParentPageUuidFromPageTree($document);
        if ($parentPageUuid) {
            // we know now it's a `page_tree_route` route
            // so load the parent and find out the webspace
            try {
                $parentDocument = $this->documentManager->find($parentPageUuid, $event->getLocale());
                $mainWebspace = $this->documentInspector->getWebspace($parentDocument);
                $document->setMainWebspace($mainWebspace);
                $document->setAdditionalWebspaces([]);
            } catch (DocumentNotFoundException $exception) {
                // Do nothing, because when the parent page was deleted it should still work while restoring.
            }
        }

        $mainWebspace = $document->getMainWebspace();
        $additionalWebspaces = null;
        if ($mainWebspace) {
            $mainWebspace = $document->getMainWebspace();
            $additionalWebspaces = $document->getAdditionalWebspaces();
        }

        $event->getNode()->setProperty(
            $this->getMainWebspacePropertyName($document->getLocale()),
            $mainWebspace
        );
        $event->getNode()->setProperty(
            $this->getAdditionalWebspacesPropertyName($document->getLocale()),
            $additionalWebspaces
        );
    }

    /**
     * Returns encoded "mainWebspace" property-name.
     */
    private function getMainWebspacePropertyName(string $locale): string
    {
        return $this->propertyEncoder->localizedSystemName(self::MAIN_WEBSPACE_PROPERTY, $locale);
    }

    /**
     * Returns encoded "additionalWebspaces" property-name.
     */
    private function getAdditionalWebspacesPropertyName(string $locale): string
    {
        return $this->propertyEncoder->localizedSystemName(self::ADDITIONAL_WEBSPACES_PROPERTY, $locale);
    }

    protected function getDocumentInspector()
    {
        return $this->documentInspector;
    }
}
