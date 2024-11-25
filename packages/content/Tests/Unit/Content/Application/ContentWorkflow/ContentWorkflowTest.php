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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentWorkflow;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflow;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Bundle\ContentBundle\Content\Domain\Exception\UnavailableContentTransitionException;
use Sulu\Bundle\ContentBundle\Content\Domain\Exception\UnknownContentTransitionException;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollection;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Repository\DimensionContentRepositoryInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\ContentBundle\Tests\Unit\Mocks\DimensionContentMockWrapperTrait;
use Sulu\Bundle\ContentBundle\Tests\Unit\Mocks\MockWrapper;
use Sulu\Bundle\ContentBundle\Tests\Unit\Mocks\WorkflowMockWrapperTrait;

class ContentWorkflowTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createContentWorkflowInstance(
        DimensionContentRepositoryInterface $dimensionContentRepository,
        ContentMergerInterface $contentMerger
    ): ContentWorkflowInterface {
        return new ContentWorkflow(
            $dimensionContentRepository,
            $contentMerger
        );
    }

    /**
     * @template T of DimensionContentInterface&WorkflowInterface
     *
     * @param ObjectProphecy<T> $templateMock
     *
     * @return T
     */
    protected function wrapWorkflowMock(ObjectProphecy $templateMock): WorkflowInterface
    {
        return new class($templateMock) extends MockWrapper implements // @phpstan-ignore-line
            DimensionContentInterface,
            WorkflowInterface {
            use DimensionContentMockWrapperTrait;
            use WorkflowMockWrapperTrait;

            /**
             * @var T
             */
            protected $instance;

            public static function getWorkflowName(): string
            {
                return 'content_workflow';
            }
        };
    }

    public function testTransitionNoWorkflowInterface(): void
    {
        $this->expectException(\RuntimeException::class);

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);

        $contentWorkflow = $this->createContentWorkflowInstance(
            $dimensionContentRepository->reveal(),
            $contentMerger->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];
        $transitionName = 'request_for_review';

        $dimensionContent1 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->getStage()->willReturn('draft');
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent2 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->getStage()->willReturn('draft');
        $dimensionContent1->getLocale()->willReturn('de');

        $this->expectExceptionMessage(\sprintf(
            'Expected "%s" but "%s" given.',
            WorkflowInterface::class,
            \get_class($dimensionContent2->reveal()))
        );

        $dimensionContentCollection = new DimensionContentCollection([
            $dimensionContent1->reveal(),
            $dimensionContent2->reveal(),
        ], $dimensionAttributes, ExampleDimensionContent::class);

        $dimensionContentRepository->load($contentRichEntity->reveal(), $dimensionAttributes)
            ->willReturn($dimensionContentCollection)
            ->shouldBeCalled();

        $contentWorkflow->apply(
            $contentRichEntity->reveal(),
            $dimensionAttributes,
            $transitionName
        );
    }

    public function testTransitionNoLocalizedDimensionContent(): void
    {
        $this->expectException(ContentNotFoundException::class);

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);

        $contentWorkflow = $this->createContentWorkflowInstance(
            $dimensionContentRepository->reveal(),
            $contentMerger->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];
        $transitionName = 'request_for_review';

        $dimensionContent1 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent1->getStage()->willReturn('draft');

        $dimensionContentCollection = new DimensionContentCollection([
            $dimensionContent1->reveal(),
        ], $dimensionAttributes, ExampleDimensionContent::class);

        $dimensionContentRepository->load($contentRichEntity->reveal(), $dimensionAttributes)
            ->willReturn($dimensionContentCollection)
            ->shouldBeCalled();

        $contentWorkflow->apply(
            $contentRichEntity->reveal(),
            $dimensionAttributes,
            $transitionName
        );
    }

    public function testNotExistTransition(): void
    {
        $this->expectException(UnknownContentTransitionException::class);
        $this->expectExceptionMessage(
            'Transition "not-exist-transition" is not defined for workflow "content_workflow".'
        );

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);

        $contentWorkflow = $this->createContentWorkflowInstance(
            $dimensionContentRepository->reveal(),
            $contentMerger->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];

        $dimensionContent1 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->willImplement(WorkflowInterface::class);
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent1->getStage()->willReturn('draft');

        $dimensionContent2 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent2->willImplement(WorkflowInterface::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getStage()->willReturn('draft');

        $dimensionContent2->getWorkflowPlace()
            ->willReturn('unpublished')
            ->shouldBeCalled();

        $dimensionContentCollection = new DimensionContentCollection([
            $this->wrapWorkflowMock($dimensionContent1),
            $this->wrapWorkflowMock($dimensionContent2),
        ], $dimensionAttributes, ExampleDimensionContent::class);

        $dimensionContentRepository->load($contentRichEntity->reveal(), $dimensionAttributes)
            ->willReturn($dimensionContentCollection)
            ->shouldBeCalled();

        $contentWorkflow->apply(
            $contentRichEntity->reveal(),
            $dimensionAttributes,
            'not-exist-transition'
        );
    }

    /**
     * @dataProvider transitionProvider
     */
    public function testTransitions(
        string $currentPlace,
        string $transitionName,
        bool $isTransitionAllowed
    ): void {
        if (!$isTransitionAllowed) {
            $this->expectException(UnavailableContentTransitionException::class);
        }

        $dimensionContentRepository = $this->prophesize(DimensionContentRepositoryInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);

        $contentWorkflow = $this->createContentWorkflowInstance(
            $dimensionContentRepository->reveal(),
            $contentMerger->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];

        $dimensionContent1 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->willImplement(WorkflowInterface::class);
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent1->getStage()->willReturn('draft');
        $dimensionContent2 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent2->willImplement(WorkflowInterface::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getStage()->willReturn('draft');

        $dimensionContent2->getWorkflowPlace()
            ->willReturn($currentPlace)
            ->shouldBeCalled();

        if ($isTransitionAllowed) {
            $dimensionContent2->setWorkflowPlace(Argument::any(), Argument::any())
                ->shouldBeCalled();
        }

        $dimensionContentCollection = new DimensionContentCollection([
            $this->wrapWorkflowMock($dimensionContent1),
            $this->wrapWorkflowMock($dimensionContent2),
        ], $dimensionAttributes, ExampleDimensionContent::class);

        $dimensionContentRepository->load($contentRichEntity->reveal(), $dimensionAttributes)
            ->willReturn($dimensionContentCollection)
            ->shouldBeCalled();

        $mergedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $contentMerger->merge($dimensionContentCollection)
            ->willReturn($mergedDimensionContent)
            ->shouldBeCalledTimes($isTransitionAllowed ? 1 : 0);

        $this->assertSame(
            $isTransitionAllowed ? $mergedDimensionContent->reveal() : null,
            $contentWorkflow->apply(
                $contentRichEntity->reveal(),
                $dimensionAttributes,
                $transitionName
            )
        );
    }

    /**
     * @return \Generator<mixed[]>
     */
    public function transitionProvider(): \Generator
    {
        $places = [
            'unpublished' => [
                'request_for_review' => true,
                'reject' => false,
                'publish' => true,
                'unpublish' => false,
                'create_draft' => false,
                'remove_draft' => false,
                'request_for_review_draft' => false,
                'reject_draft' => false,
            ],
            'review' => [
                'request_for_review' => false,
                'reject' => true,
                'publish' => true,
                'unpublish' => false,
                'create_draft' => false,
                'remove_draft' => false,
                'request_for_review_draft' => false,
                'reject_draft' => false,
            ],
            'published' => [
                'request_for_review' => false,
                'reject' => false,
                'publish' => true,
                'unpublish' => true,
                'create_draft' => true,
                'remove_draft' => false,
                'request_for_review_draft' => false,
                'reject_draft' => false,
            ],
            'draft' => [
                'request_for_review' => false,
                'reject' => false,
                'publish' => true,
                'unpublish' => true,
                'create_draft' => false,
                'remove_draft' => true,
                'request_for_review_draft' => true,
                'reject_draft' => false,
            ],
            'review_draft' => [
                'request_for_review' => false,
                'reject' => false,
                'publish' => true,
                'unpublish' => false,
                'create_draft' => false,
                'remove_draft' => false,
                'request_for_review_draft' => false,
                'reject_draft' => true,
            ],
        ];

        foreach ($places as $place => $transitions) {
            foreach ($transitions as $transition => $allowed) {
                yield [
                    $place,
                    $transition,
                    $allowed,
                ];
            }
        }
    }
}
