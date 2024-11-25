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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Infrastructure\Sulu\SmartContent\DataItem;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\SmartContent\DataItem\ContentDataItem;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;

class ContentDataItemTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     * @param mixed[] $data
     *
     * @return ContentDataItem<T>
     */
    protected function getContentDataItem(
        DimensionContentInterface $dimensionContent,
        array $data
    ): ContentDataItem {
        return new ContentDataItem($dimensionContent, $data);
    }

    public function testGetId(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);

        $dataItem = $this->getContentDataItem($dimensionContent, []);

        $this->assertSame('123-123', $dataItem->getId());
    }

    public function testGetTitle(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);

        $data = [
            'title' => 'test-title-1',
            'name' => 'test-name-1',
        ];

        $dataItem = $this->getContentDataItem($dimensionContent, $data);

        $this->assertSame('test-title-1', $dataItem->getTitle());
    }

    public function testGetTitleNull(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);

        $data = [];

        $dataItem = $this->getContentDataItem($dimensionContent, $data);

        $this->assertNull($dataItem->getTitle());
    }

    public function testGetNameAsTitle(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);

        $data = [
            'title' => null,
            'name' => 'test-name-1',
        ];

        $dataItem = $this->getContentDataItem($dimensionContent, $data);

        $this->assertSame('test-name-1', $dataItem->getTitle());
    }

    public function testGetImage(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);

        $dataItem = $this->getContentDataItem($dimensionContent, []);

        $this->assertNull($dataItem->getImage());
    }

    public function testGetPublished(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');

        $published = new \DateTimeImmutable();

        $dimensionContent = new ExampleDimensionContent($resource);
        $dimensionContent->setLocale('en');
        $dimensionContent->setWorkflowPublished($published);

        $dataItem = $this->getContentDataItem($dimensionContent, []);

        $this->assertSame($published, $dataItem->getPublished());
    }

    public function testGetPublishedLocaleNull(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);

        $dataItem = $this->getContentDataItem($dimensionContent, []);

        $this->assertNull($dataItem->getPublished());
    }

    public function testGetPublishedState(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);
        $dimensionContent->setLocale('en');
        $dimensionContent->setStage('draft');
        $dimensionContent->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_PUBLISHED);

        $dataItem = $this->getContentDataItem($dimensionContent, []);

        $this->assertTrue($dataItem->getPublishedState());
    }

    public function testGetPublishedStateLocaleNull(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);
        $dimensionContent->setLocale(null);

        $dataItem = $this->getContentDataItem($dimensionContent, []);

        $this->assertFalse($dataItem->getPublishedState());
    }

    public function testGetPublishedStateStageLive(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);
        $dimensionContent->setLocale('en');
        $dimensionContent->setStage('live');

        $dataItem = $this->getContentDataItem($dimensionContent, []);

        $this->assertTrue($dataItem->getPublishedState());
    }

    public function testGetPublishedStateUnpublished(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);
        $dimensionContent->setLocale('en');
        $dimensionContent->setStage('draft');
        $dimensionContent->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_UNPUBLISHED);

        $dataItem = $this->getContentDataItem($dimensionContent, []);

        $this->assertFalse($dataItem->getPublishedState());
    }

    public function testGetPublishedStateDraft(): void
    {
        $resource = new Example();
        static::setPrivateProperty($resource, 'id', '123-123');
        $dimensionContent = new ExampleDimensionContent($resource);
        $dimensionContent->setLocale('en');
        $dimensionContent->setStage('draft');
        $dimensionContent->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_DRAFT);

        $dataItem = $this->getContentDataItem($dimensionContent, []);

        $this->assertFalse($dataItem->getPublishedState());
    }

    public function testGetPublishedNoWorkflow(): void
    {
        $resource = $this->prophesize(ContentRichEntityInterface::class);
        $resource->getId()->willReturn('123-123');

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->getResource()->willReturn($resource->reveal());
        $dimensionContent->getLocale()->willReturn('en');

        $this->assertNotInstanceOf(WorkflowInterface::class, $dimensionContent->reveal());
        $dataItem = $this->getContentDataItem($dimensionContent->reveal(), []);

        $this->assertNull($dataItem->getPublished());
    }

    public function testGetPublishedStateNoWorkflow(): void
    {
        $resource = $this->prophesize(ContentRichEntityInterface::class);
        $resource->getId()->willReturn('123-123');

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->getResource()->willReturn($resource->reveal());
        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getStage()->willReturn('draft');

        $this->assertNotInstanceOf(WorkflowInterface::class, $dimensionContent->reveal());
        $dataItem = $this->getContentDataItem($dimensionContent->reveal(), []);

        $this->assertTrue($dataItem->getPublishedState());
    }
}
