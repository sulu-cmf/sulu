<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ResetInterface;

class DocumentManager implements DocumentManagerInterface, ResetInterface
{
    /**
     * @var array<string, OptionsResolver> Cached options resolver instances
     */
    private $optionsResolvers = [];

    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function find($identifier, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::FIND)->resolve($options);

        $event = new Event\FindEvent($identifier, $locale, $options);
        $this->eventDispatcher->dispatch($event, Events::FIND);

        return $event->getDocument();
    }

    public function create($alias)
    {
        $event = new Event\CreateEvent($alias);
        $this->eventDispatcher->dispatch($event, Events::CREATE);

        return $event->getDocument();
    }

    public function persist($document, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::PERSIST)->resolve($options);

        $event = new Event\PersistEvent($document, $locale, $options);
        $this->eventDispatcher->dispatch($event, Events::PERSIST);
    }

    public function remove($document/*, array $options = []*/)
    {
        $options = \func_num_args() >= 2 ? (array) \func_get_arg(1) : [];
        $options = $this->getOptionsResolver(Events::REMOVE)->resolve($options);

        $event = new Event\RemoveEvent($document, $options);
        $this->eventDispatcher->dispatch($event, Events::REMOVE);
    }

    public function removeLocale($document, $locale)
    {
        $event = new Event\RemoveLocaleEvent($document, $locale);
        $this->eventDispatcher->dispatch($event, Events::REMOVE_LOCALE);
    }

    public function move($document, $destId)
    {
        $event = new Event\MoveEvent($document, $destId);
        $this->eventDispatcher->dispatch($event, Events::MOVE);
    }

    public function copy($document, $destPath)
    {
        $event = new Event\CopyEvent($document, $destPath);
        $this->eventDispatcher->dispatch($event, Events::COPY);

        return $event->getCopiedPath();
    }

    public function copyLocale($document, $srcLocale, $destLocale)
    {
        $event = new Event\CopyLocaleEvent($document, $srcLocale, $destLocale);
        $this->eventDispatcher->dispatch($event, Events::COPY_LOCALE);
    }

    public function reorder($document, $destId)
    {
        $event = new Event\ReorderEvent($document, $destId);
        $this->eventDispatcher->dispatch($event, Events::REORDER);
    }

    public function sort(object $document, string $locale): void
    {
        $event = new Event\SortEvent($document, $locale);
        $this->eventDispatcher->dispatch($event, Events::SORT);
    }

    public function publish($document, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::PUBLISH)->resolve($options);

        $event = new Event\PublishEvent($document, $locale, $options);
        $this->eventDispatcher->dispatch($event, Events::PUBLISH);
    }

    public function unpublish($document, $locale)
    {
        $event = new Event\UnpublishEvent($document, $locale);
        $this->eventDispatcher->dispatch($event, Events::UNPUBLISH);
    }

    public function removeDraft($document, $locale)
    {
        $event = new Event\RemoveDraftEvent($document, $locale);
        $this->eventDispatcher->dispatch($event, Events::REMOVE_DRAFT);
    }

    public function restore($document, $locale, $version, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::RESTORE)->resolve($options);

        $event = new Event\RestoreEvent($document, $locale, $version, $options);
        $this->eventDispatcher->dispatch($event, Events::RESTORE);
    }

    public function refresh($document)
    {
        $event = new Event\RefreshEvent($document);
        $this->eventDispatcher->dispatch($event, Events::REFRESH);
    }

    public function flush()
    {
        $event = new Event\FlushEvent();
        $this->eventDispatcher->dispatch($event, Events::FLUSH);
    }

    public function clear()
    {
        $event = new Event\ClearEvent();
        $this->eventDispatcher->dispatch($event, Events::CLEAR);
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->clear();
    }

    public function createQuery($query, $locale = null, array $options = [])
    {
        $event = new Event\QueryCreateEvent($query, $locale, $options);
        $this->eventDispatcher->dispatch($event, Events::QUERY_CREATE);

        return $event->getQuery();
    }

    private function getOptionsResolver(string $eventName): OptionsResolver
    {
        if (isset($this->optionsResolvers[$eventName])) {
            return $this->optionsResolvers[$eventName];
        }

        $resolver = new OptionsResolver();
        $resolver->setDefault('locale', null);

        $event = new Event\ConfigureOptionsEvent($resolver);
        $this->eventDispatcher->dispatch($event, Events::CONFIGURE_OPTIONS);

        $this->optionsResolvers[$eventName] = $resolver;

        return $resolver;
    }
}
