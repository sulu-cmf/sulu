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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Parses content of response and set the replaced html as new content.
 */
class MarkupListener implements EventSubscriberInterface
{
    /**
     * @var array<string, MarkupParserInterface>
     */
    private array $markupParser;

    /**
     * @param iterable<string, MarkupParserInterface> $markupParser
     */
    public function __construct(iterable $markupParser)
    {
        $this->markupParser = [...$markupParser];
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['replaceMarkup', -10]];
    }

    /**
     * Parses content of response and set the replaced html as new content.
     */
    public function replaceMarkup(ResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $format = $request->getRequestFormat();
        $content = $response->getContent();

        if (!$content || !\array_key_exists($format, $this->markupParser)) {
            return;
        }

        $response->setContent(
            $this->markupParser[$format]->parse($content, $request->getLocale())
        );
    }
}
