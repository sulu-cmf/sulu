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

use PHPCR\NodeInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Compat\Structure\Property;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\DocumentInspector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;

/**
 * Set a fallback locale for the document if necessary
 *
 * TODO: Most of this code is legacy. It seems to me that this could be
 *       much simpler and more efficient.
 */
class FallbackLocalizationSubscriber implements EventSubscriberInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var string
     */
    private $defaultLocale;

    public function __construct(
        PropertyEncoder $encoder,
        WebspaceManagerInterface $webspaceManager,
        DocumentInspector $inspector,
        DocumentRegistry $documentRegistry
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->encoder = $encoder;
        $this->inspector = $inspector;
        $this->documentRegistry = $documentRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            // needs to happen after the node and document has been initially registered
            // but before any mapping takes place.
            Events::HYDRATE => array('handleHydrate', 400),
        );
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

        if (!$locale) {
            return;
        }

        $node = $event->getNode();
        $newLocale = $this->getAvailableLocalization($node, $document, $locale);
        $event->setLocale($newLocale);

        if ($newLocale === $locale) {
            return;
        }

        if ($event->getOption('load_ghost_content', true) === true) {
            $this->documentRegistry->updateLocale($document, $newLocale, $locale);
            return;
        }

        $this->documentRegistry->updateLocale($document, $locale, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableLocalization(NodeInterface $node, $document, $locale)
    {
        $structureTypeName = $this->encoder->localizedSystemName(StructureSubscriber::STRUCTURE_TYPE_FIELD, $locale);

        // check if it already is the correct localization
        if ($node->hasProperty($structureTypeName)) {
            return $locale;
        }

        $fallbackLocale = null;

        if ($document instanceof WebspaceBehavior) {
            $fallbackLocale = $this->getWebspaceLocale($document, $node, $locale);
        }

        if (!$fallbackLocale) {
            $locales = $this->inspector->getLocales($document);
            $fallbackLocale = reset($locales);
        }

        if (!$fallbackLocale) {
            $fallbackLocale = $this->documentRegistry->getDefaultLocale();
        }

        return $fallbackLocale;
    }

    private function getWebspaceLocale($document, $node, $locale)
    {
        $webspaceName = $this->inspector->getWebspace($document);

        if (!$webspaceName) {
            return;
        }

        // get localization object for querying parent localizations
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceName);
        $localization = $webspace->getLocalization($locale);

        if (null === $localization) {
            return;
        }

        $resultLocalization = null;

        // find first available localization in parents
        $resultLocalization = $this->findAvailableParentLocalization(
            $node,
            $localization
        );

        // find first available localization in children, if no result is found yet
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableChildLocalization(
                $node,
                $localization
            );
        }

        // find any localization available, if no result is found yet
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableLocalization(
                $node,
                $webspace->getLocalizations()
            );
        }

        if (!$resultLocalization) {
            return;
        }

        return $resultLocalization->getLocalization();
    }

    /**
     * Finds the next available parent-localization in which the node has a translation
     *
     * @param NodeInterface $node         The node, which properties will be checked
     * @param Localization  $localization The localization to start the search for
     *
     * @return Localization|null
     */
    private function findAvailableParentLocalization(
        NodeInterface $node,
        Localization $localization
    ) {
        do {
            $propertyName = $this->getPropertyName($localization->getLocalization());

            if ($node->hasProperty($propertyName)) {
                return $localization;
            }

            // try to load parent and stop if there is no parent
            $localization = $localization->getParent();
        } while ($localization != null);

        return;
    }

    /**
     * Finds the next available child-localization in which the node has a translation
     *
     * @param  NodeInterface      $node         The node, which properties will be checked
     * @param  Localization       $localization The localization to start the search for
     * @param  TranslatedProperty $property     The property which will be checked for the translation
     * @return null|Localization
     */
    private function findAvailableChildLocalization(
        NodeInterface $node,
        Localization $localization
    ) {
        $childrenLocalizations = $localization->getChildren();

        if (!empty($childrenLocalizations)) {
            foreach ($childrenLocalizations as $childrenLocalization) {
                $propertyName = $this->getPropertyName($childrenLocalization->getLocalization());
                // return the localization if a translation exists in the child localization
                if ($node->hasProperty($propertyName)) {
                    return $childrenLocalization;
                }

                // recursively call this function for checking children
                return $this->findAvailableChildLocalization($node, $childrenLocalization);
            }
        }

        // return null if nothing was found
        return;
    }

    /**
     * Finds any localization, in which the node is translated
     * @param  NodeInterface      $node          The node, which properties will be checkec
     * @param  array              $localizations The available localizations
     * @param  TranslatedProperty $property      The property to check
     * @return null|Localization
     */
    private function findAvailableLocalization(
        NodeInterface $node,
        array $localizations
    ) {
        foreach ($localizations as $localization) {
            $propertyName = $this->getPropertyName($localization->getLocalization());

            if ($node->hasProperty($propertyName)) {
                return $localization;
            }

            $children = $localization->getChildren();

            if ($children) {
                return $this->findAvailableLocalization($node, $children);
            }
        }

        return;
    }

    private function getPropertyName($locale)
    {
        return $this->encoder->localizedSystemName(StructureSubscriber::STRUCTURE_TYPE_FIELD, $locale);
    }
}
