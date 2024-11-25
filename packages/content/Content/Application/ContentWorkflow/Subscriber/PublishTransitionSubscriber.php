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

use Sulu\Bundle\ContentBundle\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class PublishTransitionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContentCopierInterface
     */
    private $contentCopier;

    public function __construct(ContentCopierInterface $contentCopier)
    {
        $this->contentCopier = $contentCopier;
    }

    public function onPublish(TransitionEvent $transitionEvent): void
    {
        $dimensionContent = $transitionEvent->getSubject();

        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        if ($dimensionContent instanceof WorkflowInterface) {
            if (!$dimensionContent->getWorkflowPublished()) {
                $dimensionContent->setWorkflowPublished(new \DateTimeImmutable());
            }
        }

        $context = $transitionEvent->getContext();

        $dimensionContentCollection = $context[ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY] ?? null;
        $dimensionAttributes = $context[ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY] ?? null;
        $contentRichEntity = $context[ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY] ?? null;

        if (!$dimensionAttributes) {
            throw new \RuntimeException('No "dimensionAttributes" given.');
        }

        if (!$dimensionContentCollection instanceof DimensionContentCollectionInterface) {
            throw new \RuntimeException('No "dimensionContentCollection" given.');
        }

        if (!$contentRichEntity instanceof ContentRichEntityInterface) {
            throw new \RuntimeException('No "contentRichEntity" given.');
        }

        $sourceDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes['stage'] = DimensionContentInterface::STAGE_LIVE;

        $shadowLocale = $dimensionContent instanceof ShadowInterface
            ? $dimensionContent->getShadowLocale()
            : null;

        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        if (!$shadowLocale) {
            $publishedDimensionContent = $this->contentCopier->copyFromDimensionContentCollection(
                $dimensionContentCollection,
                $contentRichEntity,
                $targetDimensionAttributes
            );

            if (!$publishedDimensionContent instanceof ShadowInterface) {
                return;
            }

            $shadowLocales = $publishedDimensionContent->getShadowLocalesForLocale($locale);

            foreach ($shadowLocales as $shadowLocale) {
                $targetDimensionAttributes['locale'] = $shadowLocale;

                $this->contentCopier->copyFromDimensionContentCollection(
                    $dimensionContentCollection,
                    $contentRichEntity,
                    $targetDimensionAttributes,
                    [
                        'ignoredAttributes' => [
                            'shadowOn',
                            'shadowLocale',
                            'url',
                        ],
                    ]
                );
            }

            return;
        }

        $sourceDimensionAttributes['locale'] = $shadowLocale;
        $sourceDimensionAttributes['stage'] = DimensionContentInterface::STAGE_LIVE;

        $data = [
            // @see \Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\ShadowDataMapper::map
            'shadowOn' => true,
            'shadowLocale' => $shadowLocale,
        ];

        if ($dimensionContent instanceof TemplateInterface) {
            $data['url'] = $dimensionContent->getTemplateData()['url'] ?? null; // TODO get correct route property
        }

        $this->contentCopier->copy(
            $contentRichEntity,
            $sourceDimensionAttributes,
            $contentRichEntity,
            $targetDimensionAttributes,
            ['data' => $data]
        );
    }

    public static function getSubscribedEvents(): array
    {
        $eventName = 'workflow.content_workflow.transition.' . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH;

        return [
            $eventName => 'onPublish',
        ];
    }
}
