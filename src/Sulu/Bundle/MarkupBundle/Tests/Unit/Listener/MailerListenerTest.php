<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tests\Unit\Listener;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MarkupBundle\Listener\MailerListener;
use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class MailerListenerTest extends TestCase
{
    /**
     * @var MarkupParserInterface
     */
    private $markupParser;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var MailerListener
     */
    private $mailerListener;

    public function setUp(): void
    {
        $this->markupParser = new class implements MarkupParserInterface {
            public function parse($content, $locale)
            {
                // a simple dummy html markup parser for sulu links
                return \str_replace(
                    ['<sulu-link href="123-123-123">', '</sulu-link>'],
                    ['<a href="/' . $locale . '/123-123-123">', '</a>'],
                    $content
                );
            }

            public function validate($content, $locale)
            {
                return [];
            }
        };

        $this->requestStack = new RequestStack();
        $this->defaultLocale = 'en';

        $this->mailerListener = new MailerListener(
            $this->markupParser,
            $this->requestStack,
            $this->defaultLocale
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                MessageEvent::class => 'onMessage',
            ],
            MailerListener::getSubscribedEvents()
        );
    }

    public function testOnMessageNoEmail(): void
    {
        $event = $this->createMessageEvent(new RawMessage('test'));

        $this->mailerListener->onMessage($event);

        $this->assertSame('test', $event->getMessage()->toString());
    }

    public function testOnMessageEmailNoHtml(): void
    {
        $event = $this->createMessageEvent((new Email())->text('test'));

        $this->mailerListener->onMessage($event);

        /** @var Email $message */
        $message = $event->getMessage();
        $this->assertEmpty($message->getHtmlBody());
        $this->assertSame('test', $message->getTextBody());
    }

    public function testOnMessageEmailHtml(): void
    {
        $event = $this->createMessageEvent((new Email())->html('<p>Test</p>'));

        $this->mailerListener->onMessage($event);

        /** @var Email $message */
        $message = $event->getMessage();
        $this->assertSame('<p>Test</p>', $message->getHtmlBody());
    }

    public function testOnMessageEmailLink(): void
    {
        $event = $this->createMessageEvent((new Email())->html('<sulu-link href="123-123-123">Test</sulu-link>'));

        $this->mailerListener->onMessage($event);

        /** @var Email $message */
        $message = $event->getMessage();
        $this->assertSame('<a href="/en/123-123-123">Test</a>', $message->getHtmlBody());
    }

    public function testOnMessageEmailLocalizedLink(): void
    {
        $request = Request::create('/test');
        $request->setLocale('de');
        $this->requestStack->push($request);

        $event = $this->createMessageEvent((new Email())->html('<sulu-link href="123-123-123">Test</sulu-link>'));

        $this->mailerListener->onMessage($event);

        /** @var Email $message */
        $message = $event->getMessage();
        $this->assertSame('<a href="/de/123-123-123">Test</a>', $message->getHtmlBody());
    }

    private function createMessageEvent(RawMessage $message): MessageEvent
    {
        $address = new Address('test@localhost');

        return new MessageEvent($message, new Envelope($address, [$address]), 'transport', false);
    }
}
