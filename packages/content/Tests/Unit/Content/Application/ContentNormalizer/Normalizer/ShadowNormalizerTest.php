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
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\ShadowNormalizer;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;

class ShadowNormalizerTest extends TestCase
{
    use ProphecyTrait;

    protected function createShadowNormalizerInstance(): ShadowNormalizer
    {
        return new ShadowNormalizer();
    }

    public function testIgnoredAttributesNotImplementShadowInterface(): void
    {
        $normalizer = $this->createShadowNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testIgnoredAttributes(): void
    {
        $normalizer = $this->createShadowNormalizerInstance();
        $object = $this->prophesize(ShadowInterface::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testEnhanceNotImplementShadowInterface(): void
    {
        $normalizer = $this->createShadowNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $data = [];

        $this->assertSame(
            $data,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhance(): void
    {
        $normalizer = $this->createShadowNormalizerInstance();
        $object = $this->prophesize(DimensionContentInterface::class);
        $object->willImplement(ShadowInterface::class);
        $object->getAvailableLocales()->willReturn(['en', 'de']);
        $object->getShadowLocale()->willReturn('en');

        $data = [
            'availableLocales' => ['en', 'de'],
            'shadowLocale' => 'en',
            'shadowLocales' => ['de' => 'en'],
        ];

        $expectedResult = [
            'availableLocales' => ['en', 'de'],
            'shadowLocale' => 'en',
            'shadowLocales' => ['de' => 'en'],
            'shadowOn' => true,
            'contentLocales' => ['en', 'de'],
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }
}
