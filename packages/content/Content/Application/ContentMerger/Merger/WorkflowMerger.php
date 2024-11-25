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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own merger instead.
 */
final class WorkflowMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof WorkflowInterface) {
            return;
        }

        if (!$sourceObject instanceof WorkflowInterface) {
            return;
        }

        if ($workflowPlace = $sourceObject->getWorkflowPlace()) {
            $targetObject->setWorkflowPlace($workflowPlace);
        }

        if ($workflowPublished = $sourceObject->getWorkflowPublished()) {
            $targetObject->setWorkflowPublished($workflowPublished);
        }
    }
}
