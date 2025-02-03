<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\EventListener;

use DeviceDetector\DeviceDetector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DeviceDetectorSubscriber implements EventSubscriberInterface
{
    /**
     * @var DeviceDetector
     */
    private $deviceDetector;

    public function __construct(DeviceDetector $deviceDetector)
    {
        $this->deviceDetector = $deviceDetector;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['setUserAgent'],
            ],
        ];
    }

    public function setUserAgent(RequestEvent $event)
    {
        $this->deviceDetector->setUserAgent($event->getRequest()->headers->get('User-Agent', ''));
        $this->deviceDetector->parse();
    }
}
