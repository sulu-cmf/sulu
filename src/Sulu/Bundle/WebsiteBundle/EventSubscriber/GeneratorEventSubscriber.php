<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds the X-Generator header.
 */
class GeneratorEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $version;

    public function __construct($version)
    {
        $this->version = $version;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event)
    {
        $event->getResponse()->headers->set('X-Generator', 'Sulu/' . $this->version);
    }
}
