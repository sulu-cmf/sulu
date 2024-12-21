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
use Sulu\Component\Webspace\Analyzer\Attributes\UrlRequestProcessor;
use Symfony\Component\HttpFoundation\Request;

class UrlRequestProcessorTest extends TestCase
{
    /**
     * @var UrlRequestProcessor
     */
    private $urlRequestProcessor;

    public function setUp(): void
    {
        $this->urlRequestProcessor = new UrlRequestProcessor();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideProcess')]
    public function testProcess($url, $host, $port, $path): void
    {
        $request = Request::create($url);
        $requestAttributes = $this->urlRequestProcessor->process($request, new RequestAttributes());

        $this->assertEquals($host, $requestAttributes->getAttribute('host'));
        $this->assertEquals($port, $requestAttributes->getAttribute('port'));
        $this->assertEquals($path, $requestAttributes->getAttribute('path'));
    }

    public static function provideProcess()
    {
        return [
            ['http://127.0.0.1:8000/en/test', '127.0.0.1', 8000, '/en/test'],
            ['http://sulu.lo/en/test', 'sulu.lo', 80, '/en/test'],
            ['http://sulu.lo/de/test', 'sulu.lo', 80, '/de/test'],
        ];
    }
}
