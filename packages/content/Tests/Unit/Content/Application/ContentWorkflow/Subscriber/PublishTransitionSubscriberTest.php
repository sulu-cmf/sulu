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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentWorkflow\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ContentBundle\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\Subscriber\PublishTransitionSubscriber;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Marking;

class PublishTransitionSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function createContentPublisherSubscriberInstance(
        ContentCopierInterface $contentCopier
    ): PublishTransitionSubscriber {
        return new PublishTransitionSubscriber($contentCopier);
    }

    public function testGetSubscribedEvents(): void
    {
        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $this->assertSame([
            'workflow.content_workflow.transition.publish' => 'onPublish',
        ], $contentPublishSubscriber::getSubscribedEvents());
    }

    public function testOnPublishNoDimensionContentInterface(): void
    {
        $dimensionContent = $this->prophesize(WorkflowInterface::class);
        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentCopier->copyFromDimensionContentCollection(Argument::cetera())->shouldNotBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishNoDimensionContentCollection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No "dimensionContentCollection" given.');

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentCopier->copyFromDimensionContentCollection(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishNoContentRichEntity(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No "contentRichEntity" given.');

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentCopier->copyFromDimensionContentCollection(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishNoDimensionAttributes(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No "dimensionAttributes" given.');

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentCopier->copyFromDimensionContentCollection(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublish(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getWorkflowPublished()->willReturn(null);
        $dimensionContent->setWorkflowPublished(Argument::cetera())->shouldBeCalled();

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $sourceDimensionAttributes = $dimensionAttributes;
        $sourceDimensionAttributes['stage'] = 'live';

        $resolvedCopiedContent = $this->prophesize(DimensionContentInterface::class);
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            $sourceDimensionAttributes
        )
            ->willReturn($resolvedCopiedContent->reveal())
            ->shouldBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishExistingPublished(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getWorkflowPublished()->willReturn(new \DateTimeImmutable());
        $dimensionContent->setWorkflowPublished(Argument::any())->shouldNotBeCalled();

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $targetDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes['stage'] = 'live';

        $resolvedCopiedContent = $this->prophesize(DimensionContentInterface::class);
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            $targetDimensionAttributes
        )
            ->willReturn($resolvedCopiedContent->reveal())
            ->shouldBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishShadow(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContent->willImplement(ShadowInterface::class);
        $dimensionContent->willImplement(TemplateInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getShadowLocale()->willReturn('de');
        $dimensionContent->getTemplateData()->willReturn(['url' => '/test-de']);
        $dimensionContent->getWorkflowPublished()->willReturn(null);
        $dimensionContent->setWorkflowPublished(Argument::cetera())->shouldBeCalled();

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $sourceDimensionAttributes = $dimensionAttributes;
        $sourceDimensionAttributes['locale'] = 'de';
        $sourceDimensionAttributes['stage'] = 'live';
        $targetDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes['stage'] = 'live';

        $resolvedCopiedContent = $this->prophesize(DimensionContentInterface::class);
        $contentCopier->copy(
            $contentRichEntity->reveal(),
            $sourceDimensionAttributes,
            $contentRichEntity->reveal(),
            $targetDimensionAttributes,
            [
                'data' => [
                    'shadowOn' => true,
                    'shadowLocale' => 'de',
                    'url' => '/test-de',
                ],
            ]
        )
            ->willReturn($resolvedCopiedContent->reveal())
            ->shouldBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishHasShadow(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContent->willImplement(ShadowInterface::class);
        $dimensionContent->willImplement(TemplateInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getShadowLocale()->willReturn(null);
        $dimensionContent->getWorkflowPublished()->willReturn(null);
        $dimensionContent->setWorkflowPublished(Argument::cetera())->shouldBeCalled();

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $targetDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes['stage'] = 'live';

        $resolvedCopiedContent = $this->prophesize(DimensionContentInterface::class);
        $resolvedCopiedContent->willImplement(ShadowInterface::class);
        $resolvedCopiedContent->getShadowLocalesForLocale('en')->willReturn(['de'])->shouldBeCalled();
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            $targetDimensionAttributes
        )
            ->willReturn($resolvedCopiedContent->reveal())
            ->shouldBeCalled();

        $targetDimensionAttributes['locale'] = 'de';
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            $targetDimensionAttributes,
            [
                'ignoredAttributes' => [
                    'shadowOn',
                    'shadowLocale',
                    'url',
                ],
            ]
        )
            ->willReturn($resolvedCopiedContent->reveal())
            ->shouldBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }
}
