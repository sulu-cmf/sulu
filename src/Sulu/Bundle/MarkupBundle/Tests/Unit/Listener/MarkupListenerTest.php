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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MarkupBundle\Listener\MarkupListener;
use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class MarkupListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MarkupParserInterface>
     */
    private $markupParser;

    /**
     * @var ResponseEvent
     */
    private $event;

    /**
     * @var ObjectProphecy<Response>
     */
    private $response;

    /**
     * @var ObjectProphecy<Request>
     */
    private $request;

    /**
     * @var MarkupListener
     */
    private $listener;

    /**
     * @var ObjectProphecy<HttpKernelInterface>
     */
    private $kernel;

    protected function setUp(): void
    {
        $this->markupParser = $this->prophesize(MarkupParserInterface::class);

        $this->kernel = $this->prophesize(HttpKernelInterface::class);

        $this->request = $this->prophesize(Request::class);
        $this->response = $this->prophesize(Response::class);

        $this->event = new ResponseEvent(
            $this->kernel->reveal(),
            $this->request->reveal(),
            HttpKernelInterface::MAIN_REQUEST,
            $this->response->reveal()
        );

        $this->listener = new MarkupListener(['html' => $this->markupParser->reveal()]);
    }

    public function testReplaceMarkup(): void
    {
        $this->request->getRequestFormat(null)->willReturn('html');
        $this->request->getLocale()->willReturn('de');
        $this->response->getContent()->willReturn('<html><sulu-link href="123-123-123"/></html>');

        $this->markupParser->parse('<html><sulu-link href="123-123-123"/></html>', 'de')
            ->willReturn('<html><a href="/test">Page-Title</a></html>')->shouldBeCalled();

        $this->response->setContent('<html><a href="/test">Page-Title</a></html>')
            ->willReturn($this->response->reveal())
            ->shouldBeCalled();

        $this->listener->replaceMarkup($this->event);
    }

    public function testReplaceMarkupWithEmptyContent(): void
    {
        $this->request->getRequestFormat()->willReturn('html');
        $this->request->getLocale()->willReturn('de');
        $this->response->getContent()->willReturn(false);

        $this->markupParser->parse(false, 'de')->willReturn(false);

        $this->response->setContent(Argument::any())->shouldNotBeCalled();
        $this->listener->replaceMarkup($this->event);
    }
}
