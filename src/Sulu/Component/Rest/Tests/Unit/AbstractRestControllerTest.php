<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;

class AbstractRestControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var AbstractRestController
     */
    protected $controller;

    public function setUp(): void
    {
        $viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $this->controller = new class($viewHandler->reveal()) extends AbstractRestController {
        };
    }

    public function testResponseGetById(): void
    {
        $method = new \ReflectionMethod(AbstractRestController::class, 'responseGetById');
        $method->setAccessible(true);

        $id = 1;
        $findCallback = function($id) {
            return ['id' => $id];
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $findCallback);

        $this->assertEquals(200, $view->getStatusCode());
        $this->assertEquals(['id' => 1], $view->getData());
    }

    public function testResponseGetByNotExistingId(): void
    {
        $method = new \ReflectionMethod(AbstractRestController::class, 'responseGetById');
        $method->setAccessible(true);

        $id = 1;
        $findCallback = function($id) {
            return;
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $findCallback);

        $this->assertEquals(404, $view->getStatusCode());
    }

    public function testProcessPutEmpty(): void
    {
        $delete = function() {
            $this->fail('delete should not be called');
        };

        $update = function() {
            $this->fail('update should not be called');
        };

        $add = function() {
            $this->fail('add should not be called');
        };

        $method = new \ReflectionMethod(AbstractRestController::class, 'processPut');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, [], [], $delete, $update, $add);
        $this->assertTrue($result);
    }

    public function testProcessPutWithDelete(): void
    {
        $deleteCalled = false;
        $delete = function() use (&$deleteCalled) {
            $deleteCalled = true;
        };

        $update = function() {
            $this->fail('update should not be called');
        };

        $add = function() {
            $this->fail('add should not be called');
        };

        $method = new \ReflectionMethod(AbstractRestController::class, 'processPut');
        $method->setAccessible(true);

        $object = $this->prophesize(ApiEntity::class);
        $object->getId()->willReturn(1);

        $method->invoke(
            $this->controller,
            [
                $object->reveal(),
            ],
            [],
            $delete,
            $update,
            $add
        );

        $this->assertTrue($deleteCalled);
    }

    public function testProcessPutWithUpdate(): void
    {
        $delete = function() {
            $this->fail('delete should not be called');
        };

        $updateCalled = false;
        $update = function() use (&$updateCalled) {
            $updateCalled = true;
        };

        $add = function() {
            $this->fail('add should not be called');
        };

        $method = new \ReflectionMethod(AbstractRestController::class, 'processPut');
        $method->setAccessible(true);

        $object = $this->prophesize(ApiEntity::class);
        $object->getId()->willReturn(1);

        $method->invoke(
            $this->controller,
            [
                $object->reveal(),
            ],
            [
                [
                    'id' => 1,
                ],
            ],
            $delete,
            $update,
            $add
        );

        $this->assertTrue($updateCalled);
    }

    public function testProcessPutWithAdd(): void
    {
        $delete = function() {
            $this->fail('delete should not be called');
        };

        $update = function() {
            $this->fail('update shoudl not be called');
        };

        $addCalled = false;
        $add = function() use (&$addCalled) {
            $addCalled = true;
        };

        $method = new \ReflectionMethod(AbstractRestController::class, 'processPut');
        $method->setAccessible(true);

        $method->invoke(
            $this->controller,
            [],
            [
                [
                    'id' => 1,
                ],
            ],
            $delete,
            $update,
            $add
        );

        $this->assertTrue($addCalled);
    }

    public function testDelete(): void
    {
        $method = new \ReflectionMethod(AbstractRestController::class, 'responseDelete');
        $method->setAccessible(true);

        $id = 1;
        $deleteCallBack = function($id) {
            return true;
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $deleteCallBack);

        $this->assertEquals(204, $view->getStatusCode());
        $this->assertEquals(null, $view->getData());
    }

    public function testDeleteWithNotExistingEntity(): void
    {
        $method = new \ReflectionMethod(AbstractRestController::class, 'responseDelete');
        $method->setAccessible(true);

        $id = 1;
        $deleteCallBack = function($id) {
            throw new EntityNotFoundException('Sulu\Bundle\CoreBundle\Entity\Example', 7);
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $deleteCallBack);

        $this->assertEquals(404, $view->getStatusCode());
    }

    public function testDeleteWithError(): void
    {
        $method = new \ReflectionMethod(AbstractRestController::class, 'responseDelete');
        $method->setAccessible(true);

        $id = 1;
        $deleteCallBack = function($id) {
            throw new RestException();
        };

        /** @var View $view */
        $view = $method->invoke($this->controller, $id, $deleteCallBack);

        $this->assertEquals(400, $view->getStatusCode());
    }
}
