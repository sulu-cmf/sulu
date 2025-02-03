<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkflowStageSubscriber implements EventSubscriberInterface
{
    public const WORKFLOW_STAGE_FIELD = 'state';

    public const PUBLISHED_FIELD = 'published';

    public function __construct(
        private PropertyEncoder $propertyEncoder,
        private DocumentInspector $documentInspector,
        private SessionInterface $defaultSession,
        private SessionInterface $liveSession,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::HYDRATE => 'setWorkflowStageOnDocument',
            Events::PERSIST => 'setWorkflowStageToTest',
            Events::PUBLISH => 'setWorkflowStageToPublished',
            Events::UNPUBLISH => 'setWorkflowStageToTestAndResetPublishedDate',
            Events::COPY => 'setWorkflowStageToTestForCopy',
            Events::RESTORE => ['setWorkflowStageToTestForRestore', -32],
        ];
    }

    /**
     * Sets the workflow properties from the node on the document.
     *
     * @throws DocumentManagerException
     */
    public function setWorkflowStageOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($event)) {
            return;
        }

        $node = $event->getNode();
        $locale = $this->documentInspector->getOriginalLocale($document);

        $document->setWorkflowStage(
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, $locale),
                WorkflowStage::TEST
            )
        );

        $event->getAccessor()->set(
            self::PUBLISHED_FIELD,
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->localizedSystemName(self::PUBLISHED_FIELD, $locale),
                null
            )
        );
    }

    /**
     * Sets the workflow stage for the passed document to test.
     */
    public function setWorkflowStageToTest(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($event)) {
            return;
        }

        $this->setWorkflowStage($document, $event->getAccessor(), WorkflowStage::TEST, $event->getLocale(), false);
    }

    /**
     * Sets the workflow stage for the passed document to published.
     */
    public function setWorkflowStageToPublished(PublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($event)) {
            return;
        }

        $this->setWorkflowStage($document, $event->getAccessor(), WorkflowStage::PUBLISHED, $event->getLocale(), true);
    }

    /**
     * Resets the workflowstage to test and the published date to null.
     */
    public function setWorkflowStageToTestAndResetPublishedDate(UnpublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($event)) {
            return;
        }

        $locale = $event->getLocale();

        $node = $this->defaultSession->getNode($this->documentInspector->getPath($document));
        $node->setProperty(
            $this->propertyEncoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, $locale),
            WorkflowStage::TEST
        );

        $node->setProperty($this->propertyEncoder->localizedSystemName(self::PUBLISHED_FIELD, $locale), null);
    }

    /**
     * Sets the workflowstage for the copied node and all its children to test. This is done because newly copied pages
     * shouldn't be automatically published on the website.
     */
    public function setWorkflowStageToTestForCopy(CopyEvent $event)
    {
        $this->setNodeWorkflowStageToTestForCopy($event->getCopiedNode());
    }

    /**
     * Sets the workflowstage for the restored node to test.
     */
    public function setWorkflowStageToTestForRestore(RestoreEvent $event)
    {
        if (!$this->supports($event)) {
            return;
        }

        $this->setWorkflowStageOnNode($event->getNode(), $event->getLocale(), WorkflowStage::TEST, null);
    }

    /**
     * Sets the workflowstage and the published date for the given node and all of its children to test resp. null. This
     * is done for every language in which the given properties exist.
     */
    private function setNodeWorkflowStageToTestForCopy(NodeInterface $node)
    {
        $workflowStageNameFilter = $this->propertyEncoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, '*');
        /** @var PropertyInterface<mixed> $property */
        foreach ($node->getProperties($workflowStageNameFilter) as $property) {
            $property->setValue(WorkflowStage::TEST);
        }

        $publishedNameFilter = $this->propertyEncoder->localizedSystemName(self::PUBLISHED_FIELD, '*');
        /** @var PropertyInterface<mixed> $property */
        foreach ($node->getProperties($publishedNameFilter) as $property) {
            $property->setValue(null);
        }

        /** @var NodeInterface<mixed> $node */
        foreach ($node->getNodes() as $node) {
            $this->setNodeWorkflowStageToTestForCopy($node);
        }
    }

    /**
     * Determines if the given document is supported by this subscriber.
     *
     * @param HydrateEvent|PublishEvent|PersistEvent $event
     *
     * @return bool
     */
    private function supports($event)
    {
        return $event->getLocale() && $event->getDocument() instanceof WorkflowStageBehavior;
    }

    /**
     * Sets the workflow properties on the given document.
     *
     * @param string $workflowStage
     * @param string $locale
     * @param string $live
     *
     * @throws DocumentManagerException
     */
    private function setWorkflowStage(
        WorkflowStageBehavior $document,
        DocumentAccessor $accessor,
        $workflowStage,
        $locale,
        $live
    ) {
        $path = $this->documentInspector->getPath($document);
        $document->setWorkflowStage($workflowStage);

        $publishDate = $document->getPublished();

        if (!$publishDate && WorkflowStage::PUBLISHED === $workflowStage) {
            $publishDate = new \DateTime();
            $accessor->set(self::PUBLISHED_FIELD, $publishDate);
        }

        $defaultNode = $this->defaultSession->getNode($path);
        $this->setWorkflowStageOnNode($defaultNode, $locale, $workflowStage, $publishDate);

        if ($live) {
            $liveNode = $this->liveSession->getNode($path);
            $this->setWorkflowStageOnNode($liveNode, $locale, $workflowStage, $publishDate);
        }
    }

    /**
     * Sets the workflow stage properties on the given node.
     *
     * @param string $locale
     * @param int $workflowStage
     */
    private function setWorkflowStageOnNode(NodeInterface $node, $locale, $workflowStage, ?\DateTime $publishDate = null)
    {
        $node->setProperty(
            $this->propertyEncoder->localizedSystemName(self::WORKFLOW_STAGE_FIELD, $locale),
            $workflowStage
        );

        if ($publishDate) {
            $node->setProperty(
                $this->propertyEncoder->localizedSystemName(self::PUBLISHED_FIELD, $locale),
                $publishDate
            );
        }
    }
}
