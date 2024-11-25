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

use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;

class TemplateNormalizer implements NormalizerInterface
{
    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof TemplateInterface) {
            return $normalizedData;
        }

        $normalizedData = \array_merge($normalizedData['templateData'], $normalizedData); // @phpstan-ignore-line TODO check whats going on here
        unset($normalizedData['templateData']);

        $normalizedData['template'] = $normalizedData['templateKey'];
        unset($normalizedData['templateKey']);

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof TemplateInterface) {
            return [];
        }

        return [];
    }
}
