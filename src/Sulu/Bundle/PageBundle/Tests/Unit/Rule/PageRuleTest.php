<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Rule;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Rule\PageRule;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageRuleTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    /**
     * @var ObjectProphecy<ResourceLocatorStrategyPoolInterface>
     */
    private $resourceLocatorStrategyPool;

    public function setUp(): void
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->resourceLocatorStrategyPool = $this->prophesize(ResourceLocatorStrategyPoolInterface::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideEvaluate')]
    public function testEvaluate(
        $uuidHeader,
        $uuidValue,
        $urlHeader,
        $urlValue,
        $urlUuidValue,
        $webspaceKey,
        $locale,
        $uuidRule,
        $urlExists,
        $result
    ): void {
        $pageRule = new PageRule(
            $this->requestStack->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->translator->reveal(),
            $this->resourceLocatorStrategyPool->reveal(),
            $uuidHeader,
            $urlHeader
        );

        $requestAttributes = [];
        if ($urlUuidValue) {
            $webspace = null;
            if ($webspaceKey) {
                $webspace = new Webspace();
                $webspace->setKey($webspaceKey);
            }
            $this->requestAnalyzer->getWebspace()->willReturn($webspace);
            $this->requestAnalyzer->getResourceLocator()->willReturn($urlValue);

            $localization = null;
            if ($locale) {
                $localization = new Localization($locale);
            }
            $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

            $resourceLocatorStrategy = $this->prophesize(ResourceLocatorStrategyInterface::class);

            if ('/' === \substr($urlValue, -1)) {
                $resourceLocatorStrategy->loadByResourceLocator(Argument::cetera())->shouldNotBeCalled();
            } elseif (true === $urlExists) {
                $resourceLocatorStrategy->loadByResourceLocator(
                    $urlValue,
                    $webspaceKey,
                    $locale
                )->willReturn($urlUuidValue);
            } else {
                $resourceLocatorStrategy->loadByResourceLocator(
                    $urlValue,
                    $webspaceKey,
                    $locale
                )->willThrow(ResourceLocatorNotFoundException::class);
            }

            $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey)
                ->willReturn($resourceLocatorStrategy->reveal());
        }

        $request = new Request([], [], $requestAttributes);

        if ($uuidValue) {
            $request->headers->set($uuidHeader, $uuidValue);
        }

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertEquals($result, $pageRule->evaluate(['page' => $uuidRule]));
    }

    public static function provideEvaluate()
    {
        return [
            ['X-Forwarded-UUID', 'some-uuid', 'X-Forwarded-URL', null, null, null, null, 'some-uuid', true, true],
            ['X-UUID', 'some-uuid', 'X-URL', null, null, null, null, 'some-uuid', true, true],
            ['X-Forwarded-UUID', 'some-uuid', 'X-Forwarded-URL', null, null, null, null, 'some-other-uuid', true, false],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', null, null, null, null, 'some-other-uuid', true, false],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', '/test', 'some-uuid', 'sulu_io', 'en', 'some-uuid', true, true],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', '/other-test', 'uuid', 'sulu', 'de', 'uuid', true, true],
            ['X-Forwarded-UUID', null, 'X-URL', '/test', 'some-uuid', 'sulu_io', 'en', 'some-uuid', true, true],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', '/test', 'some-uuid', 'sulu_io', 'en', 'some-other-uuid', true, false],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', '/test', 'some-uuid', 'sulu_io', null, 'some-uuid', true, false],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', '/test', 'some-uuid', null, 'en', 'some-uuid', true, false],
            ['X-UUID', 'some-uuid', 'X-URL', '/test', 'some-other-uuid', 'sulu_io', 'en', 'some-uuid', true, true],
            ['X-UUID', 'some-uuid', 'X-URL', '/test', 'some-other-uuid', 'sulu_io', 'en', 'some-other-uuid', true, false],
            ['X-UUID', 'some-uuid', 'X-URL', '/test', 'some-other-uuid', 'sulu_io', 'en', 'not-existing-uuid', false, false],
            ['X-UUID', null, 'X-URL', '/test/', 'some-other-uuid', 'sulu_io', 'en', 'not-existing-uuid', false, false],
        ];
    }
}
