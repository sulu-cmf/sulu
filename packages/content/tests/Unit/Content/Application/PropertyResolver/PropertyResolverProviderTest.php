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

namespace Sulu\Content\Tests\Unit\Content\Application\PropertyResolver;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProvider;
use Sulu\Content\Application\PropertyResolver\Resolver\BlockPropertyResolver;
use Sulu\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;
use Symfony\Component\ErrorHandler\BufferingLogger;

class PropertyResolverProviderTest extends TestCase
{
    public function testGetPropertyResolver(): void
    {
        $propertyResolverProvider = new PropertyResolverProvider(
            new \ArrayIterator([
                'block' => new BlockPropertyResolver(
                    new BufferingLogger(),
                    false
                ),
                'default' => new DefaultPropertyResolver(),
            ])
        );

        $this->assertInstanceOf(DefaultPropertyResolver::class, $propertyResolverProvider->getPropertyResolver('default'));
        $this->assertInstanceOf(BlockPropertyResolver::class, $propertyResolverProvider->getPropertyResolver('block'));
    }

    public function testGetPropertyResolverWithInvalidType(): void
    {
        $propertyResolverProvider = new PropertyResolverProvider(new \ArrayIterator(['default' => new DefaultPropertyResolver()]));

        $propertyResolver = $propertyResolverProvider->getPropertyResolver('invalid');

        $this->assertInstanceOf(DefaultPropertyResolver::class, $propertyResolver);
    }
}
