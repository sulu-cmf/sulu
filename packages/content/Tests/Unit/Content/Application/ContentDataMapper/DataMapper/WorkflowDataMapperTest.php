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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\WorkflowDataMapper;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class WorkflowDataMapperTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createWorkflowDataMapperInstance(): WorkflowDataMapper
    {
        return new WorkflowDataMapper();
    }

    public function testMapNoWorkflowInterface(): void
    {
        $data = [
            'published' => (new \DateTime())->format('c'),
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $workflowMapper = $this->createWorkflowDataMapperInstance();
        $workflowMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);

        $this->assertTrue(true); // Avoid risky test as this is an early return test
    }

    public function testMapNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $workflowMapper = $this->createWorkflowDataMapperInstance();
        $workflowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('unpublished', $localizedDimensionContent->getWorkflowPlace());
        $this->assertNull($localizedDimensionContent->getWorkflowPublished());
    }

    public function testMapStageData(): void
    {
        $publishedDate = (new \DateTime())->format('c');
        $data = [
            'published' => $publishedDate,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $workflowMapper = $this->createWorkflowDataMapperInstance();
        $workflowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('unpublished', $localizedDimensionContent->getWorkflowPlace());
        $workflowPublished = $localizedDimensionContent->getWorkflowPublished();
        $this->assertNull($workflowPublished);
    }

    public function testMapLiveData(): void
    {
        $publishedDate = (new \DateTime())->format('c');
        $data = [
            'published' => $publishedDate,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage(DimensionContentInterface::STAGE_LIVE);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setStage(DimensionContentInterface::STAGE_LIVE);

        $workflowMapper = $this->createWorkflowDataMapperInstance();
        $workflowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getWorkflowPlace());
        $workflowPublished = $localizedDimensionContent->getWorkflowPublished();
        $this->assertNotNull($workflowPublished);
        $this->assertSame($publishedDate, $workflowPublished->format('c'));
    }

    public function testMapWorkflowPlaceAlreadySet(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $localizedDimensionContent->setWorkflowPlace('something-else');

        $workflowMapper = $this->createWorkflowDataMapperInstance();
        $workflowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('something-else', $localizedDimensionContent->getWorkflowPlace());
    }

    public function testMapDataPublishedAlreadySet(): void
    {
        $data = [
            'published' => (new \DateTime())->format('c'),
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $localizedDimensionContent->setWorkflowPlace('something-else');
        $localizedDimensionContent->setWorkflowPublished(new \DateTimeImmutable('2021-01-01 00:00:00'));

        $workflowMapper = $this->createWorkflowDataMapperInstance();
        $workflowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('something-else', $localizedDimensionContent->getWorkflowPlace());
        $workflowPublished = $localizedDimensionContent->getWorkflowPublished();
        $this->assertNotNull($workflowPublished);
        $this->assertSame('2021-01-01 00:00:00', $workflowPublished->format('Y-m-d H:i:s'));
    }

    public function testMapLocalizedLivePublishedNotSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected "published" to be set in the data array.');

        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setStage(DimensionContentInterface::STAGE_LIVE);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setStage(DimensionContentInterface::STAGE_LIVE);

        $workflowMapper = $this->createWorkflowDataMapperInstance();
        $workflowMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);
    }
}
