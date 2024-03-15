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

use PHPCR\NodeInterface;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Copies values for shadow pages.
 */
class ShadowCopyPropertiesSubscriber implements EventSubscriberInterface
{
    public const SHADOW_BASE_PROPERTY = 'i18n:*-shadow-base';

    public const SHADOW_ON_PROPERTY = 'i18n:%s-shadow-on';

    public const TAGS_PROPERTY = 'i18n:%s-excerpt-tags';

    public const CATEGORIES_PROPERTY = 'i18n:%s-excerpt-categories';

    public const NAVIGATION_CONTEXT_PROPERTY = 'i18n:%s-navContexts';

    /**
     * @var PropertyEncoder
     */
    protected $encoder;

    public function __construct(PropertyEncoder $encoder)
    {
        $this->encoder = $encoder;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['copyShadowProperties', -256],
            Events::PUBLISH => 'copyShadowProperties',
        ];
    }

    /**
     * Handles persist event of document manager.
     */
    public function copyShadowProperties(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        if (!$event->getDocument()->isShadowLocaleEnabled()) {
            $this->copyToShadows($event->getDocument(), $event->getNode());
        } else {
            $this->copyFromShadow($event->getDocument(), $event->getNode());
        }
    }

    /**
     * Copy tags and categories from current locale to all shadowed pages with this locale as base-locale.
     *
     * @param object $document
     */
    public function copyToShadows($document, NodeInterface $node)
    {
        $tags = $this->getTags($node, $document->getLocale());
        $categories = $this->getCategories($node, $document->getLocale());
        $navigationContext = $this->getNavigationContext($node, $document->getLocale());

        foreach ($node->getProperties(self::SHADOW_BASE_PROPERTY) as $name => $property) {
            $locale = $this->getLocale($name);
            if ($node->getPropertyValueWithDefault(\sprintf(self::SHADOW_ON_PROPERTY, $locale), false)
                && $property->getValue() === $document->getLocale()
            ) {
                $locale = $this->getLocale($property->getName());

                $node->setProperty(\sprintf(self::TAGS_PROPERTY, $locale), $tags);
                $node->setProperty(\sprintf(self::CATEGORIES_PROPERTY, $locale), $categories);
                $node->setProperty(\sprintf(self::NAVIGATION_CONTEXT_PROPERTY, $locale), $navigationContext);
            }
        }
    }

    /**
     * Copy tags and categories from base-locale to current locale.
     *
     * @param object $document
     */
    public function copyFromShadow($document, NodeInterface $node)
    {
        $shadowLocale = $document->getShadowLocale();

        $tags = $this->getTags($node, $shadowLocale);
        $categories = $this->getCategories($node, $shadowLocale);
        $navigationContext = $this->getNavigationContext($node, $shadowLocale);

        $node->setProperty(\sprintf(self::TAGS_PROPERTY, $document->getLocale()), $tags);
        $node->setProperty(\sprintf(self::CATEGORIES_PROPERTY, $document->getLocale()), $categories);
        $node->setProperty(\sprintf(self::NAVIGATION_CONTEXT_PROPERTY, $document->getLocale()), $navigationContext);
    }

    /**
     * Returns tags of given node and locale.
     *
     * @param string $locale
     *
     * @return array
     */
    private function getTags(NodeInterface $node, $locale)
    {
        return $node->getPropertyValueWithDefault(
            \sprintf(self::TAGS_PROPERTY, $locale),
            []
        );
    }

    /**
     * Returns categories of given node and locale.
     *
     * @param string $locale
     *
     * @return array
     */
    private function getCategories(NodeInterface $node, $locale)
    {
        return $node->getPropertyValueWithDefault(
            \sprintf(self::CATEGORIES_PROPERTY, $locale),
            []
        );
    }

    /**
     * Returns navigation context of given node and locale.
     *
     * @param string $locale
     *
     * @return array
     */
    private function getNavigationContext(NodeInterface $node, $locale)
    {
        return $node->getPropertyValueWithDefault(
            \sprintf(self::NAVIGATION_CONTEXT_PROPERTY, $locale),
            []
        );
    }

    private function getLocale($propertyName)
    {
        \preg_match('/i18n:(?P<locale>.+)-shadow-base/', $propertyName, $match);

        return $match['locale'];
    }

    protected function supports($document)
    {
        return $document instanceof ShadowLocaleBehavior && $document instanceof LocaleBehavior;
    }
}
