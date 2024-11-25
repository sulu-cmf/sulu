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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;

class WorkflowNormalizer implements NormalizerInterface
{
    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof WorkflowInterface) {
            return $normalizedData;
        }

        $normalizedData['publishedState'] = WorkflowInterface::WORKFLOW_PLACE_PUBLISHED === $normalizedData['workflowPlace'];
        $normalizedData['published'] = $normalizedData['workflowPublished'];
        unset($normalizedData['workflowPublished']);

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof WorkflowInterface) {
            return [];
        }

        return [];
    }
}
