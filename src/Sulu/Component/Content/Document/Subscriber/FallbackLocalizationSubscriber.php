<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\LocalizationFinderInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set a fallback locale for the document if necessary.
 */
class FallbackLocalizationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PropertyEncoder $encoder,
        private DocumentInspector $inspector,
        private DocumentRegistry $documentRegistry,
        private LocalizationFinderInterface $localizationFinder,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // needs to happen after the node and document has been initially registered
            // but before any mapping takes place.
            Events::HYDRATE => ['handleHydrate', 400],
        ];
    }

    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        // we currently only support fallback on StructureBehavior implementors
        // because we use the template key to determine localization status
        if (!$document instanceof StructureBehavior) {
            return;
        }

        $locale = $event->getLocale();

        if (!$locale || false === $event->getOption('load_ghost_content', true)) {
            return;
        }

        // change locale of document of ghost content should be loaded
        $newLocale = $this->getAvailableLocalization($document, $locale);
        $event->setLocale($newLocale);
        $document->setLocale($newLocale);
    }

    /**
     * Return available localizations.
     *
     * @param string $locale
     *
     * @return string
     */
    public function getAvailableLocalization(StructureBehavior $document, $locale)
    {
        $availableLocales = $this->inspector->getLocales($document);

        if (\in_array($locale, $availableLocales)) {
            return $locale;
        }

        $fallbackLocale = null;

        if ($document instanceof WebspaceBehavior) {
            $fallbackLocale = $this->localizationFinder->findAvailableLocale(
                $this->inspector->getWebspace($document),
                $availableLocales,
                $locale
            );
        }

        if (!$fallbackLocale) {
            $fallbackLocale = \reset($availableLocales);
        }

        if (!$fallbackLocale) {
            $fallbackLocale = $this->documentRegistry->getDefaultLocale();
        }

        return $fallbackLocale;
    }
}
