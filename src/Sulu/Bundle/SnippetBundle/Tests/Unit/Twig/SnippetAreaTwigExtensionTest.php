<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Bundle\SnippetBundle\Snippet\WrongSnippetTypeException;
use Sulu\Bundle\SnippetBundle\Twig\SnippetAreaTwigExtension;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;

class SnippetAreaTwigExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DefaultSnippetManagerInterface>
     */
    private $defaultSnippetManager;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<Webspace>
     */
    private $webspace;

    /**
     * @var ObjectProphecy<Localization>
     */
    private $localization;

    /**
     * @var ObjectProphecy<SnippetResolverInterface>
     */
    private $snippetResolver;

    /**
     * @var ReferenceStoreInterface
     */
    private $snippetAreaReferenceStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->snippetAreaReferenceStore = new ReferenceStore();
    }

    public function testLoadByArea(): void
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('sulu_io');
        $this->localization = $this->prophesize(Localization::class);
        $this->localization->getLocale()->willReturn('de');
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($this->localization);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);

        $snippet = $this->prophesize(SnippetDocument::class);
        $snippet->getUuid()->willReturn('1234');

        $this->defaultSnippetManager->load('sulu_io', 'test', 'de')->shouldBeCalled()->willReturn($snippet->reveal());
        $this->snippetResolver->resolve(['1234'], 'sulu_io', 'de')->shouldBeCalled()->willReturn([['title' => 'Test Snippet']]);

        $twigExtension = new SnippetAreaTwigExtension(
            $this->defaultSnippetManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->snippetResolver->reveal(),
            $this->snippetAreaReferenceStore
        );

        self::assertNotContains('test', $this->snippetAreaReferenceStore->getAll());

        $this->assertEquals(
            ['title' => 'Test Snippet'],
            $twigExtension->loadByArea('test')
        );

        self::assertContains('test', $this->snippetAreaReferenceStore->getAll());
    }

    public function testLoadByAreaWrongType(): void
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('sulu_io');
        $this->localization = $this->prophesize(Localization::class);
        $this->localization->getLocale()->willReturn('de');
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($this->localization);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);

        $snippet = $this->prophesize(SnippetDocument::class);
        $snippet->getUuid()->willReturn('1234');

        $this->defaultSnippetManager->load('sulu_io', 'test', 'de')
            ->shouldBeCalled()
            ->willThrow(new WrongSnippetTypeException('', '', $snippet->reveal()));

        $this->snippetResolver->resolve(['1234'], 'sulu_io', 'de')->shouldNotBeCalled();

        $twigExtension = new SnippetAreaTwigExtension(
            $this->defaultSnippetManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->snippetResolver->reveal(),
            $this->snippetAreaReferenceStore
        );

        $this->assertEquals(null, $twigExtension->loadByArea('test'));
    }

    public function testLoadByAreaNotExist(): void
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('sulu_io');
        $this->localization = $this->prophesize(Localization::class);
        $this->localization->getLocale()->willReturn('de');
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($this->localization);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);

        $this->defaultSnippetManager->load('sulu_io', 'test', 'de')->shouldBeCalled()->willReturn(null);
        $this->snippetResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $twigExtension = new SnippetAreaTwigExtension(
            $this->defaultSnippetManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->snippetResolver->reveal(),
            $this->snippetAreaReferenceStore
        );

        $this->assertEquals(
            null,
            $twigExtension->loadByArea('test')
        );
    }

    public function testLoadByAreaOtherLocale(): void
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('sulu_io');
        $this->localization = $this->prophesize(Localization::class);
        $this->localization->getLocale()->willReturn('de');
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($this->localization);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);

        $snippet = $this->prophesize(SnippetDocument::class);
        $snippet->getUuid()->willReturn('1234');

        $this->defaultSnippetManager->load('sulu_io', 'test', 'en')->shouldBeCalled()->willReturn($snippet->reveal());
        $this->snippetResolver->resolve(['1234'], 'sulu_io', 'en')->shouldBeCalled()->willReturn([['title' => 'Test Snippet EN']]);

        $twigExtension = new SnippetAreaTwigExtension(
            $this->defaultSnippetManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->snippetResolver->reveal(),
            $this->snippetAreaReferenceStore
        );

        $this->assertEquals(
            ['title' => 'Test Snippet EN'],
            $twigExtension->loadByArea('test', null, 'en')
        );
    }

    public function testLoadByAreaOtherWebspace(): void
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('sulu_io');
        $this->localization = $this->prophesize(Localization::class);
        $this->localization->getLocale()->willReturn('de');
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($this->localization);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);

        $snippet = $this->prophesize(SnippetDocument::class);
        $snippet->getUuid()->willReturn('1234');

        $this->defaultSnippetManager->load('demo_io', 'test', 'en')->shouldBeCalled()->willReturn($snippet->reveal());
        $this->snippetResolver->resolve(['1234'], 'demo_io', 'en')->shouldBeCalled()->willReturn([['title' => 'Test Snippet EN']]);

        $twigExtension = new SnippetAreaTwigExtension(
            $this->defaultSnippetManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->snippetResolver->reveal(),
            $this->snippetAreaReferenceStore
        );

        $this->assertEquals(
            ['title' => 'Test Snippet EN'],
            $twigExtension->loadByArea('test', 'demo_io', 'en')
        );
    }
}
