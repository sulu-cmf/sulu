<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping;

use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\ProxyFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set the children on the document.
 */
class ChildrenSubscriber implements EventSubscriberInterface
{
    public function __construct(private ProxyFactory $proxyFactory)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::HYDRATE => 'handleHydrate',
        ];
    }

    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ChildrenBehavior) {
            return;
        }

        $accessor = $event->getAccessor();
        $accessor->set('children', $this->proxyFactory->createChildrenCollection($document, $event->getOptions()));
    }
}
