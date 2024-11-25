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
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;
use Webmozart\Assert\Assert;

class ShadowNormalizer implements NormalizerInterface
{
    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof ShadowInterface) {
            return $normalizedData;
        }

        Assert::isInstanceOf($object, DimensionContentInterface::class);

        $normalizedData['shadowOn'] = null !== $object->getShadowLocale();
        $normalizedData['shadowLocales'] = $normalizedData['shadowLocales'] ?? [];
        $normalizedData['contentLocales'] = $object->getAvailableLocales() ?? []; // TODO should be changed in Sulu Core (PageSettingsShadowLocaleSelect.js)

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        if (!$object instanceof ShadowInterface) {
            return [];
        }

        return [];
    }
}
