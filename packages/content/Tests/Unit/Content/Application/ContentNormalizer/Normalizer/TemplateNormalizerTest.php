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
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\TemplateNormalizer;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;

class TemplateNormalizerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createTemplateNormalizerInstance(): TemplateNormalizer
    {
        return new TemplateNormalizer();
    }

    public function testIgnoredAttributesNotImplementTemplateInterface(): void
    {
        $normalizer = $this->createTemplateNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testIgnoredAttributes(): void
    {
        $normalizer = $this->createTemplateNormalizerInstance();
        $object = $this->prophesize(TemplateInterface::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testEnhanceNotImplementTemplateInterface(): void
    {
        $normalizer = $this->createTemplateNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $data = [
            'templateData' => [
                'some' => 'data',
            ],
            'templateKey' => 'some-key',
        ];

        $this->assertSame(
            $data,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhance(): void
    {
        $normalizer = $this->createTemplateNormalizerInstance();
        $object = $this->prophesize(TemplateInterface::class);

        $data = [
            'templateData' => [
                'existingKey' => 'template-data-value',
                'newKey' => 'template-data-value',
            ],
            'templateKey' => 'some-key',
            'existingKey' => 'existing-value',
        ];

        $expectedResult = [
            'existingKey' => 'existing-value',
            'newKey' => 'template-data-value',
            'template' => 'some-key',
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }
}
