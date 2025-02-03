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

    public const AUTHOR_PROPERTY = 'i18n:%s-author';

    public const AUTHORED_PROPERTY = 'i18n:%s-authored';

    public const LAST_MODIFIED_PROPERTY = 'i18n:%s-lastModified';

    public const TEMPLATE_PROPERTY = 'i18n:%s-template';

    public function __construct(protected PropertyEncoder $encoder)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => ['copyShadowProperties', -256],
            Events::PUBLISH => ['copyShadowProperties', -256],
        ];
    }

    /**
     * Handles persist event of document manager.
     */
    public function copyShadowProperties(AbstractMappingEvent $event)
    {
        if (!$this->supports($event->getDocument())) {
            return;
        }

        /** @var ShadowLocaleBehavior&LocaleBehavior $document */
        $document = $event->getDocument();

        /** @var string|null $documentLocale */
        $documentLocale = $document->getLocale();
        if (null === $documentLocale) {
            return;
        }

        if (!$event->getDocument()->isShadowLocaleEnabled()) {
            $this->copyToShadows($document, $event->getNode());
        } else {
            $this->copyFromShadow($document, $event->getNode());
        }
    }

    /**
     * Copy tags and categories from current locale to all shadowed pages with this locale as base-locale.
     *
     * @param ShadowLocaleBehavior&LocaleBehavior $document
     */
    public function copyToShadows($document, NodeInterface $node)
    {
        $documentLocale = $document->getLocale();

        $tags = $this->getTags($node, $documentLocale);
        $categories = $this->getCategories($node, $documentLocale);
        $navigationContext = $this->getNavigationContext($node, $documentLocale);
        $author = $this->getAuthor($node, $documentLocale);
        $authored = $this->getAuthored($node, $documentLocale);
        $lastModified = $this->getLastModified($node, $documentLocale);
        $template = $this->getTemplate($node, $documentLocale);

        foreach ($node->getProperties(self::SHADOW_BASE_PROPERTY) as $name => $property) {
            $locale = $this->getLocale($name);
            if ($node->getPropertyValueWithDefault(\sprintf(self::SHADOW_ON_PROPERTY, $locale), false)
                && $property->getValue() === $documentLocale
            ) {
                $locale = $this->getLocale($property->getName());

                $node->setProperty(\sprintf(self::TAGS_PROPERTY, $locale), $tags);
                $node->setProperty(\sprintf(self::CATEGORIES_PROPERTY, $locale), $categories);
                $node->setProperty(\sprintf(self::NAVIGATION_CONTEXT_PROPERTY, $locale), $navigationContext);
                $node->setProperty(\sprintf(self::AUTHOR_PROPERTY, $locale), $author);
                $node->setProperty(\sprintf(self::AUTHORED_PROPERTY, $locale), $authored);
                $node->setProperty(\sprintf(self::LAST_MODIFIED_PROPERTY, $locale), $lastModified);
                $node->setProperty(\sprintf(self::TEMPLATE_PROPERTY, $locale), $template);
            }
        }
    }

    /**
     * Copy tags and categories from base-locale to current locale.
     *
     * @param ShadowLocaleBehavior&LocaleBehavior $document
     */
    public function copyFromShadow($document, NodeInterface $node)
    {
        /** @var string $shadowLocale */
        $shadowLocale = $document->getShadowLocale();
        /** @var string $locale */
        $locale = $document->getLocale();

        $tags = $this->getTags($node, $shadowLocale);
        $categories = $this->getCategories($node, $shadowLocale);
        $navigationContext = $this->getNavigationContext($node, $shadowLocale);
        $author = $this->getAuthor($node, $shadowLocale);
        $authored = $this->getAuthored($node, $shadowLocale);
        $lastModified = $this->getLastModified($node, $shadowLocale);
        $template = $this->getTemplate($node, $shadowLocale);

        $node->setProperty(\sprintf(self::TAGS_PROPERTY, $locale), $tags);
        $node->setProperty(\sprintf(self::CATEGORIES_PROPERTY, $locale), $categories);
        $node->setProperty(\sprintf(self::NAVIGATION_CONTEXT_PROPERTY, $locale), $navigationContext);
        $node->setProperty(\sprintf(self::AUTHOR_PROPERTY, $locale), $author);
        $node->setProperty(\sprintf(self::AUTHORED_PROPERTY, $locale), $authored);
        $node->setProperty(\sprintf(self::LAST_MODIFIED_PROPERTY, $locale), $lastModified);
        $node->setProperty(\sprintf(self::TEMPLATE_PROPERTY, $locale), $template);
    }

    /**
     * @return int[]
     */
    private function getTags(NodeInterface $node, string $locale): array
    {
        /** @var int[] $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::TAGS_PROPERTY, $locale),
            []
        );

        return $result;
    }

    /**
     * @return int[]
     */
    private function getCategories(NodeInterface $node, string $locale): array
    {
        /** @var int[] $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::CATEGORIES_PROPERTY, $locale),
            []
        );

        return $result;
    }

    /**
     * @return string[]
     */
    private function getNavigationContext(NodeInterface $node, string $locale): array
    {
        /** @var string[] $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::NAVIGATION_CONTEXT_PROPERTY, $locale),
            []
        );

        return $result;
    }

    private function getAuthor(NodeInterface $node, string $locale): ?string
    {
        /** @var string|null $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::AUTHOR_PROPERTY, $locale),
            null
        );

        return $result;
    }

    private function getAuthored(NodeInterface $node, string $locale): ?\DateTimeInterface
    {
        /** @var \DateTimeInterface|null $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::AUTHORED_PROPERTY, $locale),
            null
        );

        return $result;
    }

    private function getLastModified(NodeInterface $node, string $locale): ?\DateTimeInterface
    {
        /** @var \DateTimeInterface|null $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::LAST_MODIFIED_PROPERTY, $locale),
            null
        );

        return $result;
    }

    private function getTemplate(NodeInterface $node, string $locale): ?string
    {
        /** @var string|null $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::TEMPLATE_PROPERTY, $locale),
            null
        );

        return $result;
    }

    private function getLocale(string $propertyName): string
    {
        \preg_match('/i18n:(?P<locale>.+)-shadow-base/', $propertyName, $match);
        \assert(\array_key_exists('locale', $match), 'This method should only be called with property names matching the shadow base property schema.');

        return $match['locale'];
    }

    protected function supports(object $document): bool
    {
        return $document instanceof ShadowLocaleBehavior && $document instanceof LocaleBehavior;
    }
}
