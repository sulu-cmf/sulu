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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentResolver\Value;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ResolvableResource;

class ContentViewTest extends TestCase
{
    public function testCreate(): void
    {
        $contentView = ContentView::create('content', ['view' => 'data']);

        $this->assertSame('content', $contentView->getContent());
        $this->assertSame(['view' => 'data'], $contentView->getView());
    }

    public function testCreateResolvable(): void
    {
        $contentView = ContentView::createResolvable(5, 'resourceLoaderKey', ['view' => 'data']);

        $content = $contentView->getContent();
        /** @var ResolvableResource $resolvable */
        $resolvable = $content;
        $this->assertSame(5, $resolvable->getId());
        $this->assertSame('resourceLoaderKey', $resolvable->getResourceLoaderKey());
        $this->assertSame(['view' => 'data'], $contentView->getView());
    }

    public function testCreateResolvables(): void
    {
        $contentView = ContentView::createResolvables([5, 6], 'resourceLoaderKey', ['view' => 'data']);

        /** @var ResolvableResource[] $resolvables */
        $resolvables = $contentView->getContent();
        $this->assertCount(2, $resolvables);
        $this->assertSame(5, $resolvables[0]->getId());
        $this->assertSame('resourceLoaderKey', $resolvables[0]->getResourceLoaderKey());
        $this->assertSame(6, $resolvables[1]->getId());
        $this->assertSame('resourceLoaderKey', $resolvables[1]->getResourceLoaderKey());
        $this->assertSame(['view' => 'data'], $contentView->getView());
    }

    public function testGetContent(): void
    {
        $contentView = ContentView::create('content', ['view' => 'data']);

        $this->assertSame('content', $contentView->getContent());
    }

    public function testGetView(): void
    {
        $contentView = ContentView::create('content', ['view' => 'data']);

        $this->assertSame(['view' => 'data'], $contentView->getView());
    }
}
