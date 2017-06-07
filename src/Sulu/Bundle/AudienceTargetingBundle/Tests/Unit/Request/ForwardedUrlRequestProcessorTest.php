<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer\Attributes;

use Sulu\Bundle\AudienceTargetingBundle\Request\ForwardedUrlRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\Request;

class ForwardedUrlRequestProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideProcess
     */
    public function testProcess($urlHeader, $url, $host, $port, $path)
    {
        $forwardedUrlRequestProcessor = new ForwardedUrlRequestProcessor($urlHeader);
        $request = new Request();
        $request->headers->set($urlHeader, $url);
        $requestAttributes = $forwardedUrlRequestProcessor->process($request, new RequestAttributes());

        $this->assertEquals($host, $requestAttributes->getAttribute('host'));
        $this->assertEquals($port, $requestAttributes->getAttribute('port'));
        $this->assertEquals($path, $requestAttributes->getAttribute('path'));
    }

    public function provideProcess()
    {
        return [
            ['X-Forwarded-Url', 'http://127.0.0.1:8000/en/test', '127.0.0.1', 8000, '/en/test'],
            ['X-Url', 'http://sulu.lo/en/test', 'sulu.lo', 80, '/en/test'],
            ['X-Forwarded-Url', 'http://sulu.lo/de/test', 'sulu.lo', 80, '/de/test'],
        ];
    }
}
