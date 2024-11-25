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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\PropertyResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Bundle\ContentBundle\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;

class DefaultPropertyResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $defaultPropertyResolver = new DefaultPropertyResolver();
        $result = $defaultPropertyResolver->resolve('data', 'locale', ['params' => 'value']);

        $this->assertInstanceOf(ContentView::class, $result);
        $this->assertSame('data', $result->getContent());
        $this->assertSame([], $result->getView());
    }

    public function testGetType(): void
    {
        $this->assertSame('default', DefaultPropertyResolver::getType());
    }
}
