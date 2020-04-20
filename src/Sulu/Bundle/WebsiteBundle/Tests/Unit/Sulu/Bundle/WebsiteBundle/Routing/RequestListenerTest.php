<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\WebsiteBundle\Routing\RequestListener;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class RequestListenerTest extends TestCase
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PortalInformation
     */
    private $portalInformation;

    /**
     * @var RequestContext
     */
    private $requestContext;

    /**
     * @var HttpKernelInterface
     */
    private $kernel;

    public function setUp(): void
    {
        parent::setUp();

        $this->kernel = $this->prophesize(HttpKernelInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->router = $this->prophesize(RouterInterface::class);
        $this->portalInformation = $this->prophesize(PortalInformation::class);
        $this->requestContext = $this->prophesize(RequestContext::class);
    }

    public function testRequestAnalyzer()
    {
        $this->portalInformation->getPrefix()->willReturn('test/');
        $this->portalInformation->getHost()->willReturn('sulu.io');
        $this->requestAnalyzer->getPortalInformation()->willReturn($this->portalInformation);

        $this->requestContext->hasParameter('prefix')->willReturn(false);
        $this->requestContext->hasParameter('host')->willReturn(false);

        $this->requestContext->setParameter('prefix', 'test/')->shouldBeCalled();
        $this->requestContext->setParameter('host', 'sulu.io')->shouldBeCalled();

        $this->router->getContext()->willReturn($this->requestContext);

        $event = $this->createRequestEvent(new Request());

        $requestListener = new RequestListener($this->router->reveal(), $this->requestAnalyzer->reveal());
        $requestListener->onRequest($event);
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
