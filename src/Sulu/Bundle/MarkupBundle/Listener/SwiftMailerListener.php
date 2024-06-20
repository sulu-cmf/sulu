<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Listener;

use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SwiftMailerListener implements \Swift_Events_SendListener
{
    /**
     * @var MarkupParserInterface[]
     */
    private $markupParser;

    public function __construct(
        \Traversable $markupParser,
        private RequestStack $requestStack,
        private string $defaultLocale,
    ) {
        $this->markupParser = \iterator_to_array($markupParser);
    }

    public function beforeSendPerformed(\Swift_Events_SendEvent $event)
    {
        $message = $event->getMessage();

        $body = $message->getBody();
        $format = $message->getBodyContentType();

        if (\count($explodedFormat = \explode('/', $format)) > 1) {
            $format = $explodedFormat[1];
        }

        $currentRequest = $this->requestStack->getCurrentRequest();
        if (null !== $currentRequest) {
            $locale = $currentRequest->getLocale();
        } else {
            $locale = $this->defaultLocale;
        }

        if (!$body || !\array_key_exists($format, $this->markupParser)) {
            return;
        }

        $message->setbody(
            $this->markupParser[$format]->parse($body, $locale)
        );
    }

    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
    }
}
