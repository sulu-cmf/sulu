<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;

class WorkflowStageSubscriber implements EventSubscriberInterface
{
    const WORKFLOW_STAGE_FIELD = 'state';
    const PUBLISHED_FIELD = 'published';

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @param PropertyEncoder $encoder
     */
    public function __construct(
        PropertyEncoder $encoder
    ) {
        $this->encoder = $encoder;
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::METADATA_LOAD => 'handleMetadataLoad',
            Events::PERSIST => 'handlePersist',
        );
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(WorkflowStageBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('workflowStage', array(
            'encoding' => 'system_localized',
            'property' => self::WORKFLOW_STAGE_FIELD,
            'type' => 'long',
        ));
        $metadata->addFieldMapping('published', array(
            'encoding' => 'system_localized',
            'property' => self::PUBLISHED_FIELD,
            'type' => 'date',
        ));
    }

    /**
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof WorkflowStageBehavior) {
            return;
        }

        $stage = $document->getWorkflowStage();
        $node = $event->getNode();
        $locale = $event->getLocale();
        $persistedStage = $this->getWorkflowStage($node, $locale);

        if ($stage == WorkflowStage::PUBLISHED && $stage !== $persistedStage) {
            $event->getAccessor()->set(self::PUBLISHED_FIELD, new \DateTime());
        }

        if ($stage == WorkflowStage::TEST && $stage !== $persistedStage) {
            $event->getAccessor()->set(self::PUBLISHED_FIELD, null);
        }

        $document->setWorkflowStage($stage);
    }

    private function getWorkflowStage(NodeInterface $node, $locale)
    {
        $value = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, $locale),
            null
        );

        return $value;
    }
}
