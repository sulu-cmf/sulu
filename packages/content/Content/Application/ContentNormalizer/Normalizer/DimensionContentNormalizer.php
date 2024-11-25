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

use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class DimensionContentNormalizer implements NormalizerInterface
{
    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof DimensionContentInterface) {
            return [];
        }

        return [
            'id',
            'merged',
            'dimension',
            'resource',
        ];
    }

    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof DimensionContentInterface) {
            return $normalizedData;
        }

        $normalizedData['id'] = $object->getResource()->getId();
        $normalizedData['locale'] = $object->getLocale();
        $normalizedData['stage'] = $object->getStage();

        return $normalizedData;
    }
}
