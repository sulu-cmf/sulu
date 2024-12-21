<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestProcessorInterface;
use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestAnalyzerTest extends TestCase
{
    use ProphecyTrait;

    public function testAnalyzeAndValidate(): void
    {
        $provider = $this->prophesize(RequestProcessorInterface::class);
        $request = new Request();

        $provider->process($request, Argument::type(RequestAttributes::class))
            ->shouldBeCalled()->willReturn(new RequestAttributes());
        $provider->validate(Argument::type(RequestAttributes::class))->shouldBeCalled()->willReturn(true);

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request);
        $requestAnalyzer = new RequestAnalyzer($requestStack->reveal(), [$provider->reveal()]);
        $requestAnalyzer->analyze($request);
        $requestAnalyzer->validate($request);
    }

    public function testAnalyzeAndValidateWithError(): void
    {
        $this->expectException(UrlMatchNotFoundException::class);
        $provider = $this->prophesize(RequestProcessorInterface::class);
        $request = new Request();

        $provider->process($request, Argument::type(RequestAttributes::class))
            ->shouldBeCalled()->willReturn(new RequestAttributes());
        $provider->validate(Argument::type(RequestAttributes::class))
            ->shouldBeCalled()
            ->willThrow(new UrlMatchNotFoundException(''));

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request);
        $requestAnalyzer = new RequestAnalyzer($requestStack->reveal(), [$provider->reveal()]);
        $requestAnalyzer->analyze($request);
        $requestAnalyzer->validate($request);
    }

    public function testAnalyzeWithoutValidateWithError(): void
    {
        $provider = $this->prophesize(RequestProcessorInterface::class);
        $request = new Request();

        $provider->process($request, Argument::type(RequestAttributes::class))
            ->shouldBeCalled()->willReturn(new RequestAttributes());
        $provider->validate(Argument::type(RequestAttributes::class))
            ->shouldNotBeCalled()
            ->willThrow(new UrlMatchNotFoundException(''));

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request);
        $requestAnalyzer = new RequestAnalyzer($requestStack->reveal(), [$provider->reveal()]);
        $requestAnalyzer->analyze($request);
    }

    public function testGetAttribute(): void
    {
        $provider = $this->prophesize(RequestProcessorInterface::class);
        $request = new Request();

        $provider->process($request, Argument::type(RequestAttributes::class))
            ->shouldBeCalledTimes(1)->willReturn(new RequestAttributes(['test' => 1]));
        $provider->validate(Argument::type(RequestAttributes::class))->shouldBeCalled()->willReturn(true);

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request);
        $requestAnalyzer = new RequestAnalyzer($requestStack->reveal(), [$provider->reveal()]);

        $requestAnalyzer->analyze($request);
        $requestAnalyzer->validate($request);

        $this->assertEquals(1, $requestAnalyzer->getAttribute('test'));
        $this->assertEquals(2, $requestAnalyzer->getAttribute('test1', 2));
    }

    public function testGetAttributeTwice(): void
    {
        $provider = $this->prophesize(RequestProcessorInterface::class);
        $request = new Request();

        $provider->process($request, Argument::type(RequestAttributes::class))
            ->shouldBeCalledTimes(1)->willReturn(new RequestAttributes(['test' => 1]));
        $provider->validate(Argument::type(RequestAttributes::class))->shouldBeCalled()->willReturn(true);

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request);
        $requestAnalyzer = new RequestAnalyzer($requestStack->reveal(), [$provider->reveal()]);

        $requestAnalyzer->analyze($request);
        $requestAnalyzer->validate($request);

        $this->assertEquals(1, $requestAnalyzer->getAttribute('test'));
        $this->assertEquals(2, $requestAnalyzer->getAttribute('test1', 2));

        $this->assertEquals(1, $requestAnalyzer->getAttribute('test'));
        $this->assertEquals(2, $requestAnalyzer->getAttribute('test1', 2));
    }

    public function testGetAttributeAfterChangingSegment(): void
    {
        $provider = $this->prophesize(RequestProcessorInterface::class);

        $webspace = new Webspace();

        $winterSegment = new Segment();
        $winterSegment->setKey('w');
        $summerSegment = new Segment();
        $summerSegment->setKey('s');

        $webspace->addSegment($winterSegment);
        $webspace->addSegment($summerSegment);

        $request = new Request();

        $provider->process($request, Argument::type(RequestAttributes::class))
             ->shouldBeCalled()
             ->willReturn(new RequestAttributes(['segment' => $winterSegment, 'webspace' => $webspace]));
        $provider->validate(Argument::type(RequestAttributes::class))->shouldBeCalled()->willReturn(true);

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request);
        $requestAnalyzer = new RequestAnalyzer($requestStack->reveal(), [$provider->reveal()]);
        $requestAnalyzer->analyze($request);
        $requestAnalyzer->validate($request);

        $this->assertSame($winterSegment, $requestAnalyzer->getSegment());

        $requestAnalyzer->changeSegment('s');

        $this->assertSame($summerSegment, $requestAnalyzer->getSegment());
    }

    public function testAnalyzeMultipleProvider(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('www.sulu.io');
        $request->getScheme()->willReturn('https');
        $request->getRequestUri()->willReturn('/');

        $attributesBag = $this->prophesize(ParameterBag::class);
        $attributesBag->get('_sulu')->willReturn(null);
        $attributesBag->has('_sulu')->willReturn(false);
        $attributesBag->set('_sulu', Argument::type(RequestAttributes::class))->shouldBeCalledTimes(1)->will(
            function($arguments) use ($attributesBag) {
                $attributesBag->get('_sulu')->willReturn($arguments[1]);
                $attributesBag->has('_sulu')->willReturn(true);
            }
        );
        $request->reveal()->attributes = $attributesBag->reveal();

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request->reveal());
        $requestAnalyzer = new RequestAnalyzer($requestStack->reveal(), []);

        $requestAnalyzer->analyze($request->reveal());

        $this->assertEquals('https', $requestAnalyzer->getAttribute('scheme'));
    }

    public static function provideGetter()
    {
        $localization = new Localization('de', 'at');

        return [
            [['matchType' => 1], 'getMatchType', 1],
            [[], 'getMatchType', null],
            [['webspace' => 1], 'getWebspace', 1],
            [[], 'getWebspace', null],
            [['portal' => 1], 'getPortal', 1],
            [[], 'getPortal', null],
            [['segment' => 1], 'getSegment', 1],
            [[], 'getSegment', null],
            [['localization' => $localization], 'getCurrentLocalization', $localization],
            [[], 'getCurrentLocalization', null],
            [['portalUrl' => 1], 'getPortalUrl', 1],
            [[], 'getPortalUrl', null],
            [['redirect' => 1], 'getRedirect', 1],
            [[], 'getRedirect', null],
            [['resourceLocator' => 1], 'getResourceLocator', 1],
            [[], 'getResourceLocator', false],
            [['resourceLocatorPrefix' => 1], 'getResourceLocatorPrefix', 1],
            [[], 'getResourceLocatorPrefix', ''],
            [['portalInformation' => 1], 'getPortalInformation', 1],
            [[], 'getPortalInformation', null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideGetter')]
    public function testGetter(array $attributes, $method, $expected): void
    {
        $provider = $this->prophesize(RequestProcessorInterface::class);

        $request = new Request();

        $provider->process($request, Argument::type(RequestAttributes::class))
            ->shouldBeCalled()->willReturn(new RequestAttributes($attributes));
        $provider->validate(Argument::type(RequestAttributes::class))->shouldBeCalled()->willReturn(true);

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request);
        $requestAnalyzer = new RequestAnalyzer($requestStack->reveal(), [$provider->reveal()]);
        $requestAnalyzer->analyze($request);
        $requestAnalyzer->validate($request);

        $this->assertSame($expected, $requestAnalyzer->{$method}());
    }

    public function testGetDateTime(): void
    {
        $provider = $this->prophesize(RequestProcessorInterface::class);
        $request = new Request();

        $dateTime = new \DateTime();

        $provider->process($request, Argument::type(RequestAttributes::class))
            ->willReturn(new RequestAttributes(['dateTime' => $dateTime]));

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn($request);
        $requestAnalyzer = new RequestAnalyzer($requestStack->reveal(), [$provider->reveal()]);

        $requestAnalyzer->analyze($request);

        $this->assertEquals($dateTime, $requestAnalyzer->getDateTime());
    }
}
