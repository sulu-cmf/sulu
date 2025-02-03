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
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShadowLocaleSubscriber implements EventSubscriberInterface
{
    public const SHADOW_ENABLED_FIELD = 'shadow-on';

    public const SHADOW_LOCALE_FIELD = 'shadow-base';

    public function __construct(
        private PropertyEncoder $encoder,
        private DocumentInspector $inspector,
        private DocumentRegistry $registry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::METADATA_LOAD => 'handleMetadataLoad',
            Events::PERSIST => [
                // before resourceSegment and content
                ['handlePersistUpdateUrl', 20],
                ['saveShadowProperties', 15],
            ],
            Events::HYDRATE => [
                ['handleHydrate', 390],
            ],
            Events::PUBLISH => ['saveShadowProperties', 15],
            Events::CONFIGURE_OPTIONS => 'handleConfigureOptions',
        ];
    }

    public function handleConfigureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();
        $options->setDefaults(
            [
                'load_shadow_content' => true,
            ]
        );
        $options->setAllowedTypes('load_shadow_content', 'bool');
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (!$metadata->getReflectionClass()->isSubclassOf(ShadowLocaleBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('shadowLocaleEnabled', [
            'property' => self::SHADOW_ENABLED_FIELD,
            'encoding' => 'system_localized',
            'mapped' => false,
        ]);

        $metadata->addFieldMapping('shadowLocale', [
            'property' => self::SHADOW_LOCALE_FIELD,
            'encoding' => 'system_localized',
            'mapped' => false,
        ]);
    }

    /**
     * Update the locale to the shadow locale, if it is enabled.
     *
     * Note that this should happen before the fallback locale has been resolved
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ShadowLocaleBehavior || !$event->getOption('load_shadow_content')) {
            return;
        }

        $node = $event->getNode();
        $locale = $this->inspector->getOriginalLocale($document);
        $shadowLocaleEnabled = $this->getShadowLocaleEnabled($node, $locale);
        $document->setShadowLocaleEnabled($shadowLocaleEnabled);

        if (!$shadowLocaleEnabled) {
            return;
        }

        $shadowLocale = $this->getShadowLocale($node, $locale);
        $document->setShadowLocale($shadowLocale);
        $event->setLocale($shadowLocale);
        $document->setLocale($shadowLocale);
    }

    public function saveShadowProperties(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ShadowLocaleBehavior) {
            return;
        }

        if (!$event->getLocale()) {
            return;
        }

        if ($document->isShadowLocaleEnabled()) {
            $this->validateShadow($document);
        }

        $event->getNode()->setProperty(
            $this->encoder->localizedSystemName(self::SHADOW_ENABLED_FIELD, $event->getLocale()),
            $document->isShadowLocaleEnabled() ?: null
        );

        $event->getNode()->setProperty(
            $this->encoder->localizedSystemName(self::SHADOW_LOCALE_FIELD, $event->getLocale()),
            $document->getShadowLocale()
        );
    }

    /**
     * If this is a shadow document, update the URL to that of the shadowed document.
     *
     * TODO: This is about caching and should be handled somewhere else.
     */
    public function handlePersistUpdateUrl(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ShadowLocaleBehavior) {
            return;
        }

        if (!$document->isShadowLocaleEnabled()) {
            return;
        }

        $node = $event->getNode();
        $structure = $this->inspector->getStructureMetadata($document);

        if (false === $structure->hasPropertyWithTagName('sulu.rlp')) {
            return;
        }

        $locatorProperty = $structure->getPropertyByTagName('sulu.rlp');

        if ($node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(
                $locatorProperty->getName(), $document->getLocale()
            ),
            null
        )) {
            return;
        }

        $shadowLocator = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(
                $locatorProperty->getName(), $document->getShadowLocale()
            ),
            null
        );

        if (!$shadowLocator) {
            return;
        }

        if (!$event->getAccessor()->get('resourceSegment')) {
            $event->getAccessor()->set(
                'resourceSegment',
                $shadowLocator
            );
        }
    }

    private function getShadowLocaleEnabled(NodeInterface $node, $locale)
    {
        return (bool) $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::SHADOW_ENABLED_FIELD, $locale),
            false
        );
    }

    private function getShadowLocale(NodeInterface $node, $locale)
    {
        return $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::SHADOW_LOCALE_FIELD, $locale),
            null
        );
    }

    private function validateShadow(ShadowLocaleBehavior $document)
    {
        if ($document->getLocale() === $document->getShadowLocale()) {
            throw new \RuntimeException(\sprintf(
                'Document cannot be a shadow of itself for locale "%s"',
                $document->getLocale()
            ));
        }

        $locales = $this->inspector->getConcreteLocales($document);
        if (!\in_array($document->getShadowLocale(), $locales)) {
            $this->inspector->getNode($document)->revert();

            throw new \RuntimeException(\sprintf(
                'Attempting to create shadow for "%s" on a non-concrete locale "%s" for document at "%s". Concrete languages are "%s"',
                $document->getLocale(),
                $document->getShadowLocale(),
                $this->inspector->getPath($document),
                \implode('", "', $locales)
            ));
        }
    }
}
