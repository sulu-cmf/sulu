<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class RequestListener implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(RouterInterface $router, RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->router = $router;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 31]];
    }

    public function onRequest(RequestEvent $event)
    {
        $context = $this->router->getContext();
        $portalInformation = $this->requestAnalyzer->getPortalInformation();

        if ($portalInformation) {
            if (!$context->hasParameter('prefix')) {
                $context->setParameter('prefix', $portalInformation->getPrefix());
            }
            if (!$context->hasParameter('host')) {
                $context->setParameter('host', $portalInformation->getHost());
            }
        }
    }
}
