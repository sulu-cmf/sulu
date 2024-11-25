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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer;

interface ContentNormalizerInterface
{
    /**
     * @return mixed[]
     */
    public function normalize(object $object): array;
}
