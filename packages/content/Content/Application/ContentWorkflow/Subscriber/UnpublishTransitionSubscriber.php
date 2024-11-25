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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Repository\DimensionContentRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class UnpublishTransitionSubscriber implements EventSubscriberInterface
{
    /**
     * @var DimensionContentRepositoryInterface
     */
    private $dimensionContentRepository;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(
        DimensionContentRepositoryInterface $dimensionContentRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->dimensionContentRepository = $dimensionContentRepository;
        $this->entityManager = $entityManager;
    }

    public function onUnpublish(TransitionEvent $transitionEvent): void
    {
        $dimensionContent = $transitionEvent->getSubject();

        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        if ($dimensionContent instanceof WorkflowInterface) {
            $dimensionContent->setWorkflowPublished(null);
        }

        $context = $transitionEvent->getContext();

        $dimensionAttributes = $context[ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY] ?? null;
        if (!$dimensionAttributes) {
            throw new \RuntimeException('Transition context must contain "dimensionAttributes".');
        }

        $contentRichEntity = $context[ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY] ?? null;
        if (!$contentRichEntity instanceof ContentRichEntityInterface) {
            throw new \RuntimeException('Transition context must contain "contentRichEntity".');
        }

        $liveDimensionAttributes = \array_merge($dimensionAttributes, ['stage' => DimensionContentInterface::STAGE_LIVE]);
        $dimensionContentCollection = $this->dimensionContentRepository->load($contentRichEntity, $liveDimensionAttributes);
        $localizedLiveDimensionContent = $dimensionContentCollection->getDimensionContent($liveDimensionAttributes);

        if (!$localizedLiveDimensionContent) {
            throw new ContentNotFoundException($contentRichEntity, $liveDimensionAttributes);
        }

        $locale = $localizedLiveDimensionContent->getLocale();

        if ($locale) {
            $unlocalizedLiveDimensionAttributes = \array_merge($liveDimensionAttributes, ['locale' => null]);

            /** @var DimensionContentInterface $unlocalizedLiveDimensionContent */
            $unlocalizedLiveDimensionContent = $dimensionContentCollection->getDimensionContent($unlocalizedLiveDimensionAttributes);  // @phpstan-ignore-line we can not define the generic of DimensionContentInterface here
            $unlocalizedLiveDimensionContent->removeAvailableLocale($locale);

            if ($unlocalizedLiveDimensionContent instanceof ShadowInterface) {
                $unlocalizedLiveDimensionContent->removeShadowLocale($locale);
            }
        }

        $this->entityManager->remove($localizedLiveDimensionContent);
    }

    public static function getSubscribedEvents(): array
    {
        $eventName = 'workflow.content_workflow.transition.' . WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH;

        return [
            $eventName => 'onUnpublish',
        ];
    }
}
