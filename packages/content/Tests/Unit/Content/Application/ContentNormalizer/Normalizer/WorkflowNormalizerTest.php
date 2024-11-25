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
use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\WorkflowNormalizer;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;

class WorkflowNormalizerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createWorkflowNormalizerInstance(): WorkflowNormalizer
    {
        return new WorkflowNormalizer();
    }

    public function testIgnoredAttributesNotImplementWorkflowInterface(): void
    {
        $normalizer = $this->createWorkflowNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testIgnoredAttributes(): void
    {
        $normalizer = $this->createWorkflowNormalizerInstance();
        $object = $this->prophesize(WorkflowInterface::class);

        $this->assertSame(
            [],
            $normalizer->getIgnoredAttributes($object->reveal())
        );
    }

    public function testEnhanceNotImplementWorkflowInterface(): void
    {
        $normalizer = $this->createWorkflowNormalizerInstance();
        $object = $this->prophesize(\stdClass::class);

        $data = [
            'workflowPublished' => '12345',
            'publishedState' => '123',
            'published' => '456',
        ];

        $this->assertSame(
            $data,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhancePublished(): void
    {
        $normalizer = $this->createWorkflowNormalizerInstance();
        $object = $this->prophesize(WorkflowInterface::class);

        $data = [
            'workflowPlace' => 'published',
            'workflowPublished' => '2019-01-01',
        ];

        $expectedResult = [
            'workflowPlace' => 'published',
            'publishedState' => true,
            'published' => '2019-01-01',
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }

    public function testEnhanceNotPublished(): void
    {
        $normalizer = $this->createWorkflowNormalizerInstance();
        $object = $this->prophesize(WorkflowInterface::class);

        $data = [
            'workflowPlace' => 'unpublished',
            'workflowPublished' => null,
        ];

        $expectedResult = [
            'workflowPlace' => 'unpublished',
            'publishedState' => false,
            'published' => null,
        ];

        $this->assertSame(
            $expectedResult,
            $normalizer->enhance($object->reveal(), $data)
        );
    }
}
