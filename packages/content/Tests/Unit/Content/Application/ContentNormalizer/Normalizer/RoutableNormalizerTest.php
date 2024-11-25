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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentNormalizer\Normalizer;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\RoutableNormalizer;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableInterface;

class RoutableNormalizerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createRoutableNormalizerInstance(): RoutableNormalizer
    {
        return new RoutableNormalizer();
    }

    public function testIgnoredAttributesNotImplementRoutableInterface(): void
    {
        $normalizer = $this->createRoutableNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testIgnoredAttributes(): void
    {
        $normalizer = $this->createRoutableNormalizerInstance();
        $object = $this->prophesize(RoutableInterface::class);

        $this->assertSame(
            [
                'resourceId',
            ],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testEnhanceNotImplementRoutableInterface(): void
    {
        $normalizer = $this->createRoutableNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $data = [
            'property1' => 'value-1',
            'property2' => 'value-2',
        ];

        $this->assertSame(
            $data,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhance(): void
    {
        $normalizer = $this->createRoutableNormalizerInstance();
        $object = $this->prophesize(RoutableInterface::class);

        $data = [
            'property1' => 'value-1',
            'property2' => 'value-2',
        ];

        $this->assertSame(
            $data,
            $normalizer->enhance($object->reveal(), $data)
        );
    }
}
