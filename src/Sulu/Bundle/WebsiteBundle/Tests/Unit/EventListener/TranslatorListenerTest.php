<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\EventListener\TranslatorListener;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Translation\Translator;

class TranslatorListenerTest extends TestCase
{
    /**
     * @var ObjectProphecy<HttpKernelInterface>
     */
    private $kernel;

    public function setUp(): void
    {
        $this->kernel = $this->prophesize(HttpKernelInterface::class);
    }

    public function testOnKernelRequest(): void
    {
        $translator = $this->prophesize(Translator::class);
        $request = new Request();
        $localization = new Localization('de', 'at');
        $requestAttributes = new RequestAttributes([
            'localization' => $localization,
        ]);
        $request->attributes->set('_sulu', $requestAttributes);

        $event = $this->createRequestEvent($request);

        $eventListener = new TranslatorListener($translator->reveal());

        $translator->setLocale('de_AT')->shouldBeCalled();

        $eventListener->onKernelRequest($event);
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->kernel->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
    }
}
