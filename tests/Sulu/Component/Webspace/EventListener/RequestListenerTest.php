<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\EventListener;

use Prophecy\Argument;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestListener
     */
    private $requestListener;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var GetResponseEvent
     */
    private $getResponseEvent;

    public function setUp()
    {
        parent::setUp();

        $this->getResponseEvent = $this->prophesize('Symfony\Component\HttpKernel\Event\GetResponseEvent');

        $this->requestAnalyzer = $this->prophesize('Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface');

        $this->requestListener = new RequestListener($this->requestAnalyzer->reveal());
    }

    public function testAnalyze()
    {
        $this->getResponseEvent->isMasterRequest()->willReturn(true);
        $this->getResponseEvent->getRequest()->willReturn(new Request());

        $this->requestListener->onKernelRequest($this->getResponseEvent->reveal());

        $this->requestAnalyzer->analyze(Argument::any())->shouldHaveBeenCalled();
    }

    public function testNoAnalyzerForSubRequest()
    {
        $this->getResponseEvent->isMasterRequest()->willReturn(false);

        $this->requestListener->onKernelRequest($this->getResponseEvent->reveal());

        $this->requestAnalyzer->analyze(Argument::any())->shouldNotHaveBeenCalled();
    }
}
