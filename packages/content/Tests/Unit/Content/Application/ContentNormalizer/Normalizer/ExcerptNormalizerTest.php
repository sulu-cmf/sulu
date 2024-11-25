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
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\ExcerptNormalizer;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;

class ExcerptNormalizerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createExcerptNormalizerInstance(): ExcerptNormalizer
    {
        return new ExcerptNormalizer();
    }

    public function testIgnoredAttributesNotImplementExcerptInterface(): void
    {
        $normalizer = $this->createExcerptNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testIgnoredAttributes(): void
    {
        $normalizer = $this->createExcerptNormalizerInstance();
        $object = $this->prophesize(ExcerptInterface::class);

        $this->assertSame(
            [
                'excerptTags',
                'excerptCategories',
            ],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testEnhanceNotImplementExcerptInterface(): void
    {
        $normalizer = $this->createExcerptNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $data = [
            'excerptTagNames' => '12345',
            'excerptCategoryIds' => '123',
        ];

        $this->assertSame(
            $data,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhance(): void
    {
        $normalizer = $this->createExcerptNormalizerInstance();
        $object = $this->prophesize(ExcerptInterface::class);

        $data = [
            'excerptTagNames' => ['Tag 1', 'Tag 2'],
            'excerptCategoryIds' => [3, 4],
        ];

        $expectedResult = [
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [3, 4],
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }
}
