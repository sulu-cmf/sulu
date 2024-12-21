<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Unit\Twig;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TagBundle\Twig\TagTwigExtension;
use Sulu\Component\Cache\Memoize;
use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\Tag\Request\TagRequestHandler;
use Sulu\Component\Tag\Request\TagRequestHandlerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TagTwigExtensionTest extends TestCase
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

    public static function getProvider()
    {
        return [
            [[]],
            [[['name' => 'sulu']]],
            [[['name' => 'sulu'], ['name' => 'core']]],
            [[['name' => 'sulu'], ['name' => 'core'], ['name' => 'massive art']]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getProvider')]
    public function testGetTags($tagData): void
    {
        $tags = [];
        foreach ($tagData as $tagItem) {
            $tag = new Tag();
            $tag->setName($tagItem['name']);

            $tags[] = $tag;
        }

        $tagManager = $this->prophesize(TagManagerInterface::class);
        $tagManager->findAll()->shouldBeCalled()->willReturn($tags);

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $serializer->serialize($tags, Argument::type(SerializationContext::class))
            ->shouldBeCalled()->willReturn($tagData);
        $tagRequestHandler = $this->prophesize(TagRequestHandlerInterface::class);

        $tagExtension = new TagTwigExtension(
            $tagManager->reveal(),
            $tagRequestHandler->reveal(),
            $serializer->reveal(),
            $this->getMemoizeCache()
        );
        $this->assertEquals($tagData, $tagExtension->getTagsFunction());
    }

    public static function appendProvider()
    {
        return [
            ['t', '/test', 'Sulu,Core', 'Sulu,Core,Test'],
            ['tags', '/asdf', 'Sulu,Core', 'Sulu,Core,Test'],
            ['t', '/asdf', 'Sulu,Core', 'Sulu,Core,Test'],
            ['tags', '/test', 'Sulu,Core', 'Sulu,Core,Test'],
            ['tags', '/test', 'Sulu,Test', 'Sulu,Test'],
            ['tags', '/test', '', 'Test'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('appendProvider')]
    public function testAppendTagUrl($tagsParameter, $url, $tagsString, $expected): void
    {
        $tag = ['name' => 'Test'];

        $tagManager = $this->prophesize(TagManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$tagsParameter => $tagsString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($tagsParameter, '')->willReturn($tagsString);
        $request->getPathInfo()->willReturn($url);

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $tagRequestHandler = new TagRequestHandler($requestStack->reveal());

        $tagExtension = new TagTwigExtension(
            $tagManager->reveal(),
            $tagRequestHandler,
            $serializer->reveal(),
            $this->getMemoizeCache()
        );
        $result = $tagExtension->appendTagUrlFunction($tag, $tagsParameter);

        $this->assertEquals($url . '?' . $tagsParameter . '=' . \urlencode($expected), $result);
    }

    public static function setProvider()
    {
        return [
            ['t', '/test', 'Sulu,Core', 'Test'],
            ['tags', '/asdf', 'Sulu,Core', 'Test'],
            ['t', '/asdf', 'Sulu,Core', 'Test'],
            ['tags', '/test', 'Sulu,Core', 'Test'],
            ['tags', '/test', 'Sulu,Test', 'Test'],
            ['tags', '/test', '', 'Test'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('setProvider')]
    public function testSetTagUrl($tagsParameter, $url, $tagsString, $expected): void
    {
        $tag = ['name' => 'Test'];

        $tagManager = $this->prophesize(TagManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$tagsParameter => $tagsString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($tagsParameter, '')->willReturn($tagsString);
        $request->getPathInfo()->willReturn($url);

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $tagRequestHandler = new TagRequestHandler($requestStack->reveal());

        $tagExtension = new TagTwigExtension(
            $tagManager->reveal(),
            $tagRequestHandler,
            $serializer->reveal(),
            $this->getMemoizeCache()
        );
        $result = $tagExtension->setTagUrlFunction($tag, $tagsParameter);

        $this->assertEquals($url . '?' . $tagsParameter . '=' . \urlencode($expected), $result);
    }

    public static function clearProvider()
    {
        return [
            ['t', '/test', 'Sulu,Core'],
            ['t', '/asdf', 'Sulu,Core'],
            ['tags', '/asdf', 'Sulu,Core'],
            ['tags', '/test', 'Sulu,Core'],
            ['tags', '/test', 'Sulu,Test'],
            ['tags', '/test', ''],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('clearProvider')]
    public function testClearTagUrl($tagsParameter, $url, $tagsString): void
    {
        $tagManager = $this->prophesize(TagManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new InputBag([$tagsParameter => $tagsString]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($tagsParameter, '')->willReturn($tagsString);
        $request->getPathInfo()->willReturn($url);

        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $tagRequestHandler = new TagRequestHandler($requestStack->reveal());

        $tagExtension = new TagTwigExtension(
            $tagManager->reveal(),
            $tagRequestHandler,
            $serializer->reveal(),
            $this->getMemoizeCache()
        );
        $result = $tagExtension->clearTagUrlFunction($tagsParameter);

        $this->assertEquals($url, $result);
    }
}
