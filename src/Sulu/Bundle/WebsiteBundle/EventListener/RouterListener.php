<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener as BaseRouterListener;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This listener replaces the default RouterListener delivered by Symfony and adds the analyzing of the request as
 * required by Sulu. Validating the result can be deactivated by passing `false` to the `_requestAnalyzer` default in
 * the route.
 */
class RouterListener implements EventSubscriberInterface
{
    public const REQUEST_ANALYZER = '_requestAnalyzer';

    /**
     * @var BaseRouterListener
     */
    private $baseRouteListener;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(BaseRouterListener $baseRouterListener, RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->baseRouteListener = $baseRouterListener;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * Analyzes the request before passing the event to the default RouterListener from symfony and validates the result
     * afterwards.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // This call is required in all cases, because the default router needs our webspace information
        // Would be nice to also only call this if the _requestAnalyzer attribute is set, but it's set on the next line
        $this->requestAnalyzer->analyze($request);

        $this->baseRouteListener->onKernelRequest($event);
        if (false !== $request->attributes->getBoolean(static::REQUEST_ANALYZER, true)) {
            $this->requestAnalyzer->validate($request);
        }
    }

    /**
     * Simply pass the event to the route listener, because we have nothing to add here.
     */
    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        $this->baseRouteListener->onKernelFinishRequest($event);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 32]],
            KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
        ];
    }
}
