<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer\Attributes;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\SegmentRequestProcessor;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class SegmentRequestProcessorTest extends TestCase
{
    /**
     * @var SegmentRequestProcessor
     */
    private $segmentRequestProcessor;

    public function setUp(): void
    {
        $this->segmentRequestProcessor = new SegmentRequestProcessor('_ss');
    }

    public function testProcessWithoutSegmentValue(): void
    {
        $request = new Request();

        $webspace = new Webspace();
        $portalInformation = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace
        );

        $attributes = $this->segmentRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation])
        );

        $this->assertNull($attributes->getAttribute('segment'));
    }

    public function testProcessWithoutWebspace(): void
    {
        $request = new Request();

        $webspace = null;
        $portalInformation = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace
        );

        $attributes = $this->segmentRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation])
        );

        $this->assertNull($attributes->getAttribute('segment'));
    }

    public function testProcessWithoutPortalInformation(): void
    {
        $request = new Request();

        $attributes = $this->segmentRequestProcessor->process(
            $request,
            new RequestAttributes()
        );

        $this->assertNull($attributes->getAttribute('segment'));
    }

    public static function provideProcessWithDefaultSegmentValue()
    {
        return [
            [null, 's', 's'],
            [null, 'w', 'w'],
            ['w', 'w', 'w'],
            ['s', 'w', 's'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideProcessWithDefaultSegmentValue')]
    public function testProcessWithDefaultSegmentValue($cookieSegmentKey, $defaultSegmentKey, $expectedSegmentKey): void
    {
        $request = new Request();
        $webspace = new Webspace();

        if ($cookieSegmentKey) {
            $request->cookies->set('_ss', $cookieSegmentKey);
            $cookieSegment = new Segment();
            $cookieSegment->setKey($cookieSegmentKey);
            $webspace->addSegment($cookieSegment);
        }

        $segment = new Segment();
        $segment->setKey($defaultSegmentKey);
        $webspace->setDefaultSegment($segment);
        $portalInformation = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace
        );

        $attributes = $this->segmentRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation])
        );

        $this->assertEquals($expectedSegmentKey, $attributes->getAttribute('segment')->getKey());
    }
}
