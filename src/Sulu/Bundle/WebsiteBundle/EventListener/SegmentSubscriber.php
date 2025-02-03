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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SegmentSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $segmentHeader;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var string
     */
    private $segmentCookieName;

    public function __construct(
        string $segmentHeader,
        RequestAnalyzerInterface $requestAnalyzer,
        string $segmentCookieName
    ) {
        $this->segmentHeader = $segmentHeader;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->segmentCookieName = $segmentCookieName;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['addVaryHeader'],
                ['addCookieHeader'],
            ],
        ];
    }

    public function addVaryHeader(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $webspace = $this->requestAnalyzer->getWebspace();

        if ($webspace && \count($webspace->getSegments())) {
            $response->setVary($this->segmentHeader, false);
        }
    }

    public function addCookieHeader(ResponseEvent $event)
    {
        $response = $event->getResponse();

        $webspace = $this->requestAnalyzer->getWebspace();

        $currentSegment = $this->requestAnalyzer->getSegment();
        $defaultSegment = $webspace ? $webspace->getDefaultSegment() : null;

        $defaultSegmentKey = $defaultSegment ? $defaultSegment->getKey() : null;

        $currentSegmentKey = $currentSegment ? $currentSegment->getKey() : $defaultSegmentKey;
        $cookieSegmentKey = $event->getRequest()->cookies->get($this->segmentCookieName) ?? $defaultSegmentKey;

        if ($currentSegmentKey !== $cookieSegmentKey) {
            $response->headers->setCookie(
                Cookie::create(
                    $this->segmentCookieName,
                    $defaultSegmentKey === $currentSegmentKey ? null : $currentSegmentKey
                )
            );
        }
    }
}
