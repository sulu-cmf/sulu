<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tests\Unit\Markup;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\MarkupBundle\Markup\LinkTag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\RequestContext;

class LinkTagTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<LinkProviderInterface>[]
     */
    private $providers;

    /**
     * @var ObjectProphecy<LinkProviderPoolInterface>
     */
    private $providerPool;

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var LinkTag
     */
    private $linkTag;

    protected function setUp(): void
    {
        $providers = $this->providers = [
            'page' => $this->prophesize(LinkProviderInterface::class),
            'article' => $this->prophesize(LinkProviderInterface::class),
        ];
        $this->providerPool = $this->prophesize(LinkProviderPoolInterface::class);
        $this->providerPool->getProvider(Argument::any())->will(
            function($arguments) use ($providers) {
                return $providers[$arguments[0]];
            }
        );

        $requestContext = new RequestContext('', 'GET', 'sulu.lo');
        $this->urlHelper = new UrlHelper(new RequestStack(), $requestContext);

        $this->linkTag = new LinkTag($this->providerPool->reveal(), false, $this->urlHelper);
    }

    /**
     * @return mixed[]
     */
    public static function provideParseData(): array
    {
        return [
            [
                '<sulu-link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test" title="Test-Title">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title" provider="article" remove-if-not-exists="true">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                    'remove-if-not-exists' => true,
                ],
                [],
                '',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title" provider="article"/>',
                ['href' => '123-123-123', 'title' => 'Test-Title', 'provider' => 'article'],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test" title="Test-Title">Page-Title</a>',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title" provider="article"></sulu-link>',
                ['href' => '123-123-123', 'title' => 'Test-Title', 'provider' => 'article'],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test" title="Test-Title">Page-Title</a>',
            ],
            [
                '<sulu-link href="123-123-123" provider="article">Test-Content</sulu-link>',
                ['href' => '123-123-123', 'content' => 'Test-Content', 'provider' => 'article'],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title" target="_blank" provider="article">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test" title="Test-Title" target="_blank">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title" target="_self" provider="article">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'target' => '_self',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test" title="Test-Title" target="_self">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123" provider="article" class="test">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123',
                    'provider' => 'article',
                    'class' => 'test',
                    'content' => 'Test-Content',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test" class="test">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title" class="test" provider="article">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'class' => 'test',
                    'provider' => 'article',
                    'content' => 'Test-Content',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test" title="Test-Title" class="test">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123#anchor" provider="article" title="Test-Title">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123#anchor',
                    'title' => 'Test-Title',
                    'provider' => 'article',
                    'content' => 'Test-Content',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test#anchor" title="Test-Title">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123?query=parameter" provider="article" title="Test-Title">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123?query=parameter',
                    'title' => 'Test-Title',
                    'provider' => 'article',
                    'content' => 'Test-Content',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test?query=parameter" title="Test-Title">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123?query=parameter#anchor" provider="article" title="Test-Title">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123?query=parameter#anchor',
                    'title' => 'Test-Title',
                    'provider' => 'article',
                    'content' => 'Test-Content',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test?query=parameter#anchor" title="Test-Title">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123#anchor?not=query" provider="article" title="Test-Title">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123#anchor?not=query',
                    'title' => 'Test-Title',
                    'provider' => 'article',
                    'content' => 'Test-Content',
                ],
                [new LinkItem('123-123-123', 'Page-Title', 'de/test', true)],
                '<a href="http://sulu.lo/de/test#anchor?not=query" title="Test-Title">Test-Content</a>',
            ],
            [
                '<sulu-link href="123-123-123" title="Test-Title" provider="article" data-provider="dummy">Test-Content</sulu-link>',
                [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                    'data-provider' => 'dummy',
                ],
                [new LinkItem('123-123-123', 'Page-Title', '/de/test', true)],
                '<a href="http://sulu.lo/de/test" title="Test-Title" data-provider="dummy">Test-Content</a>',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideParseData')]
    public function testParseAll($tag, $attributes, $items, $expected): void
    {
        $uuid = \preg_split('/[#?]/', $attributes['href'], 2);
        $uuids = [
            $uuid ? $uuid[0] : $attributes['href'],
        ];

        $this->providers[$attributes['provider']]->preload($uuids, 'de', true)->willReturn($items);

        $result = $this->linkTag->parseAll([$tag => $attributes], 'de');

        $this->assertEquals([$tag => $expected], $result);
    }

    public function testParseAllWithoutUrlHelper(): void
    {
        $this->linkTag = new LinkTag($this->providerPool->reveal(), true, null);

        $tag = '<sulu-link href="123-123-123" title="Test-Title" provider="article"/>';

        $this->providers['article']->preload(['123-123-123'], 'de', true)
            ->willReturn([new LinkItem('123-123-123', 'Page-Title', '/de/test', true)]);

        $result = $this->linkTag->parseAll(
            [$tag => ['href' => '123-123-123', 'title' => 'Test-Title', 'provider' => 'article']],
            'de'
        );

        $this->assertEquals(
            [$tag => '<a href="/de/test" title="Test-Title">Page-Title</a>'],
            $result
        );
    }

    public function testParseAllMultipleTags(): void
    {
        $this->providers['article']->preload(['123-123-123', '312-312-312'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title 1', '/de/test-1', true),
                    new LinkItem('312-312-312', 'Page-Title 2', '/de/test-2', true),
                ]
            );

        $tag1 = '<sulu-link href="123-123-123" provider="article">Test-Content</sulu-link>';
        $tag2 = '<sulu-link href="123-123-123" title="Test-Title" provider="article"/>';
        $tag3 = '<sulu-link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu-link>';
        $tag4 = '<sulu-link href="123-123-123" title="Test-Title" target="_blank" provider="article">Test-Content</sulu-link>';

        $result = $this->linkTag->parseAll(
            [
                $tag1 => ['href' => '123-123-123', 'content' => 'Test-Content', 'provider' => 'article'],
                $tag2 => ['href' => '312-312-312', 'title' => 'Test-Title', 'provider' => 'article'],
                $tag3 => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                $tag4 => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => '<a href="http://sulu.lo/de/test-1">Test-Content</a>',
                $tag2 => '<a href="http://sulu.lo/de/test-2" title="Test-Title">Page-Title 2</a>',
                $tag3 => '<a href="http://sulu.lo/de/test-1" title="Test-Title">Test-Content</a>',
                $tag4 => '<a href="http://sulu.lo/de/test-1" title="Test-Title" target="_blank">Test-Content</a>',
            ],
            $result
        );
    }

    public function testParseAllMultipleProvider(): void
    {
        $this->providers['page']->preload(['312-312-312'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('312-312-312', 'Page-Title 1', '/de/test-1', true),
                ]
            );
        $this->providers['article']->preload(['123-123-123'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title 2', '/de/test-2', true),
                ]
            );

        $tag1 = '<sulu-link href="123-123-123" provider="article"/>';
        $tag2 = '<sulu-link href="312-312-312" provider="page"/>';

        $result = $this->linkTag->parseAll(
            [
                $tag1 => ['href' => '123-123-123', 'provider' => 'article'],
                $tag2 => ['href' => '312-312-312', 'provider' => 'page'],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => '<a href="http://sulu.lo/de/test-2">Page-Title 2</a>',
                $tag2 => '<a href="http://sulu.lo/de/test-1">Page-Title 1</a>',
            ],
            $result
        );
    }

    public function testParseAllMultipleProviderSameId(): void
    {
        $this->providers['page']->preload(['123-123-123'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title 1', '/de/test-1', true),
                ]
            );
        $this->providers['article']->preload(['123-123-123'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title 2', '/de/test-2', true),
                ]
            );

        $tag1 = '<sulu-link href="123-123-123" provider="article"/>';
        $tag2 = '<sulu-link href="123-123-123" provider="page"/>';

        $result = $this->linkTag->parseAll(
            [
                $tag1 => ['href' => '123-123-123', 'provider' => 'article'],
                $tag2 => ['href' => '123-123-123', 'provider' => 'page'],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => '<a href="http://sulu.lo/de/test-2">Page-Title 2</a>',
                $tag2 => '<a href="http://sulu.lo/de/test-1">Page-Title 1</a>',
            ],
            $result
        );
    }

    public function testParseAllMultipleTagsMissingContent(): void
    {
        $tag1 = '<sulu-link href="123-123-123" provider="article">Test-Content</sulu-link>';
        $tag2 = '<sulu-link href="123-123-123" title="Test-Title" provider="article"/>';
        $tag3 = '<sulu-link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu-link>';
        $tag4 = '<sulu-link href="123-123-123" provider="article"/>';

        $this->providers['article']->preload(['123-123-123'], 'de', true)->willReturn([]);

        $result = $this->linkTag->parseAll(
            [
                $tag1 => ['href' => '123-123-123', 'content' => 'Test-Content', 'provider' => 'article'],
                $tag2 => ['href' => '123-123-123', 'title' => 'Test-Title', 'provider' => 'article'],
                $tag3 => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                $tag4 => ['href' => '123-123-123', 'provider' => 'article'],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => 'Test-Content',
                $tag2 => 'Test-Title',
                $tag3 => 'Test-Content',
                $tag4 => '',
            ],
            $result
        );
    }

    public function testParseAllValidationState(): void
    {
        $this->providers['article']->preload(['123-123-123', '456-123-123', '456-789-123'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title 1', '/de/test-1', true),
                ]
            );

        $tag1 = '<sulu-link href="123-123-123" title="Test-Title" target="_blank" provider="article" sulu-validation-state="unpublished">Test-Content</sulu-link>';
        $tag2 = '<sulu-link href="456-123-123" title="Test-Title" target="_blank" provider="article" sulu-validation-state="unpublished">Test-Content</sulu-link>';
        $tag3 = '<sulu-link href="456-789-123" title="Test-Title" target="_blank" provider="article" sulu-validation-state="removed">Test-Content</sulu-link>';
        $tag4 = '<sulu-link provider="article" sulu-validation-state="removed">Test-Content</sulu-link>';

        $result = $this->linkTag->parseAll(
            [
                $tag1 => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                    'sulu-validation-state' => 'unpublished',
                ],
                $tag2 => [
                    'href' => '456-123-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                    'sulu-validation-state' => 'unpublished',
                ],
                $tag3 => [
                    'href' => '456-789-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                    'sulu-validation-state' => 'removed',
                ],
                $tag4 => [
                    'provider' => 'article',
                    'content' => 'Test-Content',
                    'sulu-validation-state' => 'removed',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => '<a href="http://sulu.lo/de/test-1" title="Test-Title" target="_blank">Test-Content</a>',
                $tag2 => 'Test-Content',
                $tag3 => 'Test-Content',
                $tag4 => 'Test-Content',
            ],
            $result
        );
    }

    public function testParseAllValidationStateInPreview(): void
    {
        $this->linkTag = new LinkTag($this->providerPool->reveal(), true, $this->urlHelper);

        $this->providers['article']->preload(['123-123-123', '456-123-123', '456-789-123'], 'de', true)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title 1', '/de/test-1', true),
                ]
            );

        $tag1 = '<sulu-link href="123-123-123" title="Test-Title" target="_blank" provider="article" sulu-validation-state="unpublished">Test-Content</sulu-link>';
        $tag2 = '<sulu-link href="456-123-123" title="Test-Title" target="_blank" provider="article" sulu-validation-state="unpublished">Test-Content</sulu-link>';
        $tag3 = '<sulu-link href="456-789-123" title="Test-Title" target="_blank" provider="article" sulu-validation-state="removed">Test-Content</sulu-link>';
        $tag4 = '<sulu-link provider="article" sulu-validation-state="removed">Test-Content</sulu-link>';

        $result = $this->linkTag->parseAll(
            [
                $tag1 => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                    'sulu-validation-state' => 'unpublished',
                ],
                $tag2 => [
                    'href' => '456-123-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                    'sulu-validation-state' => 'unpublished',
                ],
                $tag3 => [
                    'href' => '456-789-123',
                    'title' => 'Test-Title',
                    'target' => '_blank',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                    'sulu-validation-state' => 'removed',
                ],
                $tag4 => [
                    'provider' => 'article',
                    'content' => 'Test-Content',
                    'sulu-validation-state' => 'removed',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [
                $tag1 => '<a href="http://sulu.lo/de/test-1" title="Test-Title" target="_blank">Test-Content</a>',
                $tag2 => '<a title="Test-Title" target="_blank">Test-Content</a>',
                $tag3 => 'Test-Content',
                $tag4 => 'Test-Content',
            ],
            $result
        );
    }

    public function testParseAllWithProviderAttribute(): void
    {
        $this->linkTag = new LinkTag($this->providerPool->reveal(), true, $this->urlHelper, 'data-provider');

        $tag = '<sulu-link href="123-123-123" title="Test-Title" provider="article"/>';

        $this->providers['article']->preload(['123-123-123'], 'de', true)
            ->willReturn([new LinkItem('123-123-123', 'Page-Title', '/de/test', true)]);

        $result = $this->linkTag->parseAll(
            [
                $tag => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [$tag => '<a href="http://sulu.lo/de/test" title="Test-Title" data-provider="article">Page-Title</a>'],
            $result
        );
    }

    public function testValidate(): void
    {
        $this->providers['article']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test', true),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals([], $result);
    }

    public function testValidateWithAnchor(): void
    {
        $this->providers['article']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test', true),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123#anchor" title="Test-Title" provider="article">Test-Content</sulu-link>' => [
                    'href' => '123-123-123#anchor',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals([], $result);
    }

    public function testValidateInvalid(): void
    {
        $this->providers['article']->preload(['123-123-123'], 'de', false)->willReturn([]);

        $result = $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            ['<sulu-link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu-link>' => LinkTag::VALIDATE_REMOVED],
            $result
        );
    }

    public function testValidateUnpublished(): void
    {
        $this->providers['article']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test', false),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            ['<sulu-link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu-link>' => LinkTag::VALIDATE_UNPUBLISHED],
            $result
        );
    }

    public function testValidateMixed(): void
    {
        $this->providers['article']->preload(['123-123-123', '312-312-312', '123-456-789'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test', true),
                    new LinkItem('123-456-789', 'Page-Title', '/de/test', false),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" title="Test-Title" provider="article">Test-Content</sulu-link>' => [
                    'href' => '123-123-123',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                '<sulu-link href="312-312-312" title="Test-Title" provider="article">Test-Content</sulu-link>' => [
                    'href' => '312-312-312',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
                '<sulu-link href="123-312-123" title="Test-Title" provider="article">Test-Content</sulu-link>' => [
                    'href' => '123-456-789',
                    'title' => 'Test-Title',
                    'content' => 'Test-Content',
                    'provider' => 'article',
                ],
            ],
            'de'
        );

        $this->assertEquals(
            [
                '<sulu-link href="312-312-312" title="Test-Title" provider="article">Test-Content</sulu-link>' => LinkTag::VALIDATE_REMOVED,
                '<sulu-link href="123-312-123" title="Test-Title" provider="article">Test-Content</sulu-link>' => LinkTag::VALIDATE_UNPUBLISHED,
            ],
            $result
        );
    }

    public function testValidateAllMultipleProviders(): void
    {
        $this->providers['page']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test-1', true),
                ]
            );
        $this->providers['article']->preload([1], 'de', false)
            ->willReturn(
                [
                    new LinkItem(1, 'Article-Title', '/de/test-2', true),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu-link href="1" provider="article"/>' => [
                    'href' => 1,
                    'provider' => 'article',
                ],
                '<sulu-link href="123-123-123" provider="page"/>' => [
                    'href' => '123-123-123',
                    'provider' => 'page',
                ],
            ],
            'de'
        );

        $this->assertEquals([], $result);
    }

    public function testValidateAllMultipleProvidersSameId(): void
    {
        $this->providers['page']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Page-Title', '/de/test-1', true),
                ]
            );
        $this->providers['article']->preload(['123-123-123'], 'de', false)
            ->willReturn(
                [
                    new LinkItem('123-123-123', 'Article-Title', '/de/test-2', true),
                ]
            );

        $result = $this->linkTag->validateAll(
            [
                '<sulu-link href="123-123-123" provider="article"/>' => [
                    'href' => '123-123-123',
                    'provider' => 'article',
                ],
                '<sulu-link href="123-123-123" provider="page"/>' => [
                    'href' => '123-123-123',
                    'provider' => 'page',
                ],
            ],
            'de'
        );

        $this->assertEquals([], $result);
    }
}
