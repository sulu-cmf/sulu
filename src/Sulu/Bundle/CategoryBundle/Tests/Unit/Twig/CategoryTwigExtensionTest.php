<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Twig;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\CategoryBundle\Api\Category as ApiCategory;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface as EntityCategory;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotFoundException;
use Sulu\Bundle\CategoryBundle\Twig\CategoryTwigExtension;
use Sulu\Component\Cache\Memoize;
use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Category\Request\CategoryRequestHandler;
use Sulu\Component\Category\Request\CategoryRequestHandlerInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CategoryTwigExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * Returns memoize cache instance.
     *
     * @return MemoizeInterface
     */
    private function getMemoizeCache()
    {
        return new Memoize(DoctrineProvider::wrap(new ArrayAdapter()), 0);
    }

    /**
     * Returns ApiCategory with given Data (id, name).
     *
     * @param array{id: int, name: string} $data
     *
     * @return ApiCategory
     */
    private function createCategoryEntity(array $data)
    {
        $category = $this->prophesize(ApiCategory::class);
        $category->getId()->willReturn($data['id']);
        $category->getName()->willReturn($data['name']);

        return $category->reveal();
    }

    /**
     * @param array{id: int, name: string} $data
     *
     * @return EntityCategory
     */
    private function createCategoryApi(array $data)
    {
        $category = $this->prophesize(EntityCategory::class);
        $category->getId()->willReturn($data['id']);

        return $category->reveal();
    }

    /**
     * @return array<array{0:array<mixed>, 1?:string, 2?:string, 3?:int}>
     */
    public static function getProvider(): array
    {
        return [
            [[]],
            [[['id' => 1, 'name' => 'sulu']]],
            [[['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core']]],
            [[['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core'], ['id' => 3, 'name' => 'massive']]],
            [[], 'de', '5'],
            [[['id' => 1, 'name' => 'sulu']], 'de', '5'],
            [[['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core']], 'de', '5'],
            [[['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core'], ['id' => 3, 'name' => 'massive']], 'de', '5'],
            [[], 'de', '5', 1],
            [[['id' => 1, 'name' => 'sulu']], 'de', '5', 1],
            [[['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core']], 'de', '5', 1],
            [
                [['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core'], ['id' => 3, 'name' => 'massive']],
                'de',
                '5',
                1,
            ],
        ];
    }

    /**
     * @param array<array{id:int, name: string}> $categoryData
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getProvider')]
    public function testGet(array $categoryData, string $locale = 'en', ?string $parent = null, ?int $depth = null): void
    {
        $categoryEntities = [];
        $categoryApis = [];
        foreach ($categoryData as $category) {
            $categoryEntities[] = $this->createCategoryEntity($category);
            $categoryApis[] = $this->createCategoryApi($category);
        }

        $manager = $this->prophesize(CategoryManagerInterface::class);
        $manager->findChildrenByParentKey($parent)->shouldBeCalled()->willReturn($categoryEntities);
        $manager->getApiObjects($categoryEntities, $locale)->shouldBeCalled()->willReturn($categoryApis);

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $serializer->serialize($categoryApis, Argument::type(SerializationContext::class))
            ->shouldBeCalled()->willReturn($categoryData);

        $requestHandler = $this->prophesize(CategoryRequestHandlerInterface::class);
        $extension = new CategoryTwigExtension(
            $manager->reveal(),
            $requestHandler->reveal(),
            $serializer->reveal(),
            $this->getMemoizeCache()
        );

        $this->assertEquals($categoryData, $extension->getCategoriesFunction($locale, $parent, $depth));
    }

    /**
     * @return array<array{string, string, string, string}>
     */
    public static function appendProvider(): array
    {
        return [
            ['c', '/test', '1,2', '1,2,3'],
            ['categories', '/asdf', '1,2', '1,2,3'],
            ['c', '/asdf', '1,2', '1,2,3'],
            ['c', '/asdf', '2,1', '2,1,3'],
            ['categories', '/test', '1,2', '1,2,3'],
            ['categories', '/test', '1,3', '1,3'],
            ['categories', '/test', '', '3'],
        ];
    }

    public function testGetReturnsEmptyOnCategoryKeyNotFoundException(): void
    {
        $exception = new CategoryKeyNotFoundException('abc');

        $manager = $this->prophesize(CategoryManagerInterface::class);

        $manager->findChildrenByParentKey('abc')->shouldBeCalled()->willThrow($exception);
        $manager->getApiObjects()->shouldNotBeCalled();

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $serializer->serialize()->shouldNotBeCalled();

        $requestHandler = $this->prophesize(CategoryRequestHandlerInterface::class);

        $extension = new CategoryTwigExtension(
            $manager->reveal(),
            $requestHandler->reveal(),
            $serializer->reveal(),
            $this->getMemoizeCache()
        );

        $result = $extension->getCategoriesFunction('en', 'abc');
        $this->assertSame([], $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('appendProvider')]
    public function testAppendUrl(string $parameter, string $url, string $string, string $expected): void
    {
        $category = ['id' => 3, 'name' => 'test'];

        $manager = $this->prophesize(CategoryManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$parameter => $string]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($string);
        $request->getPathInfo()->willReturn($url);

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $requestHandler = new CategoryRequestHandler($requestStack->reveal());

        $extension = new CategoryTwigExtension(
            $manager->reveal(),
            $requestHandler,
            $serializer->reveal(),
            $this->getMemoizeCache()
        );
        $result = $extension->appendCategoryUrlFunction($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . \urlencode($expected), $result);
    }

    /**
     * @return array<array{string, string, string, string}>
     */
    public static function setProvider(): array
    {
        return [
            ['c', '/test', '1,2', '3'],
            ['categories', '/asdf', '1,2', '3'],
            ['c', '/asdf', '1,2', '3'],
            ['c', '/asdf', '2,1', '3'],
            ['categories', '/test', '2,1', '3'],
            ['categories', '/test', '1,2', '3'],
            ['categories', '/test', '1,2', '3'],
            ['categories', '/test', '', '3'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('setProvider')]
    public function testSetUrl(string $parameter, string $url, string $string, string $expected): void
    {
        $category = ['id' => 3, 'name' => 'test'];

        $manager = $this->prophesize(CategoryManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$parameter => $string]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($string);
        $request->getPathInfo()->willReturn($url);

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $requestHandler = new CategoryRequestHandler($requestStack->reveal());

        $extension = new CategoryTwigExtension(
            $manager->reveal(),
            $requestHandler,
            $serializer->reveal(),
            $this->getMemoizeCache()
        );
        $result = $extension->setCategoryUrlFunction($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . \urlencode($expected), $result);
    }

    /** @return array<array{string, string, string}> */
    public static function clearProvider(): array
    {
        return [
            ['c', '/test', '1,2'],
            ['c', '/asdf', '1,2'],
            ['categories', '/asdf', '1,2'],
            ['categories', '/test', '1,2'],
            ['categories', '/test', '1,2'],
            ['categories', '/test', ''],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('clearProvider')]
    public function testClearUrl(string $parameter, string $url, string $string): void
    {
        $manager = $this->prophesize(CategoryManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$parameter => $string]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($string);
        $request->getPathInfo()->willReturn($url);

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $requestHandler = new CategoryRequestHandler($requestStack->reveal());

        $tagExtension = new CategoryTwigExtension(
            $manager->reveal(),
            $requestHandler,
            $serializer->reveal(),
            $this->getMemoizeCache()
        );
        $result = $tagExtension->clearCategoryUrlFunction($parameter);

        $this->assertEquals($url, $result);
    }
}
