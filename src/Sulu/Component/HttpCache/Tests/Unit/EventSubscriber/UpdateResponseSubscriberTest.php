<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Tests\Unit\EventSubscriber;

use Prophecy\Argument;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\EventSubscriber\UpdateResponseSubscriber;
use Sulu\Component\HttpCache\HandlerInterface;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;
use Sulu\Component\HttpCache\HandlerUpdateResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class UpdateResponseSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var UpdateResponseSubscriber
     */
    private $subscriber;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * @var GetResponseEvent
     */
    private $getResponseEvent;

    /**
     * @var FilterResponseEvent
     */
    private $filterResponseEvent;

    public function setUp()
    {
        parent::setUp();

        $this->getResponseEvent = $this->prophesize(GetResponseEvent::class);
        $this->filterResponseEvent = $this->prophesize(FilterResponseEvent::class);
        $this->structure = $this->prophesize(StructureInterface::class);
        $this->handler = $this->prophesize(HandlerUpdateResponseInterface::class)
            ->willImplement(HandlerInvalidateStructureInterface::class);

        $this->response = new Response();
        $this->request = new Request();

        $this->subscriber = new UpdateResponseSubscriber(
            $this->handler->reveal()
        );
    }

    public function provideLifecycle()
    {
        return [
            // INVALIDATE: Is master request, has a structure and is not a preview
            [
                [
                    'is_master_request' => true,
                    'has_structure' => true,
                    'preview' => false,
                ],
                true,
            ],
            // NO INVALIDATE: Has not structure
            [
                [
                    'is_master_request' => true,
                    'has_structure' => false,
                    'preview' => false,
                ],
                false,
            ],
            // NO INVALIDATE: Is preview
            [
                [
                    'is_master_request' => true,
                    'has_structure' => true,
                    'preview' => true,
                ],
                false,
            ],
        ];
    }

    /**
     * @dataProvider provideLifecycle
     */
    public function testLifecycle($options, $shouldInvalidate)
    {
        if ($options['has_structure']) {
            $this->request->attributes->set('structure', $this->structure->reveal());
        }

        if ($options['preview']) {
            $this->request->query->set('preview', true);
        }

        $this->filterResponseEvent->getResponse()->willReturn($this->response);
        $this->filterResponseEvent->getRequest()->willReturn($this->request);
        $this->filterResponseEvent->isMasterRequest()->willReturn($options['is_master_request']);

        $invalidateProphecy = $this->handler->updateResponse($this->response, Argument::any());

        if ($shouldInvalidate) {
            $invalidateProphecy->shouldBeCalled();
        } else {
            $invalidateProphecy->shouldNotBeCalled();
        }

        $this->subscriber->onResponse($this->filterResponseEvent->reveal());
    }
}
