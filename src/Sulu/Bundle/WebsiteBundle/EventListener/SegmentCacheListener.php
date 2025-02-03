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

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SegmentCacheListener implements EventSubscriberInterface
{
    public const SEGMENT_COOKIE = '_ss';

    public const SEGMENT_HEADER = 'X-Sulu-Segment';

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => ['preHandle', 512],
            Events::POST_HANDLE => ['postHandle', -512],
        ];
    }

    public function preHandle(CacheEvent $cacheEvent): void
    {
        $request = $cacheEvent->getRequest();

        // add the segment as separate header to vary on it
        $segment = $request->cookies->get(static::SEGMENT_COOKIE);
        $request->headers->set(static::SEGMENT_HEADER, (string) $segment);
    }

    public function postHandle(CacheEvent $cacheEvent): void
    {
        $response = $cacheEvent->getResponse();

        if (\in_array(static::SEGMENT_HEADER, $response->getVary())) {
            $response->setMaxAge(0);
            $response->setSharedMaxAge(0);
        }
    }
}
