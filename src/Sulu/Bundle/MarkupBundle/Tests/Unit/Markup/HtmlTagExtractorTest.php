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
use Sulu\Bundle\MarkupBundle\Markup\HtmlTagExtractor;

class HtmlTagExtractorTest extends TestCase
{
    public static function provideTags()
    {
        return [
            ['<sulu-tag/>', 'tag', []],
            ['<sulu-tag />', 'tag', []],
            ['<sulu-tag></sulu-tag>', 'tag', []],
            ['<sulu-tag href="1"/>', 'tag', ['href' => '1']],
            ['<sulu-tag>Test</sulu-tag>', 'tag', ['content' => 'Test']],
            ['<sulu-tag>http://www.google.com</sulu-tag>', 'tag', ['content' => 'http://www.google.com']],
            ['<sulu-tag><a href="http://google.com">http://google.com</a></sulu-tag>', 'tag', ['content' => '<a href="http://google.com">http://google.com</a>']],
            ['<sulu-tag href="http://google.com">http://google.com</sulu-tag>', 'tag', ['href' => 'http://google.com', 'content' => 'http://google.com']],
            ['<sulu-tag id="1">http://google.com</sulu-tag>', 'tag', ['id' => '1', 'content' => 'http://google.com']],
            ['<sulu-tag id="a slash (/) in here is allowed">http://google.com</sulu-tag>', 'tag', ['id' => 'a slash (/) in here is allowed', 'content' => 'http://google.com']],
            ['<sulu-tag id="2">everything also <tags/> are allowed</sulu-tag>', 'tag', ['id' => '2', 'content' => 'everything also <tags/> are allowed']],
            ['<sulu-link target="1-1-1-1-1"><sulu:media id="123" /></sulu-link>', 'link', ['target' => '1-1-1-1-1', 'content' => '<sulu:media id="123" />']],
            ["<sulu-link target=\"1-1-1-1-1\">\n<sulu:media id=\"123\" />\n</sulu-link>", 'link', ['target' => '1-1-1-1-1', 'content' => "\n<sulu:media id=\"123\" />\n"]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideTags')]
    public function testExtract($tag, $tagName, array $attributes): void
    {
        $html = '<html><body>' . $tag . '</body></html>';

        $extractor = new HtmlTagExtractor('sulu');
        $result = $extractor->extract($html);

        $this->assertCount(1, $result);
        $this->assertEquals($result[0]->getTagName(), $tagName);
        $this->assertEquals($result[0]->getTags()[$tag], $attributes);
    }

    public static function provideMultipleTags()
    {
        $tags = [
            '<sulu-tag/>',
            '<sulu-tag />',
            '<sulu-tag></sulu-tag>',
            '<sulu-tag href="1"/>',
            '<sulu-tag>Test</sulu-tag>',
            '<sulu-tag>http://www.google.com</sulu-tag>',
            '<sulu-tag><a href="http://google.com">http://google.com</a></sulu-tag>',
            '<sulu-tag href="http://google.com">http://google.com</sulu-tag>',
            '<sulu-tag id="1">http://google.com</sulu-tag>',
            '<sulu-tag id="a slash (/) in here isnt allowed">http://google.com</sulu-tag>',
            '<sulu-tag id="2">everything but <tags/> are allowed</sulu-tag>',
            // media cannot be detected with current regex. will be solved with recursion.
            '<sulu-link target="1-1-1-1-1"><sulu-media id="123" /></sulu-link>',
            "<sulu-link target=\"1-1-1-1-1\">\n<sulu-media id=\"123\" />\n</sulu-link>",
        ];

        return [
            ['<html><body>' . \implode($tags) . '</body></html>', ['tag' => 11, 'link' => 2], 15],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideMultipleTags')]
    public function testExtractAll($html, array $counts): void
    {
        $extractor = new HtmlTagExtractor('sulu');
        $result = $extractor->extract($html);

        $this->assertCount(\count($counts), $result);
        foreach ($result as $tagMatchGroup) {
            $this->assertCount($counts[$tagMatchGroup->getTagName()], $tagMatchGroup->getTags());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideMultipleTags')]
    public function testCount($html, array $counts, $count): void
    {
        $extractor = new HtmlTagExtractor('sulu');

        $result = $extractor->count($html);
        $this->assertEquals($count, $result);
    }
}
