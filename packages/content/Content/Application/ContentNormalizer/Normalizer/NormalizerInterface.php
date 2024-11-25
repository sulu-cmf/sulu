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

interface NormalizerInterface
{
    /**
     * @return string[]
     */
    public function getIgnoredAttributes(object $object): array;

    /**
     * @param mixed[] $normalizedData
     *
     * @return mixed[]
     */
    public function enhance(object $object, array $normalizedData): array;
}
