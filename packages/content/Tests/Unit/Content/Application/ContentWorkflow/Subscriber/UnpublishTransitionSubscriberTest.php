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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\Subscriber\UnpublishTransitionSubscriber;
use Sulu\Bundle\ContentBundle\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Repository\DimensionContentRepositoryInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Marking;

class UnpublishTransitionSubscriberTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    public function createContentUnpublishSubscriberInstance(
        DimensionContentRepositoryInterface $dimensionContentRepository,
        EntityManagerInterface $entityManager
    ): UnpublishTransitionSubscriber {
        return new UnpublishTransitionSubscriber($dimensionContentRepository, $entityManager);
    }

    public function testGetSubscribedEvents(): void
    {
        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $contentUnpublishSubscriber = $this->createContentUnpublishSubscriberInstance(
            $dimensionContentRepository->reveal(),
            $entityManager->reveal()
        );

        $this->assertSame([
            'workflow.content_workflow.transition.unpublish' => 'onUnpublish',
        ], $contentUnpublishSubscriber::getSubscribedEvents());
    }

    public function testOnUnpublishNoDimensionContentInterface(): void
    {
        $dimensionContent = $this->prophesize(WorkflowInterface::class);
        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $contentUnpublishSubscriber = $this->createContentUnpublishSubscriberInstance(
            $dimensionContentRepository->reveal(),
            $entityManager->reveal()
        );

        $entityManager->remove(Argument::cetera())->shouldNotBeCalled();

        $contentUnpublishSubscriber->onUnpublish($event);
    }

    public function testOnUnpublishNoDimensionAttributes(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transition context must contain "dimensionAttributes".');

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $contentUnpublishSubscriber = $this->createContentUnpublishSubscriberInstance(
            $dimensionContentRepository->reveal(),
            $entityManager->reveal()
        );

        $entityManager->remove(Argument::cetera())->shouldNotBeCalled();

        $contentUnpublishSubscriber->onUnpublish($event);
    }

    public function testOnUnpublishNoContentRichEntity(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transition context must contain "contentRichEntity".');

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
        ]);

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $contentUnpublishSubscriber = $this->createContentUnpublishSubscriberInstance(
            $dimensionContentRepository->reveal(),
            $entityManager->reveal()
        );

        $entityManager->remove(Argument::cetera())->shouldNotBeCalled();

        $contentUnpublishSubscriber->onUnpublish($event);
    }

    public function testOnUnpublishNoLocalizedDimensionContent(): void
    {
        $this->expectException(ContentNotFoundException::class);

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

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $contentUnpublishSubscriber = $this->createContentUnpublishSubscriberInstance(
            $dimensionContentRepository->reveal(),
            $entityManager->reveal()
        );

        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $dimensionContentCollection->getDimensionContent(['locale' => 'en', 'stage' => 'live'])
            ->willReturn(null)
            ->shouldBeCalled();

        $liveDimensionAttributes = \array_merge($dimensionAttributes, ['stage' => DimensionContentInterface::STAGE_LIVE]);

        $dimensionContentRepository->load($contentRichEntity->reveal(), $liveDimensionAttributes)
            ->willReturn($dimensionContentCollection)
            ->shouldBeCalled();

        $entityManager->remove(Argument::cetera())->shouldNotBeCalled();

        $contentUnpublishSubscriber->onUnpublish($event);
    }

    public function testOnUnpublish(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setStage('stage');
        $dimensionContent->setLocale('en');
        $dimensionContent->setWorkflowPublished(new \DateTimeImmutable());
        $dimensionContent->setShadowLocale('de');

        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $event = new TransitionEvent(
            $dimensionContent,
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $example,
        ]);

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $contentUnpublishSubscriber = $this->createContentUnpublishSubscriberInstance(
            $dimensionContentRepository->reveal(),
            $entityManager->reveal()
        );

        $localizedLiveDimensionContent = new ExampleDimensionContent($example);
        $localizedLiveDimensionContent->setStage('live');
        $localizedLiveDimensionContent->setLocale('en');
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $dimensionContentCollection->getDimensionContent(['locale' => 'en', 'stage' => 'live'])
            ->willReturn($localizedLiveDimensionContent)
            ->shouldBeCalled();

        $unlocalizedLiveDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedLiveDimensionContent->setStage('live');
        $unlocalizedLiveDimensionContent->addAvailableLocale('en');
        $unlocalizedLiveDimensionContent->addAvailableLocale('de');
        $unlocalizedLiveDimensionContent->addShadowLocale('en', 'de');
        $dimensionContentCollection->getDimensionContent(['locale' => null, 'stage' => 'live'])
            ->willReturn($unlocalizedLiveDimensionContent)
            ->shouldBeCalled();

        $liveDimensionAttributes = \array_merge($dimensionAttributes, ['stage' => DimensionContentInterface::STAGE_LIVE]);

        $dimensionContentRepository->load($example, $liveDimensionAttributes)
            ->willReturn($dimensionContentCollection)
            ->shouldBeCalled();

        $entityManager->remove($localizedLiveDimensionContent)->shouldBeCalled();

        $contentUnpublishSubscriber->onUnpublish($event);
        $this->assertSame(['de'], $unlocalizedLiveDimensionContent->getAvailableLocales());
        $this->assertNull($dimensionContent->getWorkflowPublished());
        $this->assertNull($unlocalizedLiveDimensionContent->getShadowLocales());
    }
}
