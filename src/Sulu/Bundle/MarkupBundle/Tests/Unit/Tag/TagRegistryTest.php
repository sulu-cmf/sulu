<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tests\Unit\Tag;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MarkupBundle\Tag\TagInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagNotFoundException;
use Sulu\Bundle\MarkupBundle\Tag\TagRegistry;

class TagRegistryTest extends TestCase
{
    public function testGetTag()
    {
        $tag = $this->prophesize(TagInterface::class)->reveal();
        $registry = new TagRegistry(['html' => ['sulu' => ['test' => $tag]]]);

        $this->assertEquals($tag, $registry->getTag('test', 'html'));
    }

    public function testGetTagNotFound()
    {
        $this->expectException(TagNotFoundException::class);

        $registry = new TagRegistry(['test' => $this->prophesize(TagInterface::class)->reveal()]);
        $registry->getTag('test-2', 'html');
    }

    public function testGetTypeNotFound()
    {
        $this->expectException(TagNotFoundException::class);

        $tag = $this->prophesize(TagInterface::class)->reveal();
        $registry = new TagRegistry(['html' => ['sulu' => ['test' => $tag]]]);
        $registry->getTag('test-2', 'xml');
    }

    public function testGetTagNoTag()
    {
        $this->expectException(TagNotFoundException::class);

        $registry = new TagRegistry([]);
        $registry->getTag('test-2', 'html');
    }
}
