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
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\DimensionContentNormalizer;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class DimensionContentNormalizerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createDimensionContentNormalizerInstance(): DimensionContentNormalizer
    {
        return new DimensionContentNormalizer();
    }

    public function testIgnoredAttributesNotImplementDimensionContentInterface(): void
    {
        $normalizer = $this->createDimensionContentNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testIgnoredAttributes(): void
    {
        $normalizer = $this->createDimensionContentNormalizerInstance();
        $object = $this->prophesize(DimensionContentInterface::class);

        $this->assertSame(
            ['id', 'merged', 'dimension', 'resource'],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testEnhanceNotImplementDimensionContentInterface(): void
    {
        $normalizer = $this->createDimensionContentNormalizerInstance();
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
        $normalizer = $this->createDimensionContentNormalizerInstance();

        $resource = $this->prophesize(ContentRichEntityInterface::class);
        $resource->getId()->willReturn('content-id-123');

        $object = $this->prophesize(DimensionContentInterface::class);
        $object->getResource()->willReturn($resource->reveal());
        $object->getLocale()->willReturn('en');
        $object->getStage()->willReturn('live');

        $data = [
            'property1' => 'value-1',
            'property2' => 'value-2',
        ];

        $expectedResult = [
            'property1' => 'value-1',
            'property2' => 'value-2',
            'id' => 'content-id-123',
            'locale' => 'en',
            'stage' => 'live',
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }
}
