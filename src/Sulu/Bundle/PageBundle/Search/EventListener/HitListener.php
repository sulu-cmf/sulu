<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Search\EventListener;

use Massive\Bundle\SearchBundle\Search\Event\HitEvent;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Listen to for new hits. If document instance of structure
 * prefix the current resource locator prefix to the URL.
 */
class HitListener
{
    public function __construct(private RequestAnalyzerInterface $requestAnalyzer)
    {
    }

    /**
     * Prefix url of document with current resourcelocator prefix.
     */
    public function onHit(HitEvent $event)
    {
        $document = $event->getHit()->getDocument();

        if ($document instanceof BasePageDocument) {
            return;
        }

        $url = $document->getUrl();

        if (!$url) {
            return;
        }

        if ('/' != \substr($url, 0, 1)) {
            // is absolute URL

            return;
        }

        $url = \sprintf(
            '%s/%s',
            \rtrim($this->requestAnalyzer->getResourceLocatorPrefix(), '/'),
            \ltrim($document->getUrl(), '/')
        );

        $document->setUrl($url);
    }
}
