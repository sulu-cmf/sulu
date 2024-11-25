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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;

class WorkflowDataMapper implements DataMapperInterface
{
    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof WorkflowInterface) {
            return;
        }

        $this->setWorkflowData($localizedDimensionContent, $data);
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param WorkflowInterface&T $object
     * @param mixed[] $data
     */
    private function setWorkflowData(WorkflowInterface $object, array $data): void
    {
        $this->setInitialPlaceToDraftDimension($object, $data);
        $this->setPublishedToLiveDimension($object, $data);
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param WorkflowInterface&T $object
     * @param mixed[] $data
     */
    private function setInitialPlaceToDraftDimension(WorkflowInterface $object, array $data): void
    {
        // we want to set the initial place only to the draft dimension, the live dimension should not have a place
        // after the place was set by this mapper initially, the place should only be changed by the ContentWorkflow
        // see: https://github.com/sulu/SuluContentBundle/issues/92

        if (DimensionContentInterface::STAGE_DRAFT !== $object->getStage()) {
            return;
        }

        if (!$object->getWorkflowPlace()) {
            // TODO: get public workflow registry and set initial place based on $object::getWorkflowName()
            $object->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_UNPUBLISHED);
        }
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param WorkflowInterface&T $object
     * @param mixed[] $data
     */
    private function setPublishedToLiveDimension(WorkflowInterface $object, array $data): void
    {
        // the published property of the draft dimension should only be changed by a ContentWorkflow subscriber
        // therefore we only want to copy the published property from the draft to the live dimension

        if (DimensionContentInterface::STAGE_LIVE !== $object->getStage()) {
            return;
        }

        /** @var string|null $published */
        $published = $data['published'] ?? null;

        if (!$published) {
            throw new \RuntimeException('Expected "published" to be set in the data array.');
        }

        $object->setWorkflowPublished(new \DateTimeImmutable($published));
    }
}
