<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Category\Tests\Unit\Request;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Category\Request\CategoryRequestHandler;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CategoryRequestHandlerTest extends TestCase
{
    use ProphecyTrait;

    public static function getProvider()
    {
        return [
            ['c', '', []],
            ['c', '1', ['1']],
            ['c', '1,2', ['1', '2']],
            ['c', '1,2,3', ['1', '2', '3']],
            ['c', '1, 2', ['1', '2']],
            ['c', ' 1, 2 ', ['1', '2']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getProvider')]
    public function testGet($parameter, $queryString, $expected): void
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$parameter => $queryString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($queryString);

        $handler = new CategoryRequestHandler($requestStack->reveal());
        $result = $handler->getCategories($parameter);

        $this->assertEquals($expected, $result);
    }

    public static function appendProvider()
    {
        return [
            ['c', '/test', '1,2', '1,2,3'],
            ['categories', '/asdf', '1,2', '1,2,3'],
            ['c', '/asdf', '1,2', '1,2,3'],
            ['c', '/asdf', '2,1', '2,1,3'],
            ['categories', '/test', '1,2', '1,2,3'],
            ['categories', '/test', '2,1', '2,1,3'],
            ['categories', '/test', '1,3', '1,3'],
            ['categories', '/test', '', '3'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('appendProvider')]
    public function testAppendToUrl($parameter, $url, $queryString, $expected): void
    {
        $category = ['id' => 3, 'name' => 'test'];

        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$parameter => $queryString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($queryString);
        $request->getPathInfo()->willReturn($url);

        $handler = new CategoryRequestHandler($requestStack->reveal());
        $result = $handler->appendCategoryToUrl($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . \urlencode($expected), $result);
    }

    public static function removeSingleProvider()
    {
        return [
            ['c', '/test', '1,2,3', '1,2'],
            ['c', '/asdf', '1,2', '1,2'],
            ['c', '/asdf', '3', ''],
            ['categories', '/asdf', '1,2', '1,2'],
            ['categories', '/test', '1,3,2', '1,2'],
            ['categories', '/test', '3,1', '1'],
            ['categories', '/test', '1,3', '1'],
            ['categories', '/test', '', ''],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('removeSingleProvider')]
    public function testRemoveSingleFromUrl($parameter, $url, $queryString, $expected): void
    {
        $category = ['id' => 3, 'name' => 'test'];

        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$parameter => $queryString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($queryString);
        $request->getPathInfo()->willReturn($url);

        $handler = new CategoryRequestHandler($requestStack->reveal());
        $result = $handler->removeCategoryFromUrl($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . \urlencode($expected), $result);
    }

    public static function toggleProvider()
    {
        return [
            ['c', '/test', '1,2', '1,2,3'],
            ['c', '/asdf', '1,3', '1'],
            ['c', '/asdf', '2,1', '2,1,3'],
            ['categories', '/asdf', '1,2', '1,2,3'],
            ['categories', '/test', '3,2', '2'],
            ['categories', '/test', '1,3', '1'],
            ['categories', '/test', '3', ''],
            ['categories', '/test', '', '3'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('toggleProvider')]
    public function testToggleToUrl($parameter, $url, $queryString, $expected): void
    {
        $category = ['id' => 3, 'name' => 'test'];

        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$parameter => $queryString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($queryString);
        $request->getPathInfo()->willReturn($url);

        $handler = new CategoryRequestHandler($requestStack->reveal());
        $result = $handler->toggleCategoryInUrl($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . \urlencode($expected), $result);
    }

    public static function setProvider()
    {
        return [
            ['c', '/test', '1,2', '3'],
            ['categories', '/asdf', '1,2', '3'],
            ['categories', '/asdf', '2,1', '3'],
            ['c', '/asdf', '1,2', '3'],
            ['c', '/asdf', '2,1', '3'],
            ['categories', '/test', '1,2', '3'],
            ['categories', '/test', '1,2', '3'],
            ['categories', '/test', '', '3'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('setProvider')]
    public function testSetToUrl($parameter, $url, $queryString, $expected): void
    {
        $category = ['id' => 3, 'name' => 'test'];

        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$parameter => $queryString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($queryString);
        $request->getPathInfo()->willReturn($url);

        $handler = new CategoryRequestHandler($requestStack->reveal());
        $result = $handler->setCategoryToUrl($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . \urlencode($expected), $result);
    }

    public static function removeProvider()
    {
        return [
            ['c', '/test', '1,2'],
            ['c', '/asdf', '1,2'],
            ['categories', '/asdf', '1,2'],
            ['categories', '/test', '1,2'],
            ['categories', '/test', '2,1'],
            ['categories', '/test', '1,3'],
            ['categories', '/test', ''],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('removeProvider')]
    public function testRemoveFromUrl($parameter, $url, $queryString): void
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$parameter => $queryString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($queryString);
        $request->getPathInfo()->willReturn($url);

        $handler = new CategoryRequestHandler($requestStack->reveal());
        $result = $handler->removeCategoriesFromUrl($parameter);

        $this->assertEquals($url, $result);
    }
}
