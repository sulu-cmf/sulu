<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tests\Unit\Markup;

use Sulu\Bundle\MarkupBundle\Markup\HtmlTagExtractor;

/**
 * Tests for HtmlTagExtractor.
 */
class HtmlTagExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function provideTags()
    {
        return [
            ['<sulu:tag/>', 'tag', []],
            ['<sulu:tag />', 'tag', []],
            ['<sulu:tag></sulu:tag>', 'tag', []],
            ['<sulu:tag href="1"/>', 'tag', ['href' => '1']],
            ['<sulu:tag>Test</sulu:tag>', 'tag', ['content' => 'Test']],
            ['<sulu:tag>http://www.google.com</sulu:tag>', 'tag', ['content' => 'http://www.google.com']],
            ['<sulu:tag><a href="http://google.com">http://google.com</a></sulu:tag>', 'tag', ['content' => '<a href="http://google.com">http://google.com</a>']],
            ['<sulu:tag href="http://google.com">http://google.com</sulu:tag>', 'tag', ['href' => 'http://google.com', 'content' => 'http://google.com']],
            ['<sulu:tag id="1">http://google.com</sulu:tag>', 'tag', ['id' => '1', 'content' => 'http://google.com']],
            ['<sulu:tag id="a slash (/) in here is allowed">http://google.com</sulu:tag>', 'tag', ['id' => 'a slash (/) in here is allowed', 'content' => 'http://google.com']],
            ['<sulu:tag id="2">everything also <tags/> are allowed</sulu:tag>', 'tag', ['id' => '2', 'content' => 'everything also <tags/> are allowed']],
            ['<sulu:link target="1-1-1-1-1"><sulu:media id="123" /></sulu:link>', 'link', ['target' => '1-1-1-1-1', 'content' => '<sulu:media id="123" />']],
        ];
    }

    /**
     * @dataProvider provideTags
     */
    public function testExtract($tag, $tagName, array $attributes)
    {
        $html = '<html><body>' . $tag . '</body></html>';

        $extractor = new HtmlTagExtractor();
        $result = $extractor->extract($html, 'sulu');

        $this->assertCount(1, $result);
        $this->assertEquals($result[$tagName][$tag], $attributes);
    }

    public function provideMultipleTags()
    {
        $tags = [
            '<sulu:tag/>',
            '<sulu:tag />',
            '<sulu:tag></sulu:tag>',
            '<sulu:tag href="1"/>',
            '<sulu:tag>Test</sulu:tag>',
            '<sulu:tag>http://www.google.com</sulu:tag>',
            '<sulu:tag><a href="http://google.com">http://google.com</a></sulu:tag>',
            '<sulu:tag href="http://google.com">http://google.com</sulu:tag>',
            '<sulu:tag id="1">http://google.com</sulu:tag>',
            '<sulu:tag id="a slash (/) in here isnt allowed">http://google.com</sulu:tag>',
            '<sulu:tag id="2">everything but <tags/> are allowed</sulu:tag>',
            // media cannot be detected with current regex. will be solved with recursion.
            '<sulu:link target="1-1-1-1-1"><sulu:media id="123" /></sulu:link>',
        ];

        return [
            ['<html><body>' . implode($tags) . '</body></html>', ['tag' => 11, 'link' => 1], 13],
        ];
    }

    /**
     * @dataProvider provideMultipleTags
     */
    public function testExtractAll($html, array $counts)
    {
        $extractor = new HtmlTagExtractor();
        $result = $extractor->extract($html, 'sulu');

        $this->assertCount(count($counts), $result);

        foreach ($counts as $key => $value) {
            $this->assertCount($value, $result[$key]);
        }
    }

    /**
     * @dataProvider provideMultipleTags
     */
    public function testCount($html, array $counts, $count)
    {
        $extractor = new HtmlTagExtractor();

        $result = $extractor->count($html, 'sulu');
        $this->assertEquals($count, $result);
    }
}
